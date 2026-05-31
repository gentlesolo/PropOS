import React, {useCallback, useEffect, useRef, useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  ScrollView,
  Text,
  View,
} from 'react-native';
import {useNavigation, useRoute, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useCallStore} from '../../store/callStore';
import {twilioService} from '../../services/twilioService';
import {liveTranscriptService, TranscriptSegment} from '../../services/liveTranscriptService';
import {callsApi} from '../../api/calls';
import {intelligenceApi, InCallHints} from '../../api/intelligence';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type InCallRouteProp  = RouteProp<CallsStackParamList, 'InCall'>;
type NavProp          = NativeStackNavigationProp<CallsStackParamList>;

function pad(n: number) { return String(n).padStart(2, '0'); }
function formatElapsed(s: number) { return `${pad(Math.floor(s / 60))}:${pad(s % 60)}`; }

const SPEAKER_COLOR: Record<string, string> = {
  Agent:   'text-brand-400',
  Contact: 'text-amber-400',
};

// ── Hints panel ─────────────────────────────────────────────────────────────
function HintsPanel({hints, loading}: {hints: InCallHints | null; loading: boolean}) {
  if (loading) {
    return (
      <View className="bg-slate-900/90 rounded-xl p-3 mx-4 mb-2 flex-row items-center gap-2">
        <ActivityIndicator size="small" color="#3b82f6" />
        <Text className="text-slate-400 text-xs">Analysing conversation…</Text>
      </View>
    );
  }
  if (!hints || (!hints.objection_detected && !hints.suggested_response && hints.talking_points.length === 0)) {
    return null;
  }
  return (
    <View className="bg-slate-900/95 border border-brand-800 rounded-xl p-3 mx-4 mb-2">
      <Text className="text-brand-400 text-xs font-semibold uppercase tracking-wide mb-2">
        🤖 AI Coach
      </Text>
      {hints.objection_detected && (
        <View className="mb-2">
          <Text className="text-amber-400 text-xs font-semibold">Objection detected:</Text>
          <Text className="text-slate-200 text-xs mt-0.5">{hints.objection_detected}</Text>
        </View>
      )}
      {hints.suggested_response && (
        <View className="mb-2 bg-brand-900/60 rounded-lg p-2">
          <Text className="text-brand-300 text-xs font-semibold">Suggested response:</Text>
          <Text className="text-white text-xs mt-0.5 leading-4">{hints.suggested_response}</Text>
        </View>
      )}
      {hints.talking_points.length > 0 && (
        <View>
          <Text className="text-slate-400 text-xs font-semibold mb-1">Bring up next:</Text>
          {hints.talking_points.map((pt, i) => (
            <Text key={i} className="text-slate-300 text-xs">• {pt}</Text>
          ))}
        </View>
      )}
      {hints.warning && (
        <Text className="text-red-400 text-xs mt-2">⚠️ {hints.warning}</Text>
      )}
      {hints.urgency_signal && (
        <Text className="text-green-400 text-xs mt-1">🔥 High intent — consider closing now</Text>
      )}
    </View>
  );
}

// ── Transcript panel ─────────────────────────────────────────────────────────
function TranscriptPanel({segments}: {segments: TranscriptSegment[]}) {
  const ref = useRef<ScrollView>(null);
  if (segments.length === 0) return null;
  return (
    <View className="mx-4 mb-2 bg-slate-900/80 rounded-xl overflow-hidden" style={{maxHeight: 140}}>
      <ScrollView
        ref={ref}
        onContentSizeChange={() => ref.current?.scrollToEnd({animated: true})}
        contentContainerClassName="p-3 gap-1.5">
        {segments.filter(s => s.is_final).map((seg, i) => (
          <View key={i} className="flex-row gap-1.5">
            <Text className={`text-xs font-semibold w-12 ${SPEAKER_COLOR[seg.speaker] ?? 'text-slate-400'}`}>
              {seg.speaker}
            </Text>
            <Text className="text-slate-200 text-xs flex-1 leading-4">{seg.text}</Text>
          </View>
        ))}
      </ScrollView>
    </View>
  );
}

// ── Main screen ──────────────────────────────────────────────────────────────
export function InCallScreen() {
  const route      = useRoute<InCallRouteProp>();
  const navigation = useNavigation<NavProp>();
  const {phoneNumber, contactId, callSid: initialSid} = route.params;

  const {activeCallState, isMuted, isSpeaker, startTime} = useCallStore();

  const [elapsed, setElapsed]         = useState(0);
  const [callId, setCallId]           = useState<number | null>(null);
  const [segments, setSegments]       = useState<TranscriptSegment[]>([]);
  const [hints, setHints]             = useState<InCallHints | null>(null);
  const [hintsLoading, setHintsLoading] = useState(false);
  const [showTranscript, setShowTranscript] = useState(true);

  const timer       = useRef<ReturnType<typeof setInterval> | null>(null);
  const hintsTimer  = useRef<ReturnType<typeof setInterval> | null>(null);
  const unsubscribe = useRef<(() => void) | null>(null);
  const transcriptRef = useRef<TranscriptSegment[]>([]);

  // Keep ref in sync so the hints interval can read current segments
  useEffect(() => { transcriptRef.current = segments; }, [segments]);

  // Start outbound call on mount
  useEffect(() => {
    if (initialSid) return;
    twilioService
      .makeCall(phoneNumber, contactId)
      .then(sid =>
        callsApi.store({
          contact_id: contactId,
          remote_number: phoneNumber,
          provider_call_sid: sid,
        }),
      )
      .then(({data}) => setCallId(data.id))
      .catch(() => {
        Alert.alert('Call failed', 'Could not connect. Please try again.');
        navigation.goBack();
      });
  }, []); // eslint-disable-line react-hooks/exhaustive-deps

  // Elapsed timer
  useEffect(() => {
    if (activeCallState === 'active') {
      timer.current = setInterval(() => setElapsed(e => e + 1), 1000);
    }
    return () => { if (timer.current) clearInterval(timer.current); };
  }, [activeCallState]);

  // Live transcript subscription once we have a callId and call is active
  useEffect(() => {
    if (!callId || activeCallState !== 'active') return;

    liveTranscriptService
      .subscribe(callId, seg => {
        setSegments(prev => {
          // Replace last interim segment or append
          if (!seg.is_final && prev.length > 0 && !prev[prev.length - 1].is_final) {
            return [...prev.slice(0, -1), seg];
          }
          return [...prev, seg];
        });
      })
      .then(unsub => { unsubscribe.current = unsub; })
      .catch(console.warn);

    return () => {
      unsubscribe.current?.();
      unsubscribe.current = null;
    };
  }, [callId, activeCallState]);

  // AI hints — poll every 30 s once there's enough transcript
  useEffect(() => {
    if (!callId || activeCallState !== 'active') return;

    hintsTimer.current = setInterval(async () => {
      const finalSegs = transcriptRef.current.filter(s => s.is_final);
      if (finalSegs.length < 3) return;                      // not enough context yet

      const text = finalSegs.map(s => `${s.speaker}: ${s.text}`).join('\n');
      setHintsLoading(true);
      try {
        const {data} = await intelligenceApi.getHints(callId, text);
        setHints(data);
      } catch {
        // hints are non-critical — fail silently
      } finally {
        setHintsLoading(false);
      }
    }, 30_000);

    return () => { if (hintsTimer.current) clearInterval(hintsTimer.current); };
  }, [callId, activeCallState]);

  // Navigate to summary when call ends
  useEffect(() => {
    if (activeCallState === 'idle' && callId) {
      unsubscribe.current?.();
      navigation.replace('PostCallSummary', {callId});
    }
  }, [activeCallState, callId, navigation]);

  const handleHangup = useCallback(() => {
    twilioService.hangup();
    if (callId) callsApi.updateStatus(callId, 'completed', elapsed);
  }, [callId, elapsed]);

  const stateLabel: Record<string, string> = {
    connecting: 'Connecting…',
    ringing:    'Ringing…',
    active:     formatElapsed(elapsed),
    ending:     'Ending…',
    idle:       'Ended',
  };

  return (
    <View className="flex-1 bg-surface">

      {/* Contact header */}
      <View className="items-center pt-16 pb-4">
        <View className="w-20 h-20 rounded-full bg-brand-700 items-center justify-center mb-3">
          <Text className="text-white text-4xl font-bold">
            {phoneNumber.charAt(0)}
          </Text>
        </View>
        <Text className="text-white text-2xl font-semibold">{phoneNumber}</Text>
        <Text className="text-slate-400 text-base mt-1">
          {stateLabel[activeCallState] ?? ''}
        </Text>
      </View>

      {/* Hints */}
      <HintsPanel hints={hints} loading={hintsLoading} />

      {/* Live transcript */}
      {showTranscript && <TranscriptPanel segments={segments} />}

      {/* Transcript toggle */}
      {segments.length > 0 && (
        <Pressable
          className="mx-4 mb-2"
          onPress={() => setShowTranscript(v => !v)}>
          <Text className="text-brand-500 text-xs text-center">
            {showTranscript ? 'Hide transcript' : `Show transcript (${segments.filter(s => s.is_final).length} segments)`}
          </Text>
        </Pressable>
      )}

      <View className="flex-1" />

      {/* Call controls */}
      <View className="px-6 pb-12 gap-6">
        <View className="flex-row justify-around">
          <CallButton label={isMuted ? 'Unmute' : 'Mute'} active={isMuted}
            onPress={() => twilioService.mute(!isMuted)} />
          <CallButton label={isSpeaker ? 'Earpiece' : 'Speaker'} active={isSpeaker}
            onPress={() => twilioService.setSpeaker(!isSpeaker)} />
          {callId && (
            <CallButton label="Flag" active={false}
              onPress={() => {
                intelligenceApi.flagCall(callId).catch(console.warn);
                Alert.alert('Flagged', 'This call has been flagged for manager review.');
              }} />
          )}
        </View>

        <Pressable
          className="bg-red-600 rounded-full py-5 items-center"
          onPress={handleHangup}>
          <Text className="text-white font-semibold text-lg">End Call</Text>
        </Pressable>
      </View>
    </View>
  );
}

function CallButton({label, active, onPress}: {
  label: string; active: boolean; onPress: () => void;
}) {
  return (
    <Pressable
      className={`w-20 h-20 rounded-full items-center justify-center ${
        active ? 'bg-brand-600' : 'bg-surface-card'
      }`}
      onPress={onPress}>
      <Text className="text-white text-xs text-center leading-4">{label}</Text>
    </Pressable>
  );
}
