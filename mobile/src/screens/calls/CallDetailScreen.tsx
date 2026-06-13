import React, {useState, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Pressable,
  ScrollView,
  Text,
  View,
  Share,
  Vibration,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {format, isToday, isYesterday} from 'date-fns';
import {callsApi} from '../../api/calls';
import {contactsApi} from '../../api/contacts';
import {tasksApi} from '../../api/tasks';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type RoutePropType = RouteProp<CallsStackParamList, 'CallDetail'>;
type NavProp = NativeStackNavigationProp<any>;

const SENTIMENT_COLORS: Record<string, { bg: string; text: string; dot: string }> = {
  hot:     { bg: 'bg-danger/10 border-danger/20',     text: 'text-danger',     dot: 'bg-danger' },
  warm:    { bg: 'bg-accent/10 border-accent/20',     text: 'text-accent',     dot: 'bg-accent' },
  cold:    { bg: 'bg-info/10 border-info/20',         text: 'text-info',       dot: 'bg-info' },
  neutral: { bg: 'bg-slate-500/10 border-slate-500/20', text: 'text-slate-400',  dot: 'bg-slate-500' },
};

const WAVEFORM_BARS = [
  15, 25, 12, 35, 45, 20, 15, 30, 25, 42, 28, 14, 22, 38, 48, 30,
  24, 18, 35, 40, 28, 30, 15, 20, 12, 26, 34, 44, 32, 22, 16, 28
];

function pad(n: number) {
  return String(n).padStart(2, '0');
}

function formatTime(seconds: number) {
  const mins = Math.floor(seconds / 60);
  const secs = Math.floor(seconds % 60);
  return `${mins}:${pad(secs)}`;
}

export function CallDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;

  // Player state
  const [isPlaying, setIsPlaying] = useState(false);
  const [currentSeconds, setCurrentSeconds] = useState(0);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);

  const playTimer = useRef<ReturnType<typeof setInterval> | null>(null);

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
  });

  // Fetch tasks to check if tasks were confirmed
  const {data: tasksData} = useQuery({
    queryKey: ['tasks'],
    queryFn: () => tasksApi.list().then(r => r.data),
  });

  const totalDuration = call?.duration_seconds || 165;

  useEffect(() => {
    if (isPlaying) {
      playTimer.current = setInterval(() => {
        setCurrentSeconds(sec => {
          const next = sec + 0.5 * speed;
          if (next >= totalDuration) {
            setIsPlaying(false);
            return totalDuration;
          }
          return next;
        });
      }, 500);
    }
    return () => {
      if (playTimer.current) clearInterval(playTimer.current);
    };
  }, [isPlaying, speed, totalDuration]);

  if (isLoading || !call) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#10B981" size="large" />
      </View>
    );
  }

  const {summary, contact} = call;
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;
  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}`.toUpperCase() : '?';

  // Check if action items were confirmed (linked tasks exist)
  const linkedTasks = ((tasksData as any)?.data || (tasksData as any) || []).filter((t: any) => t.call_id === callId);
  const hasLinkedTasks = linkedTasks.length > 0;
  const hasUnconfirmedTasks = !hasLinkedTasks && (summary?.action_items ?? []).length > 0;

  const formatCallTime = (startedAt?: string) => {
    if (!startedAt) return 'Today, 10:14am';
    try {
      const date = new Date(startedAt);
      const timeStr = format(date, 'h:mma').toLowerCase();
      if (isToday(date)) return `Today, ${timeStr}`;
      if (isYesterday(date)) return `Yesterday, ${timeStr}`;
      return `${format(date, 'MMM d')}, ${timeStr}`;
    } catch {
      return 'Today, 10:14am';
    }
  };

  const handleShare = async () => {
    Vibration.vibrate(15);
    if (!summary) return;
    const shareText = `PropOS Call Summary with ${displayName} (${formatCallTime(call.started_at)})
Duration: ${call.duration_formatted}
Sentiment: ${summary.sentiment.toUpperCase()}

AI Summary:
${summary.summary_text}

Key Points:
${(summary.key_points ?? []).map(p => `• ${p}`).join('\n')}

Action Items:
${(summary.action_items ?? []).map(a => `- ${a}`).join('\n')}`;

    try {
      await Share.share({
        message: shareText,
        title: `Call Summary - ${displayName}`,
      });
    } catch (e) {
      console.warn('Share error', e);
    }
  };

  const handlePlayPause = () => {
    Vibration.vibrate(15);
    setIsPlaying(!isPlaying);
  };

  const handleSpeedToggle = () => {
    Vibration.vibrate(10);
    setSpeed(prev => (prev === 1 ? 1.5 : prev === 1.5 ? 2 : 1));
  };

  const sentimentData = summary ? (SENTIMENT_COLORS[summary.sentiment] || SENTIMENT_COLORS.neutral) : SENTIMENT_COLORS.neutral;
  const progress = currentSeconds / totalDuration;

  return (
    <View style={{paddingTop: Math.max(insets.top, 16), paddingBottom: Math.max(insets.bottom, 16)}} className="flex-1 bg-surface">
      
      {/* ── HEADER ────────────────────────────────────────────────────── */}
      <View className="px-6 pb-4 border-b border-slate-900 flex-row items-center justify-between">
        <Pressable onPress={() => navigation.goBack()} className="w-10 h-10 items-center justify-center bg-surface-raised border border-slate-800 rounded-full">
          <Icon name="arrow-left" size={18} color="#FAFAFA" />
        </Pressable>

        <Text className="text-white text-base font-bold uppercase tracking-wider">Call Details</Text>

        <Pressable onPress={handleShare} className="w-10 h-10 items-center justify-center bg-surface-raised border border-slate-800 rounded-full">
          <Icon name="share-2" size={18} color="#FAFAFA" />
        </Pressable>
      </View>

      <ScrollView showsVerticalScrollIndicator={false} contentContainerStyle={{paddingHorizontal: 20, paddingTop: 20, paddingBottom: 30}} className="flex-1">
        
        {/* ── CONTACT PROFILE BANNER ─────────────────────────────────── */}
        <View className="flex-row items-center justify-between bg-[#090d16]/60 border border-slate-805 border-slate-800/80 rounded-2xl p-4 mb-6">
          <View className="flex-row items-center flex-1 mr-4">
            <View className="w-12 h-12 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center mr-3.5">
              <Text className="text-brand-500 font-bold text-sm">{initials}</Text>
            </View>
            <View className="flex-1">
              <Text className="text-white text-base font-bold leading-5">{displayName}</Text>
              <Text className="text-text-secondary text-[11px] font-mono mt-0.5">{formatCallTime(call.started_at)}</Text>
            </View>
          </View>

          {/* Badges container */}
          <View className="items-end gap-1.5">
            {summary?.sentiment && (
              <View className={`px-2.5 py-1 rounded-full border ${sentimentData.bg}`}>
                <Text className={`text-[9px] font-bold uppercase tracking-wider capitalize ${sentimentData.text}`}>
                  {summary.sentiment}
                </Text>
              </View>
            )}
            {hasLinkedTasks && (
              <View className="bg-brand-500/10 border border-brand-500/20 px-2 py-0.5 rounded-full flex-row items-center">
                <Icon name="check-circle" size={9} color="#10B981" className="mr-1" />
                <Text className="text-brand-500 text-[8px] font-bold uppercase tracking-wider">Tasks Created</Text>
              </View>
            )}
          </View>
        </View>

        {/* ── UNCONFIRMED TASKS WARNING BANNER ────────────────────────── */}
        {hasUnconfirmedTasks && (
          <View className="bg-accent/15 border border-accent/20 rounded-2xl p-4 mb-6 flex-row items-center justify-between shadow-sm">
            <View className="flex-1 mr-3">
              <Text className="text-accent text-[10px] font-bold uppercase tracking-wider mb-1">Unsaved Tasks</Text>
              <Text className="text-slate-300 text-xs leading-4">
                {summary?.action_items?.length} action items were not added as tasks.
              </Text>
            </View>
            <Pressable
              onPress={() => {
                Vibration.vibrate(15);
                navigation.navigate('PostCallSummary', {callId});
              }}
              className="bg-accent rounded-xl px-4 py-2 shadow-sm"
              style={({pressed}) => [{transform: [{scale: pressed ? 0.95 : 1}]}]}
            >
              <Text className="text-white text-xs font-bold uppercase tracking-wider">Review Now</Text>
            </Pressable>
          </View>
        )}

        {/* ── AUDIO PLAYER widget ─────────────────────────────────────── */}
        <View className="bg-[#090d16]/85 border border-slate-800/80 rounded-2xl p-4 mb-6">
          <View className="flex-row items-center gap-3">
            <Pressable
              onPress={handlePlayPause}
              className="w-10 h-10 rounded-full bg-brand-500 items-center justify-center shadow"
              style={({pressed}) => [{transform: [{scale: pressed ? 0.95 : 1}]}]}
            >
              <Icon name={isPlaying ? 'pause' : 'play'} size={16} color="#FAFAFA" style={!isPlaying ? {marginLeft: 2} : {}} />
            </Pressable>

            {/* Static Waveform */}
            <View className="flex-1 flex-row items-center justify-between h-8 gap-[3px]">
              {WAVEFORM_BARS.map((height, i) => {
                const isFinished = (i / WAVEFORM_BARS.length) <= progress;
                return (
                  <View
                    key={i}
                    style={{height: height - 10}}
                    className={`flex-1 rounded-full ${isFinished ? 'bg-brand-500' : 'bg-slate-800'}`}
                  />
                );
              })}
            </View>

            <View className="items-end gap-0.5 min-w-[45px]">
              <Text className="text-text-primary text-[10px] font-mono font-semibold">
                {formatTime(currentSeconds)}
              </Text>
              <Pressable onPress={handleSpeedToggle} className="bg-surface-raised border border-slate-850 px-1.5 py-0.2 rounded">
                <Text className="text-brand-500 text-[9px] font-bold">{speed}x</Text>
              </Pressable>
            </View>
          </View>
        </View>

        {/* ── AI SUMMARY CARD ─────────────────────────────────────────── */}
        {summary && (
          <View className="bg-[#090d16]/60 border-l-[3px] border-brand-500 border border-y border-r border-slate-800/60 rounded-r-2xl p-4 mb-6">
            <Text className="text-brand-500 text-xs font-bold uppercase tracking-wider mb-2">✦ AI Summary</Text>
            <Text className="text-text-primary text-xs leading-5">
              {summary.summary_text}
            </Text>
          </View>
        )}

        {/* ── KEY POINTS ──────────────────────────────────────────────── */}
        {summary && (summary.key_points ?? []).length > 0 && (
          <View className="mb-6">
            <Text className="text-white text-sm font-bold uppercase tracking-wider mb-2.5">Key Points</Text>
            <View className="border-t border-slate-900">
              {summary.key_points.map((point, i) => (
                <View key={i} className="flex-row items-center py-3 border-b border-slate-900">
                  <View className="w-1.5 h-1.5 rounded-full bg-brand-500 mr-3" />
                  <Text className="text-text-primary text-xs leading-5 flex-1">{point}</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* ── ACTION ITEMS LIST ────────────────────────────────────────── */}
        {summary && (summary.action_items ?? []).length > 0 && (
          <View className="mb-6">
            <Text className="text-white text-sm font-bold uppercase tracking-wider mb-2.5">Action Items</Text>
            <View className="gap-2.5">
              {summary.action_items.map((item, i) => {
                const confirmed = hasLinkedTasks; // if tasks already exist
                return (
                  <View key={i} className="flex-row items-center bg-[#090d16]/75 border border-slate-800/80 rounded-xl p-3">
                    <View className={`w-5 h-5 rounded border items-center justify-center mr-3 ${
                      confirmed ? 'bg-brand-500 border-brand-500' : 'border-slate-700 bg-surface'
                    }`}>
                      {confirmed && <Icon name="check" size={12} color="#FAFAFA" />}
                    </View>
                    <Text className={`text-xs leading-4 flex-1 ${confirmed ? 'text-slate-200' : 'text-text-tertiary'}`}>
                      {item}
                    </Text>
                  </View>
                );
              })}
            </View>
          </View>
        )}

        {/* View transcript shortcut button */}
        <Pressable
          onPress={() => { Vibration.vibrate(10); navigation.navigate('CallTranscript', {callId}); }}
          className="bg-surface-raised border border-slate-800 rounded-xl py-4 items-center w-full"
          style={({pressed}) => [{transform: [{scale: pressed ? 0.98 : 1}]}]}
        >
          <Text className="text-brand-500 font-bold text-xs uppercase tracking-widest">View Full Transcript</Text>
        </Pressable>

      </ScrollView>
    </View>
  );
}
