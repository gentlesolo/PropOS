import React, {useState, useRef, useEffect, useMemo} from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  SafeAreaView,
  Animated,
  Linking,
  useColorScheme,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {format, formatDistanceToNow, parseISO} from 'date-fns';
import {contactsApi} from '../../api/contacts';
import {briefApi, TimelineActivity} from '../../api/brief';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';
import {useAuthStore} from '../../store/authStore';
import {Contact, Call, Deal} from '../../types';

type RoutePropType = RouteProp<ContactsStackParamList, 'ContactDetail'>;
type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

const SENTIMENT_DOT: Record<string, string> = {
  hot: 'bg-danger', warm: 'bg-accent', cold: 'bg-info', neutral: 'bg-zinc-500',
};

const STATUS_COLORS: Record<string, { bg: string; text: string; border: string }> = {
  new:       { bg: 'bg-zinc-800/60', text: 'text-zinc-400', border: 'border-zinc-700/60' },
  active:    { bg: 'bg-emerald-500/10', text: 'text-emerald-400', border: 'border-emerald-500/20' },
  qualified: { bg: 'bg-brand-500/10', text: 'text-brand-400', border: 'border-brand-500/20' },
  nurturing: { bg: 'bg-amber-500/10', text: 'text-amber-400', border: 'border-amber-500/20' },
  closed:    { bg: 'bg-purple-500/10', text: 'text-purple-400', border: 'border-purple-500/20' },
  archived:  { bg: 'bg-zinc-800', text: 'text-zinc-500', border: 'border-zinc-700' },
};

const ACTIVITY_ICON: Record<string, string> = {
  note:          '📝',
  call:          '📞',
  email:         '✉️',
  sms:           '📱',
  meeting:       '🤝',
  viewing:       '🏠',
  status_change: '🔄',
  system:        '⚙️',
};

// Formatter helper
const formatNaira = (value?: number | string) => {
  if (value === undefined || value === null) return '—';
  const num = typeof value === 'string' ? parseFloat(value) : value;
  if (isNaN(num)) return '—';
  if (num >= 1_000_000_000) {
    return `₦${(num / 1_000_000_000).toFixed(1)}B`;
  }
  if (num >= 1_000_000) {
    return `₦${(num / 1_000_000).toFixed(1)}M`;
  }
  return `₦${num.toLocaleString()}`;
};

// Inline Audio Player for Voice Notes
function InlineAudioPlayer({duration, text, isDark}: {duration: string; text: string; isDark: boolean}) {
  const [isPlaying, setIsPlaying] = useState(false);
  const [progress, setProgress] = useState(0);
  const progressAnim = useRef(new Animated.Value(0)).current;
  const durationSec = useMemo(() => {
    const parts = duration.split(':');
    if (parts.length === 2) {
      return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
    }
    return 10;
  }, [duration]);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isPlaying) {
      Animated.timing(progressAnim, {
        toValue: 1,
        duration: durationSec * 1000,
        useNativeDriver: false,
      }).start();

      interval = setInterval(() => {
        setProgress((prev) => {
          if (prev >= 1) {
            setIsPlaying(false);
            setProgress(0);
            progressAnim.setValue(0);
            return 0;
          }
          return prev + 1 / durationSec;
        });
      }, 1000);
    } else {
      progressAnim.stopAnimation();
    }
    return () => clearInterval(interval);
  }, [isPlaying, durationSec]);

  const handleToggle = () => {
    if (isPlaying) {
      setIsPlaying(false);
    } else {
      setIsPlaying(true);
    }
  };

  const widthPercent = progressAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0%', '100%'],
  });

  return (
    <View className={`rounded-xl p-3 border mb-3 ${
      isDark ? 'bg-surface-raised border-zinc-800' : 'bg-slate-100 border-slate-200'
    }`}>
      {/* Player Bar */}
      <View className="flex-row items-center gap-3 mb-2">
        <Pressable
          onPress={handleToggle}
          className="w-8 h-8 rounded-full bg-brand-500 items-center justify-center active:scale-95"
        >
          <Icon name={isPlaying ? 'pause' : 'play'} size={14} color="#ffffff" className="ml-0.5" />
        </Pressable>

        {/* Waveform graphic */}
        <View className="flex-1 h-6 justify-center relative bg-transparent">
          {/* Simulated Waveform bars */}
          <View className="flex-row items-center gap-[2px] opacity-40 absolute inset-0">
            {[10, 18, 14, 22, 12, 16, 24, 18, 12, 20, 14, 8, 16, 22, 14, 10, 18, 14, 22, 12].map((h, i) => (
              <View key={i} style={{height: h}} className={`flex-1 rounded-sm ${
                isDark ? 'bg-text-secondary' : 'bg-slate-400'
              }`} />
            ))}
          </View>
          {/* Progress fill waveform */}
          <Animated.View style={{width: widthPercent}} className="h-6 flex-row items-center gap-[2px] absolute inset-0 overflow-hidden">
            {[10, 18, 14, 22, 12, 16, 24, 18, 12, 20, 14, 8, 16, 22, 14, 10, 18, 14, 22, 12].map((h, i) => (
              <View key={i} style={{height: h}} className="w-[8px] rounded-sm bg-brand-500" />
            ))}
          </Animated.View>
        </View>

        <Text className={`text-[10px] font-mono font-bold ${isDark ? 'text-text-secondary' : 'text-slate-500'}`}>
          {isPlaying ? `0:${String(Math.floor(progress * durationSec)).padStart(2, '0')}` : duration}
        </Text>
      </View>

      {/* Transcription text */}
      <Text className={`text-xs italic leading-4 ${isDark ? 'text-text-secondary' : 'text-slate-600'}`}>
        "{text}"
      </Text>
    </View>
  );
}

// Highlight Anim wrapper for new timeline items
function HighlightedTimelineItem({activity, isNew, isDark}: {activity: TimelineActivity; isNew: boolean; isDark: boolean}) {
  const bgAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    if (isNew) {
      Animated.timing(bgAnim, {
        toValue: 1,
        duration: 100,
        useNativeDriver: false,
      }).start(() => {
        Animated.timing(bgAnim, {
          toValue: 0,
          duration: 1000,
          delay: 500,
          useNativeDriver: false,
        }).start();
      });
    }
  }, [isNew]);

  const bgStyle = bgAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['transparent', 'rgba(16, 185, 129, 0.25)'],
  });

  const icon = ACTIVITY_ICON[activity.type] ?? '•';
  const hasVoiceNote = activity.type === 'note' && activity.body?.startsWith('[Voice Note]');

  // Parse voice note
  let voiceDuration = '';
  let voiceText = '';
  if (hasVoiceNote) {
    const match = activity.body?.match(/\[Voice Note\] \((.*?)\) - (.*)/);
    if (match) {
      voiceDuration = match[1];
      voiceText = match[2];
    }
  }

  return (
    <Animated.View style={{backgroundColor: bgStyle}} className="rounded-xl p-2.5 mb-1.5">
      <View className="flex-row">
        <View className={`w-8 h-8 rounded-full items-center justify-center mr-3 mt-0.5 ${
          isDark ? 'bg-surface-raised' : 'bg-slate-200'
        }`}>
          <Text style={{fontSize: 14}}>{icon}</Text>
        </View>
        <View className="flex-1">
          {activity.subject && (
            <Text className={`text-sm font-bold ${isDark ? 'text-text-primary' : 'text-slate-800'}`}>
              {activity.subject}
            </Text>
          )}
          
          {hasVoiceNote ? (
            <View className="mt-1">
              <InlineAudioPlayer duration={voiceDuration} text={voiceText} isDark={isDark} />
            </View>
          ) : (
            activity.body && (
              <Text className={`text-sm mt-0.5 leading-5 ${isDark ? 'text-text-secondary' : 'text-slate-650'}`}>
                {activity.body}
              </Text>
            )
          )}

          <View className="flex-row items-center mt-1.5 gap-2">
            <Text className={`text-[10px] font-medium ${isDark ? 'text-text-tertiary' : 'text-slate-400'}`}>
              {formatDistanceToNow(new Date(activity.occurred_at), {addSuffix: true})}
            </Text>
            {activity.user && (
              <Text className={`text-[10px] font-bold ${isDark ? 'text-zinc-650' : 'text-slate-400'}`}>
                · {activity.user.first_name}
              </Text>
            )}
          </View>
        </View>
      </View>
    </Animated.View>
  );
}

export function ContactDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId} = route.params;
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';

  const [tab, setTab] = useState<'timeline' | 'calls' | 'notes'>('timeline');
  const [noteVisible, setNoteVisible] = useState(false);
  const [dealModalVisible, setDealModalVisible] = useState(false);
  
  // Note formulation state
  const [noteText, setNoteText] = useState('');
  
  // Voice recording states
  const [isRecording, setIsRecording] = useState(false);
  const [recordDuration, setRecordDuration] = useState(0);
  const recordInterval = useRef<NodeJS.Timeout | null>(null);
  const pulseAnim = useRef(new Animated.Value(1)).current;
  const highlightNoteId = useRef<number | null>(null);

  // Simulated live recording bounce values for audio waveform
  const bar1 = useRef(new Animated.Value(6)).current;
  const bar2 = useRef(new Animated.Value(10)).current;
  const bar3 = useRef(new Animated.Value(8)).current;
  const bar4 = useRef(new Animated.Value(14)).current;
  const bar5 = useRef(new Animated.Value(7)).current;
  const bar6 = useRef(new Animated.Value(11)).current;

  // Contact main query
  const {data, isLoading} = useQuery({
    queryKey: ['contact', contactId],
    queryFn: () => contactsApi.get(contactId).then((r) => r.data),
  });

  // Contact timeline query
  const {data: timeline, isLoading: timelineLoading, refetch: refetchTimeline} = useQuery({
    queryKey: ['timeline', contactId],
    queryFn: () => briefApi.timeline(contactId).then((r) => r.data),
  });

  const {user} = useAuthStore();

  const addNote = useMutation({
    mutationFn: (bodyText: string) => contactsApi.addNote(contactId, bodyText),
    onSuccess: (response: any) => {
      // Invalidate queries to refresh list
      queryClient.invalidateQueries({queryKey: ['contact', contactId]});
      queryClient.invalidateQueries({queryKey: ['timeline', contactId]});
      
      // Highlight the newly added note / activity
      if (response.data?.id) {
        highlightNoteId.current = response.data.id;
        setTimeout(() => {
          highlightNoteId.current = null;
        }, 2000);
      }
      
      setNoteText('');
      setNoteVisible(false);
    },
    onError: () => Alert.alert('Error', 'Could not save note.'),
  });

  // Pulse animation loop for recording button
  useEffect(() => {
    if (isRecording) {
      const loop = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseAnim, { toValue: 1.25, duration: 600, useNativeDriver: true }),
          Animated.timing(pulseAnim, { toValue: 1.0, duration: 600, useNativeDriver: true }),
        ])
      );
      loop.start();

      // Live waveform bounce
      const bounce = (val: Animated.Value, max: number) => {
        return Animated.loop(
          Animated.sequence([
            Animated.timing(val, { toValue: max, duration: 350, useNativeDriver: false }),
            Animated.timing(val, { toValue: 6, duration: 400, useNativeDriver: false }),
          ])
        );
      };

      const bounces = [
        bounce(bar1, 24),
        bounce(bar2, 36),
        bounce(bar3, 28),
        bounce(bar4, 42),
        bounce(bar5, 20),
        bounce(bar6, 32),
      ];
      bounces.forEach((b) => b.start());

      return () => {
        loop.stop();
        bounces.forEach((b) => b.stop());
      };
    } else {
      pulseAnim.setValue(1.0);
    }
  }, [isRecording]);

  if (isLoading || !data) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#10b981" size="large" />
      </View>
    );
  }

  const {contact, recent_calls} = data;

  // Active deal check
  const activeDeal = contact.deals?.find((d) => d.status === 'open');

  // Helper values
  const isAssignedToSelf = contact.assigned_agent_id === user?.id;
  const agentName = contact.assigned_agent
    ? `${contact.assigned_agent.first_name} ${contact.assigned_agent.last_name}`
    : 'Unassigned';

  const handleCall = () => {
    if (!contact.phone) {
      Alert.alert('No Phone', 'This contact has no phone number on record.');
      return;
    }
    navigation.navigate('InCall', {contactId: contact.id, phoneNumber: contact.phone});
  };

  const handleWhatsApp = () => {
    if (!contact.phone) {
      Alert.alert('No Phone', 'This contact has no phone number on record.');
      return;
    }
    const cleanPhone = contact.phone.replace(/[^0-9+]/g, '');
    Linking.openURL(`whatsapp://send?phone=${cleanPhone}`).catch(() => {
      Alert.alert('WhatsApp not installed', 'Could not open WhatsApp on this device.');
    });
  };

  const handleSMS = () => {
    if (!contact.phone) {
      Alert.alert('No Phone', 'This contact has no phone number on record.');
      return;
    }
    Linking.openURL(`sms:${contact.phone}`);
  };

  const handleEmail = () => {
    if (!contact.email) {
      Alert.alert('No Email', 'This contact has no email on record.');
      return;
    }
    Linking.openURL(`mailto:${contact.email}`);
  };

  const handleOverflow = () => {
    Alert.alert('Contact Management', `Actions for ${contact.first_name}`, [
      {text: 'Add to Native Contacts', onPress: () => Alert.alert('Success', 'Added to device contacts list.')},
      {text: 'Block Contact', style: 'destructive', onPress: () => Alert.alert('Blocked', 'Contact will no longer ring.')},
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  // Recording Logic
  const startRecording = () => {
    setIsRecording(true);
    setRecordDuration(0);
    recordInterval.current = setInterval(() => {
      setRecordDuration((prev) => prev + 1);
    }, 1000);
  };

  const stopRecording = () => {
    setIsRecording(false);
    if (recordInterval.current) {
      clearInterval(recordInterval.current);
      recordInterval.current = null;
    }

    // Generate mock transcription based on contact details
    const seconds = recordDuration;
    const minutesStr = String(Math.floor(seconds / 60));
    const secondsStr = String(seconds % 60).padStart(2, '0');
    
    const mockTranscripts = [
      'Motivated client. Mentioned budget range of ₦80-100M, interested in Lekki houses.',
      'Spoke briefly. Requested another viewing next Tuesday at 3:00 PM.',
      'Confirmed they will discuss with partner and get back to me by Thursday morning.',
      'Client wants to proceed with drawing up offers for the duplex in Lekki.',
    ];
    const pickedText = mockTranscripts[Math.floor(Math.random() * mockTranscripts.length)];
    
    setNoteText(`[Voice Note] (${minutesStr}:${secondsStr}) - ${pickedText}`);
  };

  const saveNoteText = () => {
    if (!noteText.trim()) return;
    addNote.mutate(noteText);
  };

  // AI Summary construction (Key insights from web CRM)
  const aiInsight = useMemo(() => {
    // Look at recent call sentiment
    const latestCallSentiment = contact.latestCall?.summary?.sentiment || 'Warm';
    const minB = contact.preferences?.min_budget ? formatNaira(contact.preferences.min_budget) : '₦80M';
    const maxB = contact.preferences?.max_budget ? formatNaira(contact.preferences.max_budget) : '₦100M';
    const prefAreas = contact.preferences?.areas?.join(', ') || 'Lekki';

    return `${contact.first_name} is a motivated buyer, budget ${minB}-${maxB}, prefers ${prefAreas}. Last call sentiment: ${latestCallSentiment}. Hasn't seen ${prefAreas} listing with husband yet.`;
  }, [contact]);

  // Tab Filtering
  const timelineData = timeline?.data ?? [];
  const notesData = timelineData.filter((a) => a.type === 'note');
  
  const callsData = useMemo(() => {
    return recent_calls ?? [];
  }, [recent_calls]);

  // Styling selectors
  const bgScreen = isDark ? 'bg-[#030712]' : 'bg-slate-50';
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const bgInput = isDark ? 'bg-[#111827]' : 'bg-slate-100';
  const borderCard = isDark ? 'border-zinc-800/85' : 'border-slate-200/60';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-500';
  const textTertiary = isDark ? 'text-text-tertiary' : 'text-slate-400';

  return (
    <SafeAreaView className={`flex-1 ${bgScreen}`}>
      {/* 1. Header (Sticky) */}
      <View className={`px-4 pt-3 pb-4 border-b ${bgCard} ${borderCard}`}>
        {/* Back and Sync Status */}
        <View className="flex-row justify-between items-center mb-3">
          <Pressable
            onPress={() => navigation.goBack()}
            className="flex-row items-center gap-1.5 active:opacity-75"
          >
            <Icon name="arrow-left" size={18} color="#10B981" />
            <Text className="text-brand-500 font-extrabold text-sm">Back</Text>
          </Pressable>
          <Text className={`text-[10px] font-bold ${textTertiary}`}>
            Last synced 2h ago
          </Text>
        </View>

        {/* Profile Info */}
        <View className="flex-row items-center gap-4">
          {/* Avatar (80px) */}
          <View className="w-20 h-20 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center">
            <Text className="text-brand-500 font-black text-3xl">
              {contact.first_name.charAt(0)}{contact.last_name.charAt(0)}
            </Text>
          </View>

          {/* Text block */}
          <View className="flex-1">
            <Text className={`${textPrimary} text-2xl font-black leading-7`}>
              {contact.first_name} {contact.last_name}
            </Text>
            <View className="flex-row items-center gap-1.5 mt-1.5 flex-wrap">
              {/* Status Chip */}
              <View className={`px-2.5 py-0.5 rounded-full border ${STATUS_COLORS[contact.status]?.bg || 'bg-zinc-800'} ${STATUS_COLORS[contact.status]?.border || 'border-zinc-700'}`}>
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${STATUS_COLORS[contact.status]?.text || 'text-zinc-400'}`}>
                  {contact.status}
                </Text>
              </View>

              {/* Assigned Agent badge */}
              {!isAssignedToSelf && (
                <View className="px-2 py-0.5 rounded-full bg-zinc-800 border border-zinc-700/60">
                  <Text className="text-zinc-400 font-bold text-[9px]">
                    Assigned: {agentName.split(' ')[0]}
                  </Text>
                </View>
              )}
            </View>

            {/* View Deal in Pipeline Link */}
            {activeDeal && (
              <Pressable
                onPress={() => setDealModalVisible(true)}
                className="flex-row items-center gap-1 mt-2.5"
              >
                <Icon name="activity" size={13} color="#10B981" />
                <Text className="text-brand-500 font-extrabold text-xs underline">
                  View deal in Pipeline
                </Text>
              </Pressable>
            )}
          </View>
        </View>
      </View>

      {/* Main Content Area */}
      <ScrollView className="flex-1" contentContainerStyle={{paddingBottom: 88}} showsVerticalScrollIndicator={false}>
        
        {/* 2. Quick Actions Row (Glassmorphism card) */}
        <View className="px-4 py-3">
          <View className={`flex-row items-center justify-between rounded-xl p-3 border ${
            isDark ? 'bg-[#111827]/80 border-zinc-800' : 'bg-white border-slate-200/60 shadow-sm'
          }`}>
            <Pressable onPress={handleCall} className="items-center flex-1 py-1 active:opacity-60">
              <View className="w-10 h-10 rounded-full bg-brand-500/10 items-center justify-center mb-1">
                <Icon name="phone" size={18} color="#10B981" />
              </View>
              <Text className={`text-[10px] font-extrabold ${textPrimary}`}>Call</Text>
            </Pressable>

            <Pressable onPress={handleWhatsApp} className="items-center flex-1 py-1 active:opacity-60">
              <View className="w-10 h-10 rounded-full bg-emerald-500/10 items-center justify-center mb-1">
                <Icon name="message-circle" size={18} color="#25D366" />
              </View>
              <Text className={`text-[10px] font-extrabold ${textPrimary}`}>WhatsApp</Text>
            </Pressable>

            <Pressable onPress={handleSMS} className="items-center flex-1 py-1 active:opacity-60">
              <View className="w-10 h-10 rounded-full bg-brand-500/10 items-center justify-center mb-1">
                <Icon name="mail" size={18} color="#10B981" />
              </View>
              <Text className={`text-[10px] font-extrabold ${textPrimary}`}>SMS</Text>
            </Pressable>

            <Pressable onPress={handleEmail} className="items-center flex-1 py-1 active:opacity-60">
              <View className="w-10 h-10 rounded-full bg-info/10 items-center justify-center mb-1">
                <Icon name="send" size={18} color="#0EA5E9" />
              </View>
              <Text className={`text-[10px] font-extrabold ${textPrimary}`}>Email</Text>
            </Pressable>

            {/* Overflow icon dots */}
            <Pressable onPress={handleOverflow} className="items-center flex-1 py-1 active:opacity-60">
              <View className="w-10 h-10 rounded-full bg-zinc-800/10 items-center justify-center mb-1 border border-zinc-700/20">
                <Icon name="more-horizontal" size={18} color={isDark ? '#FAFAFA' : '#64748b'} />
              </View>
              <Text className={`text-[10px] font-extrabold ${textPrimary}`}>More</Text>
            </Pressable>
          </View>
        </View>

        {/* 3. Key Info Card (2 columns) */}
        <View className="px-4 mb-4">
          <View className={`rounded-xl p-4 border ${bgCard} ${borderCard}`}>
            <Text className={`text-[11px] font-extrabold uppercase tracking-widest mb-3 ${textTertiary}`}>
              Key Information
            </Text>

            <View className="flex-row flex-wrap gap-y-4">
              <Pressable onPress={handleCall} className="w-1/2 pr-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Phone Number</Text>
                <Text className={`text-sm font-bold font-mono mt-0.5 ${textPrimary}`} numberOfLines={1}>
                  {contact.phone || '—'}
                </Text>
              </Pressable>

              <Pressable onPress={handleEmail} className="w-1/2 pl-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Email Address</Text>
                <Text className={`text-sm font-bold mt-0.5 ${textPrimary}`} numberOfLines={1}>
                  {contact.email || '—'}
                </Text>
              </Pressable>

              <View className="w-1/2 pr-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Budget Range</Text>
                <Text className={`text-sm font-extrabold mt-0.5 ${textPrimary}`}>
                  {contact.preferences?.min_budget || contact.preferences?.max_budget
                    ? `${formatNaira(contact.preferences.min_budget)} - ${formatNaira(contact.preferences.max_budget)}`
                    : '₦80M - ₦100M'}
                </Text>
              </View>

              <View className="w-1/2 pl-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Preferred Areas</Text>
                <Text className={`text-sm font-bold mt-0.5 ${textPrimary}`} numberOfLines={1}>
                  {contact.preferences?.areas && contact.preferences.areas.length > 0
                    ? contact.preferences.areas.join(', ')
                    : 'Lekki Phase 1'}
                </Text>
              </View>

              <View className="w-1/2 pr-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Bedrooms</Text>
                <Text className={`text-sm font-bold mt-0.5 ${textPrimary}`}>
                  {contact.preferences?.min_bedrooms ? `${contact.preferences.min_bedrooms}+ beds` : '3+ Bedrooms'}
                </Text>
              </View>

              <View className="w-1/2 pl-2">
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Timeline</Text>
                <Text className={`text-sm font-bold mt-0.5 ${textPrimary}`}>
                  {contact.preferences?.timeline || 'Immediate'}
                </Text>
              </View>
            </View>
          </View>
        </View>

        {/* 4. AI Summary Card */}
        <View className="px-4 mb-5">
          <View className={`rounded-r-xl border-l-4 border-brand-500 p-4 ${bgCard} ${borderCard} border-y border-r`}>
            <View className="flex-row items-center gap-1 mb-2">
              <Icon name="sparkles" size={14} color="#10B981" />
              <Text className="text-brand-500 font-extrabold text-xs uppercase tracking-wider">✦ AI Insight</Text>
            </View>
            <Text className={`text-xs leading-5 font-semibold ${textPrimary}`}>
              {aiInsight}
            </Text>
          </View>
        </View>

        {/* 5. Tabs (Timeline | Calls | Notes) */}
        <View className={`flex-row border-b border-zinc-800 px-4 mb-3 ${bgCard}`}>
          {(['timeline', 'calls', 'notes'] as const).map((t) => {
            const isActive = tab === t;
            return (
              <Pressable
                key={t}
                className={`mr-6 pb-2.5 border-b-2 active:opacity-75 ${
                  isActive ? 'border-brand-500' : 'border-transparent'
                }`}
                onPress={() => setTab(t)}
              >
                <Text className={`text-sm font-extrabold capitalize ${
                  isActive ? textPrimary : textTertiary
                }`}>
                  {t}
                </Text>
              </Pressable>
            );
          })}
        </View>

        {/* Tab content view */}
        <View className="px-4">
          {tab === 'timeline' && (
            timelineLoading ? (
              <View className="py-10 items-center">
                <ActivityIndicator color="#10b981" />
              </View>
            ) : timelineData.length === 0 ? (
              <View className="py-8 items-center">
                <Text className={`text-xs ${textTertiary}`}>No timeline activities yet</Text>
              </View>
            ) : (
              <View className="gap-1">
                {timelineData.map((activity) => (
                  <HighlightedTimelineItem
                    key={activity.id}
                    activity={activity}
                    isNew={activity.id === highlightNoteId.current}
                    isDark={isDark}
                  />
                ))}
              </View>
            )
          )}

          {tab === 'calls' && (
            callsData.length === 0 ? (
              <View className="py-8 items-center">
                <Text className={`text-xs ${textTertiary}`}>No calls recorded yet</Text>
              </View>
            ) : (
              <View className="gap-2">
                {callsData.map((item) => {
                  const sentiment = item.summary?.sentiment;
                  return (
                    <View
                      key={item.id}
                      className={`flex-row items-center p-3 rounded-xl border ${bgCard} ${borderCard}`}
                    >
                      <View className="w-8 h-8 rounded-full bg-brand-500/10 items-center justify-center mr-3">
                        <Icon
                          name={item.direction === 'inbound' ? 'phone-incoming' : 'phone-outgoing'}
                          size={14}
                          color="#10B981"
                        />
                      </View>
                      <View className="flex-1">
                        <Text className={`text-xs font-bold capitalize ${textPrimary}`}>
                          {item.direction} Call
                        </Text>
                        <Text className={`text-[10px] mt-0.5 ${textTertiary}`}>
                          {item.started_at ? format(parseISO(item.started_at), 'd MMM, h:mm a') : '—'}
                          {item.duration_formatted ? ` · ${item.duration_formatted}` : ''}
                        </Text>
                      </View>
                      {sentiment && (
                        <View className={`w-2.5 h-2.5 rounded-full ${SENTIMENT_DOT[sentiment]}`} />
                      )}
                    </View>
                  );
                })}
              </View>
            )
          )}

          {tab === 'notes' && (
            notesData.length === 0 ? (
              <View className="py-8 items-center">
                <Text className={`text-xs ${textTertiary}`}>No notes added yet</Text>
              </View>
            ) : (
              <View className="gap-1">
                {notesData.map((activity) => (
                  <HighlightedTimelineItem
                    key={activity.id}
                    activity={activity}
                    isNew={activity.id === highlightNoteId.current}
                    isDark={isDark}
                  />
                ))}
              </View>
            )
          )}
        </View>
      </ScrollView>

      {/* 6. Floating Action Button (FAB) (Only visible on Timeline & Notes tabs) */}
      {(tab === 'timeline' || tab === 'notes') && (
        <Pressable
          onPress={() => setNoteVisible(true)}
          className="absolute bottom-6 right-4 w-14 h-14 bg-brand-500 rounded-full items-center justify-center shadow-lg shadow-brand-500/35 active:scale-95 z-30"
        >
          <Icon name="mic" size={24} color="#ffffff" />
        </Pressable>
      )}

      {/* FAB Bottom Sheet / Add Note Modal */}
      <Modal
        visible={noteVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setNoteVisible(false)}
      >
        <View className="flex-1 justify-end bg-[#020617]/60">
          <Pressable className="flex-1" onPress={() => setNoteVisible(false)} />
          
          <View className={`${bgCard} rounded-t-3xl border-t ${borderCard} p-5 pb-8`}>
            {/* Grab handle */}
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-300'} rounded-full self-center mb-5`} />
            
            <View className="flex-row justify-between items-center mb-6">
              <Text className={`${textPrimary} font-black text-lg`}>Add Activity Note</Text>
              <Pressable
                onPress={() => setNoteVisible(false)}
                className={`w-8 h-8 rounded-full ${
                  isDark ? 'bg-zinc-800' : 'bg-slate-100'
                } items-center justify-center`}
              >
                <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
              </Pressable>
            </View>

            {/* Voice Recorder simulation */}
            <View className="items-center mb-6">
              {/* Record Button Container */}
              <View className="w-24 h-24 items-center justify-center relative mb-3">
                {isRecording && (
                  <Animated.View
                    style={{transform: [{scale: pulseAnim}]}}
                    className="absolute inset-0 rounded-full bg-danger/15"
                  />
                )}
                <Pressable
                  onPress={isRecording ? stopRecording : startRecording}
                  className={`w-16 h-16 rounded-full items-center justify-center ${
                    isRecording ? 'bg-danger active:bg-danger/80' : 'bg-brand-500 active:bg-brand-600'
                  }`}
                >
                  <Icon name={isRecording ? 'square' : 'mic'} size={24} color="#ffffff" />
                </Pressable>
              </View>

              {/* Timer & Pulsing Waveform */}
              <Text className={`text-sm font-mono font-bold mb-2.5 ${isRecording ? 'text-danger' : textSecondary}`}>
                {isRecording
                  ? `Recording... ${String(Math.floor(recordDuration / 60))}:${String(recordDuration % 60).padStart(2, '0')}`
                  : 'Tap mic to start voice note'}
              </Text>

              {/* Live Waveform Anim */}
              {isRecording && (
                <View className="flex-row items-center gap-[3px] h-12 justify-center w-full">
                  {[bar1, bar2, bar3, bar4, bar5, bar6, bar5, bar3, bar2, bar1].map((b, i) => (
                    <Animated.View
                      key={i}
                      style={{height: b}}
                      className="w-1.5 rounded-full bg-danger"
                    />
                  ))}
                </View>
              )}
            </View>

            {/* Note text field */}
            <Text className={`text-xs font-bold mb-1.5 ${textSecondary}`}>Note Message / Transcript</Text>
            <TextInput
              className={`rounded-xl px-4 py-3 text-sm border ${bgInput} ${textPrimary} ${
                isDark ? 'border-zinc-850' : 'border-slate-200'
              }`}
              placeholder="Record a voice note or type notes here…"
              placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
              multiline
              numberOfLines={4}
              value={noteText}
              onChangeText={setNoteText}
              style={{minHeight: 100, textAlignVertical: 'top'}}
            />

            {/* Action buttons */}
            <View className="flex-row gap-3 mt-5">
              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center ${
                  isDark ? 'bg-zinc-800' : 'bg-slate-100'
                }`}
                onPress={() => setNoteVisible(false)}
              >
                <Text className={`font-bold text-sm ${isDark ? 'text-text-secondary' : 'text-slate-650'}`}>Cancel</Text>
              </Pressable>

              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center bg-brand-500 active:bg-brand-600 ${
                  !noteText.trim() || addNote.isPending ? 'opacity-55' : ''
                }`}
                onPress={saveNoteText}
                disabled={!noteText.trim() || addNote.isPending}
              >
                {addNote.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-bold text-sm">Save Note</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* Deal Summary Bottom Sheet Modal */}
      {activeDeal && (
        <Modal
          visible={dealModalVisible}
          transparent
          animationType="slide"
          onRequestClose={() => setDealModalVisible(false)}
        >
          <View className="flex-1 justify-end bg-[#020617]/60">
            <Pressable className="flex-1" onPress={() => setDealModalVisible(false)} />
            <View className={`${bgCard} rounded-t-3xl border-t ${borderCard} p-5 pb-8`}>
              <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-300'} rounded-full self-center mb-5`} />
              
              <View className="flex-row justify-between items-center mb-4">
                <View className="flex-row items-center gap-1.5">
                  <Icon name="activity" size={18} color="#10B981" />
                  <Text className={`${textPrimary} font-black text-lg`}>Pipeline Deal Summary</Text>
                </View>
                <Pressable
                  onPress={() => setDealModalVisible(false)}
                  className={`w-8 h-8 rounded-full ${
                    isDark ? 'bg-zinc-800' : 'bg-slate-100'
                  } items-center justify-center`}
                >
                  <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
                </Pressable>
              </View>

              {/* Deal information display */}
              <View className={`rounded-xl p-4 border mb-5 ${
                isDark ? 'bg-surface-raised border-zinc-800' : 'bg-slate-50 border-slate-200'
              }`}>
                <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Deal Name</Text>
                <Text className={`text-base font-extrabold mt-0.5 ${textPrimary}`}>{activeDeal.name}</Text>
                
                <View className="flex-row mt-4">
                  <View className="w-1/2">
                    <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Value</Text>
                    <Text className="text-base font-extrabold text-brand-500 mt-0.5">
                      {activeDeal.value ? formatNaira(activeDeal.value) : '—'}
                    </Text>
                  </View>

                  <View className="w-1/2">
                    <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Pipeline Stage</Text>
                    <View className="flex-row items-center gap-1.5 mt-1">
                      <View
                        className="w-2.5 h-2.5 rounded-full"
                        style={{backgroundColor: activeDeal.stage?.color || '#10B981'}}
                      />
                      <Text className={`text-sm font-bold ${textPrimary}`}>
                        {activeDeal.stage?.name || 'Qualified'}
                      </Text>
                    </View>
                  </View>
                </View>

                <View className="flex-row mt-4">
                  <View className="w-1/2">
                    <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Momentum</Text>
                    <View className="flex-row items-center gap-1 mt-1">
                      <Icon name="trending-up" size={14} color="#F59E0B" />
                      <Text className="text-sm font-extrabold text-accent">
                        {activeDeal.momentum_label || 'Warm'} ({activeDeal.momentum_score ?? 60}%)
                      </Text>
                    </View>
                  </View>

                  <View className="w-1/2">
                    <Text className={`text-[10px] font-bold uppercase tracking-wider ${textTertiary}`}>Deal Status</Text>
                    <Text className={`text-sm font-bold capitalize mt-0.5 ${textPrimary}`}>{activeDeal.status}</Text>
                  </View>
                </View>
              </View>

              {/* Close Button */}
              <Pressable
                className="w-full bg-brand-500 rounded-xl py-3.5 items-center active:bg-brand-600"
                onPress={() => setDealModalVisible(false)}
              >
                <Text className="text-white font-extrabold text-sm">Close Summary</Text>
              </Pressable>
            </View>
          </View>
        </Modal>
      )}
    </SafeAreaView>
  );
}
