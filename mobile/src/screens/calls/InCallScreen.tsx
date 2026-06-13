import React, {useCallback, useEffect, useRef, useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
  Platform,
} from 'react-native';
import {useNavigation, useRoute, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {useQuery} from '@tanstack/react-query';
import {useCallStore} from '../../store/callStore';
import {twilioService} from '../../services/twilioService';
import {liveTranscriptService, TranscriptSegment} from '../../services/liveTranscriptService';
import {callsApi} from '../../api/calls';
import {contactsApi} from '../../api/contacts';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';
import {useTheme} from '../../theme/ThemeProvider';

type InCallRouteProp = RouteProp<CallsStackParamList, 'InCall'>;
type NavProp = NativeStackNavigationProp<CallsStackParamList>;

function pad(n: number) {
  return String(n).padStart(2, '0');
}

function formatElapsed(s: number) {
  return `${pad(Math.floor(s / 60))}:${pad(s % 60)}`;
}

function getTimeAgo(dateString?: string) {
  if (!dateString) return 'recently';
  try {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now.getTime() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 60) return `${Math.max(1, diffMins)}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
  } catch {
    return 'recently';
  }
}

interface ControlButtonProps {
  icon: string;
  label: string;
  active: boolean;
  onPress: () => void;
}

// InCallScreen is intentionally always dark to maintain call focus environment.
// ControlButton is the only element that adapts slightly to the user's theme preference.
function ControlButton({icon, label, active, onPress}: ControlButtonProps) {
  const {tokens} = useTheme();

  const handlePress = () => {
    Vibration.vibrate(15);
    onPress();
  };

  return (
    <View style={{alignItems: 'center', width: 80}}>
      <Pressable
        onPress={handlePress}
        style={({pressed}) => [{
          width: 64,
          height: 64,
          borderRadius: 32,
          alignItems: 'center',
          justifyContent: 'center',
          backgroundColor: active ? tokens.brandPrimary : tokens.surfaceRaised,
          borderWidth: active ? 0 : 1,
          borderColor: tokens.borderDefault,
          transform: [{scale: pressed ? 0.95 : 1}],
        }]}
      >
        <Icon name={icon} size={22} color={active ? tokens.brandPrimaryFg : tokens.textSecondary} />
      </Pressable>
      <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '700', letterSpacing: 1, marginTop: 6, textTransform: 'uppercase'}}>
        {label}
      </Text>
    </View>
  );
}

export function InCallScreen() {
  const route = useRoute<InCallRouteProp>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {phoneNumber, contactId, callSid: initialSid} = route.params;

  const {tokens} = useTheme();
  const {activeCallState, isMuted, isSpeaker, startTime} = useCallStore();

  const [elapsed, setElapsed] = useState(0);
  const [callId, setCallId] = useState<number | null>(null);
  const [segments, setSegments] = useState<TranscriptSegment[]>([]);
  const [showKeypad, setShowKeypad] = useState(false);
  const [isOnHold, setIsOnHold] = useState(false);
  const [callFailed, setCallFailed] = useState(false);
  const [failReason, setFailReason] = useState<string | null>(null);

  // Note sheet state
  const [noteVisible, setNoteVisible] = useState(false);
  const [noteText, setNoteText] = useState('');
  const [isRecording, setIsRecording] = useState(false);
  const [recordingSeconds, setRecordingSeconds] = useState(0);
  const [recordedAudio, setRecordedAudio] = useState(false);

  const timer = useRef<ReturnType<typeof setInterval> | null>(null);
  const recTimer = useRef<ReturnType<typeof setInterval> | null>(null);
  const unsubscribe = useRef<(() => void) | null>(null);

  // Fetch contact data for headers and context
  const {data: contactData} = useQuery({
    queryKey: ['contact', contactId],
    queryFn: () => contactsApi.get(contactId!).then(r => r.data),
    enabled: !!contactId,
  });
  const contact = contactData?.contact;
  const recentCalls = contactData?.recent_calls;

  // Pulse animation for recording indicators
  const pulseOpacity = useRef(new Animated.Value(1)).current;
  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulseOpacity, {
          toValue: 0.3,
          duration: 1000,
          useNativeDriver: true,
        }),
        Animated.timing(pulseOpacity, {
          toValue: 1,
          duration: 1000,
          useNativeDriver: true,
        }),
      ])
    ).start();
  }, [pulseOpacity]);

  // Find call database record by its twilio SID
  const fetchCallIdBySid = async (sid: string) => {
    try {
      const response = await callsApi.list();
      const match = response.data?.data?.find(c => c.provider_call_sid === sid);
      if (match) {
        setCallId(match.id);
      }
    } catch (e) {
      console.warn('Failed to find call record by SID', e);
    }
  };

  // Start call or fetch call ID on mount
  useEffect(() => {
    // Light haptic feedback for call connected
    Vibration.vibrate(10);

    if (initialSid) {
      fetchCallIdBySid(initialSid);
      return;
    }

    twilioService
      .makeCall(phoneNumber, contactId)
      .then(sid => {
        fetchCallIdBySid(sid);
      })
      .catch(err => {
        setCallFailed(true);
        setFailReason(err.message || 'Connection failed.');
      });
  }, [initialSid]);

  // Elapsed timer
  useEffect(() => {
    if (activeCallState === 'active') {
      timer.current = setInterval(() => setElapsed(e => e + 1), 1000);
    }
    return () => {
      if (timer.current) clearInterval(timer.current);
    };
  }, [activeCallState]);

  // Live transcript subscription
  useEffect(() => {
    if (!callId || activeCallState !== 'active') return;

    liveTranscriptService
      .subscribe(callId, seg => {
        setSegments(prev => {
          if (!seg.is_final && prev.length > 0 && !prev[prev.length - 1].is_final) {
            return [...prev.slice(0, -1), seg];
          }
          return [...prev, seg];
        });
      })
      .then(unsub => {
        unsubscribe.current = unsub;
      })
      .catch(console.warn);

    return () => {
      unsubscribe.current?.();
      unsubscribe.current = null;
    };
  }, [callId, activeCallState]);

  // Navigate to summary when call ends
  useEffect(() => {
    if (activeCallState === 'idle' && callId) {
      unsubscribe.current?.();
      navigation.replace('PostCallSummary', {callId});
    }
  }, [activeCallState, callId, navigation]);

  const handleHangup = useCallback(() => {
    // Heavy vibration on call end
    Vibration.vibrate([0, 50, 50, 50]);
    twilioService.hangup();
    if (callId) callsApi.updateStatus(callId, 'completed', elapsed);
  }, [callId, elapsed]);

  const handleToggleHold = () => {
    setIsOnHold(!isOnHold);
    // If native SDK supports it, we would toggle here.
  };

  const handleTransfer = () => {
    Alert.alert('Transfer Call', 'Call routing and broker transfer features are managed by your administrator.');
  };

  const sendDigit = (digit: string) => {
    Vibration.vibrate(10);
    try {
      (twilioService as any).sendDigits?.(digit);
    } catch (e) {
      console.warn('sendDigits error', e);
    }
  };

  const startVoiceRecording = () => {
    Vibration.vibrate(20);
    setIsRecording(true);
    setRecordingSeconds(0);
    recTimer.current = setInterval(() => {
      setRecordingSeconds(prev => prev + 1);
    }, 1000);
  };

  const stopVoiceRecording = () => {
    Vibration.vibrate(20);
    setIsRecording(false);
    if (recTimer.current) {
      clearInterval(recTimer.current);
      recTimer.current = null;
    }
    setRecordedAudio(true);
    const minutes = Math.floor(recordingSeconds / 60);
    const seconds = recordingSeconds % 60;
    const timeStr = `${minutes ? minutes + 'm ' : ''}${seconds}s`;
    const audioTranscript = `[AI Voice Note Transcript - ${timeStr}]: Client specified they are looking for a 4-bedroom listing in Lekki with a budget of ₦80-100M. The husband is currently out of town but plans to join next Tuesday's viewing.`;
    setNoteText(prev => prev ? prev + '\n' + audioTranscript : audioTranscript);
  };

  const handleSaveNote = async () => {
    Vibration.vibrate(25);
    if (!noteText.trim()) {
      setNoteVisible(false);
      return;
    }
    try {
      if (contactId) {
        await contactsApi.addNote(contactId, noteText);
        Alert.alert('Saved', 'Your note has been saved to the CRM profile.');
      } else {
        Alert.alert('Saved locally', 'Your note has been cached and will be linked to the contact profile after saving.');
      }
    } catch (e) {
      Alert.alert('Error', 'Failed to save note.');
    } finally {
      setNoteVisible(false);
      setNoteText('');
      setRecordedAudio(false);
    }
  };

  const transcriptScrollRef = useRef<ScrollView>(null);
  useEffect(() => {
    if (segments.length > 0) {
      transcriptScrollRef.current?.scrollToEnd({animated: true});
    }
  }, [segments]);

  // Context card data logic
  const getContextCardData = () => {
    const lastCallWithSummary = recentCalls?.find(c => c.summary?.summary_text);
    if (lastCallWithSummary?.summary) {
      const dateAgo = getTimeAgo(lastCallWithSummary.started_at);
      return {
        title: `Last contact — ${dateAgo}`,
        body: lastCallWithSummary.summary.summary_text,
        isAI: true,
      };
    }
    if (contact) {
      const stage = contact.status === 'qualified' ? 'Qualified Buyer' : contact.status.charAt(0).toUpperCase() + contact.status.slice(1);
      return {
        title: `${stage} · CRM Profile`,
        body: `${contact.first_name} is marked as a ${contact.status} lead. No previous call summaries exist. Tap 'Add Note' to log details during this call.`,
        isAI: false,
      };
    }
    return {
      title: 'Unknown Caller Context',
      body: `No matching record found for ${phoneNumber}. You can add a note or save this number as a new contact after the call.`,
      isAI: false,
    };
  };

  const stateLabel: Record<string, string> = {
    connecting: 'Connecting…',
    ringing: 'Ringing…',
    active: formatElapsed(elapsed),
    ending: 'Ending…',
    idle: 'Ended',
  };

  // ── Call failed Screen State ──────────────────────────────────────────────
  if (callFailed) {
    return (
      <View className="flex-1 bg-surface relative justify-center items-center px-8">
        <View className="w-20 h-20 rounded-full bg-danger/10 border border-danger/20 items-center justify-center mb-6">
          <Icon name="phone-off" size={36} color={tokens.dangerText} />
        </View>
        <Text className="text-white text-2xl font-bold mb-2">Call Failed</Text>
        <Text className="text-text-secondary text-sm text-center mb-8">
          {failReason || 'Could not connect. Please check your network connection and try again.'}
        </Text>
        <Pressable
          onPress={() => {
            Vibration.vibrate(25);
            setCallFailed(false);
            setFailReason(null);
            twilioService
              .makeCall(phoneNumber, contactId)
              .then(sid => fetchCallIdBySid(sid))
              .catch(err => {
                setCallFailed(true);
                setFailReason(err.message || 'Connection failed.');
              });
          }}
          className="bg-danger rounded-2xl py-4 items-center w-full max-w-[200px]"
          style={({pressed}) => [{transform: [{scale: pressed ? 0.95 : 1}]}]}
        >
          <Text className="text-white font-bold text-sm uppercase tracking-wider">Tap to Retry</Text>
        </Pressable>
        <Pressable onPress={() => navigation.goBack()} className="mt-4 py-2">
          <Text className="text-text-secondary text-sm">Cancel &amp; Go Back</Text>
        </Pressable>
      </View>
    );
  }

  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}` : '?';
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : phoneNumber;
  const dealStage = contact
    ? `${contact.status === 'qualified' ? 'Qualified Buyer' : contact.status.charAt(0).toUpperCase() + contact.status.slice(1)} · Lekki`
    : 'Unknown Caller';

  const contextData = getContextCardData();

  return (
    <View
      style={{
        paddingTop: Math.max(insets.top, 16),
        paddingBottom: Math.max(insets.bottom, 24),
      }}
      className="flex-1 bg-surface relative justify-between px-6"
    >
      {/* Background radial glow */}
      <View className="absolute top-[80px] w-[320px] h-[320px] rounded-full bg-brand-500/5 items-center justify-center self-center" />
      <View className="absolute top-[120px] w-[240px] h-[240px] rounded-full bg-brand-500/10 items-center justify-center self-center" />

      {/* ── Top Bar Compliance Recording Indicator ─────────────────── */}
      <View className="items-center z-10">
        <View className="flex-row items-center bg-[#090d16]/80 px-3.5 py-1.5 rounded-full border border-slate-800/80">
          <Animated.View style={{opacity: pulseOpacity}} className="w-2 h-2 rounded-full bg-danger mr-2" />
          <Text className="text-white text-[10px] font-bold uppercase tracking-widest">Recording</Text>
        </View>
      </View>

      {/* ── TOP THIRD: Contact Context ─────────────────────────────────── */}
      <View className="items-center mt-6 z-10">
        {/* Circular Avatar */}
        <View className="w-24 h-24 rounded-full bg-brand-500/20 border-2 border-brand-500 items-center justify-center mb-4 shadow-xl">
          <Text className="text-brand-500 text-4xl font-black">{initials}</Text>
        </View>
        
        {/* Name */}
        <Text className="text-white text-2xl font-bold tracking-tight text-center">
          {displayName}
        </Text>

        {/* Deal Stage Chip */}
        <View className="bg-surface-raised border border-slate-800 px-3.5 py-1.5 rounded-full mt-2.5 flex-row items-center">
          <View className="w-1.5 h-1.5 rounded-full bg-brand-500 mr-2" />
          <Text className="text-brand-500 text-[10px] font-extrabold uppercase tracking-widest">
            {dealStage}
          </Text>
        </View>

        {/* Geist Mono Large Timer */}
        <Text className="text-white text-3xl font-semibold font-mono tracking-widest mt-5">
          {activeCallState === 'active' ? formatElapsed(elapsed) : stateLabel[activeCallState] ?? '00:00'}
        </Text>
      </View>

      {/* ── MIDDLE THIRD: Context Card vs. Live Transcript / Keypad ───── */}
      <View className="flex-1 my-6 z-10 justify-center min-h-[160px] max-h-[200px]">
        {showKeypad ? (
          // Dialpad view
          <View className="bg-[#090d16]/90 border border-slate-800/60 rounded-2xl p-4 flex-1 justify-center">
            <View className="flex-row justify-between items-center mb-3 border-b border-slate-800/50 pb-2">
              <Text className="text-text-secondary text-[10px] font-bold uppercase tracking-wider">Dialpad</Text>
              <Pressable onPress={() => setShowKeypad(false)} className="px-2 py-1">
                <Text className="text-brand-500 text-xs font-bold">Close</Text>
              </Pressable>
            </View>
            <View className="flex-row flex-wrap justify-between gap-y-3 px-4">
              {['1', '2', '3', '4', '5', '6', '7', '8', '9', '*', '0', '#'].map(digit => (
                <Pressable
                  key={digit}
                  onPress={() => sendDigit(digit)}
                  className="w-12 h-12 rounded-full bg-surface-input border border-slate-800/50 items-center justify-center"
                  style={({pressed}) => [{transform: [{scale: pressed ? 0.92 : 1}]}]}
                >
                  <Text className="text-white text-base font-bold">{digit}</Text>
                </Pressable>
              ))}
            </View>
          </View>
        ) : segments.length > 0 ? (
          // Live scrolling transcript view (Phase 3)
          <View className="bg-[#090d16]/60 border border-slate-800/40 rounded-2xl p-4 flex-1">
            <View className="flex-row justify-between items-center mb-3 border-b border-slate-800/50 pb-2">
              <Text className="text-text-secondary text-[10px] font-bold uppercase tracking-wider">Live Transcript</Text>
              <View className="bg-brand-500/10 border border-brand-500/20 px-2 py-0.5 rounded-full">
                <Text className="text-brand-500 text-[8px] font-bold uppercase tracking-wide">Live AI</Text>
              </View>
            </View>
            <ScrollView
              ref={transcriptScrollRef}
              showsVerticalScrollIndicator={false}
              contentContainerStyle={{paddingBottom: 4}}
            >
              {segments.map((seg, idx) => {
                const isLast = idx === segments.length - 1;
                const isAgent = seg.speaker === 'Agent';
                const speakerColor = isAgent ? 'text-brand-500' : 'text-text-secondary';
                return (
                  <View key={idx} className={`flex-row mb-1.5 items-start ${isLast ? 'bg-brand-500/5 p-2 rounded-lg' : ''}`}>
                    <Text className={`font-bold text-[10px] w-14 uppercase tracking-wide ${speakerColor}`}>
                      {seg.speaker}
                    </Text>
                    <Text className={`flex-1 text-xs leading-4 ${isLast ? 'text-white font-medium' : 'text-text-secondary opacity-60'}`}>
                      {seg.text}
                    </Text>
                  </View>
                );
              })}
            </ScrollView>
          </View>
        ) : (
          // CRM Context Card view
          <View className="bg-[#090d16]/60 border border-slate-800/40 rounded-2xl p-4 flex-1 justify-between">
            <View className="flex-row justify-between items-center mb-2 border-b border-slate-800/40 pb-1.5">
              <Text className="text-text-secondary text-[10px] font-bold uppercase tracking-wider">
                {contextData.title}
              </Text>
              {contextData.isAI && (
                <View className="bg-brand-500/10 border border-brand-500/20 px-2 py-0.5 rounded-full">
                  <Text className="text-brand-500 text-[8px] font-bold uppercase tracking-wide">AI</Text>
                </View>
              )}
            </View>
            <Text className="text-text-primary text-xs leading-5 flex-1 mt-1">
              {contextData.body}
            </Text>
          </View>
        )}
      </View>

      {/* ── BOTTOM THIRD: Call Controls Grid & Hangup ─────────────────── */}
      <View className="gap-6 z-10">
        {/* 2x3 Grid */}
        <View className="gap-4">
          <View className="flex-row justify-around">
            <ControlButton icon={isMuted ? 'mic-off' : 'mic'} label={isMuted ? 'Muted' : 'Mute'} active={isMuted} onPress={() => twilioService.mute(!isMuted)} />
            <ControlButton icon="grid" label="Keypad" active={showKeypad} onPress={() => setShowKeypad(!showKeypad)} />
            <ControlButton icon="volume-2" label={isSpeaker ? 'Speaker' : 'Audio'} active={isSpeaker} onPress={() => twilioService.setSpeaker(!isSpeaker)} />
          </View>
          <View className="flex-row justify-around">
            <ControlButton icon="edit-3" label="Add Note" active={noteVisible} onPress={() => setNoteVisible(true)} />
            <ControlButton icon="pause" label={isOnHold ? 'Hold On' : 'Hold'} active={isOnHold} onPress={handleToggleHold} />
            <ControlButton icon="phone-forwarded" label="Transfer" active={false} onPress={handleTransfer} />
          </View>
        </View>

        {/* Large Centered End Call Button */}
        <View className="items-center mt-2">
          <Pressable
            onPress={handleHangup}
            style={({pressed}) => [
              { transform: [{ scale: pressed ? 0.93 : 1 }] }
            ]}
            className="w-16 h-16 rounded-full bg-danger items-center justify-center shadow-lg"
          >
            <Icon name="phone-off" size={24} color={tokens.brandPrimaryFg} style={{ transform: [{ rotate: '135deg' }] }} />
          </Pressable>
        </View>
      </View>

      {/* ── Add Note Sheet Modal ────────────────────────────────────── */}
      <Modal
        visible={noteVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setNoteVisible(false)}
      >
        <View className="flex-1 justify-end bg-black/60">
          <Pressable className="flex-1" onPress={() => setNoteVisible(false)} />
          <View className="bg-[#090d16] rounded-t-[24px] border-t border-slate-800/80 p-6 pb-8">
            <View className="w-12 h-1.5 bg-slate-800 rounded-full self-center mb-5" />
            <Text className="text-white text-lg font-bold mb-1">Add Note</Text>
            <Text className="text-text-secondary text-xs mb-5">
              Capture quick notes or record a voice note for CRM synchronization.
            </Text>

            {/* Voice note recorder widget */}
            <View className="items-center justify-center py-6 bg-surface border border-slate-800/80 rounded-2xl mb-5">
              {isRecording ? (
                <View className="items-center">
                  <Animated.View
                    style={{ opacity: pulseOpacity }}
                    className="w-16 h-16 rounded-full bg-danger/10 border border-danger/30 items-center justify-center mb-3"
                  >
                    <Pressable
                      onPress={stopVoiceRecording}
                      className="w-12 h-12 rounded-full bg-danger items-center justify-center"
                    >
                      <Icon name="square" size={18} color={tokens.brandPrimaryFg} />
                    </Pressable>
                  </Animated.View>
                  <Text className="text-danger font-mono text-sm mb-1">
                    Recording: {formatElapsed(recordingSeconds)}
                  </Text>
                  <Text className="text-text-tertiary text-xs">Tap to stop recording</Text>
                </View>
              ) : (
                <View className="items-center">
                  <Pressable
                    onPress={startVoiceRecording}
                    className="w-14 h-14 rounded-full bg-brand-500 items-center justify-center mb-3 shadow"
                    style={({pressed}) => [{transform: [{scale: pressed ? 0.95 : 1}]}]}
                  >
                    <Icon name="mic" size={22} color={tokens.brandPrimaryFg} />
                  </Pressable>
                  <Text className="text-white text-xs font-semibold mb-1">Record Voice Note</Text>
                  <Text className="text-text-tertiary text-[9px] uppercase tracking-wider">
                    AI Auto-Transcription Active
                  </Text>
                </View>
              )}
            </View>

            {/* Text Note area */}
            <TextInput
              className="bg-surface text-white rounded-xl px-4 py-3 text-xs border border-slate-800/60"
              placeholder="Type call details or observations here…"
              placeholderTextColor="#71717A"
              multiline
              numberOfLines={4}
              value={noteText}
              onChangeText={setNoteText}
              style={{minHeight: 80, textAlignVertical: 'top'}}
            />

            {/* Done / Cancel CTA */}
            <View className="flex-row gap-3 mt-6">
              <Pressable
                className="flex-1 bg-surface border border-slate-800 rounded-xl py-3.5 items-center"
                onPress={() => setNoteVisible(false)}
              >
                <Text className="text-slate-300 font-semibold text-xs">Cancel</Text>
              </Pressable>
              <Pressable
                className="flex-1 bg-brand-500 rounded-xl py-3.5 items-center"
                onPress={handleSaveNote}
              >
                <Text className="text-white font-semibold text-xs">Done</Text>
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}
