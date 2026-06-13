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
import {tasksApi} from '../../api/tasks';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';
import {useTheme} from '../../theme/ThemeProvider';

type RoutePropType = RouteProp<CallsStackParamList, 'CallDetail'>;
type NavProp = NativeStackNavigationProp<any>;

const SENTIMENT_COLORS: Record<string, {bg: string; text: string; dotColor: string}> = {
  hot:     {bg: '#F43F5E1A', text: '#F43F5E', dotColor: '#F43F5E'},
  warm:    {bg: '#F59E0B1A', text: '#F59E0B', dotColor: '#F59E0B'},
  cold:    {bg: '#0EA5E91A', text: '#0EA5E9', dotColor: '#0EA5E9'},
  neutral: {bg: '#64748B1A', text: '#94A3B8', dotColor: '#64748B'},
};

const WAVEFORM_BARS = [
  15, 25, 12, 35, 45, 20, 15, 30, 25, 42, 28, 14, 22, 38, 48, 30,
  24, 18, 35, 40, 28, 30, 15, 20, 12, 26, 34, 44, 32, 22, 16, 28,
];

function pad(n: number) { return String(n).padStart(2, '0'); }
function formatTime(seconds: number) { return `${Math.floor(seconds / 60)}:${pad(Math.floor(seconds % 60))}`; }

export function CallDetailScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;

  const [isPlaying, setIsPlaying] = useState(false);
  const [currentSeconds, setCurrentSeconds] = useState(0);
  const [speed, setSpeed] = useState<1 | 1.5 | 2>(1);

  const playTimer = useRef<ReturnType<typeof setInterval> | null>(null);

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
  });

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
          if (next >= totalDuration) { setIsPlaying(false); return totalDuration; }
          return next;
        });
      }, 500);
    }
    return () => { if (playTimer.current) clearInterval(playTimer.current); };
  }, [isPlaying, speed, totalDuration]);

  if (isLoading || !call) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
      </View>
    );
  }

  const {summary, contact} = call;
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;
  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}`.toUpperCase() : '?';

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
    } catch { return 'Today, 10:14am'; }
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
      await Share.share({message: shareText, title: `Call Summary - ${displayName}`});
    } catch (e) { console.warn('Share error', e); }
  };

  const handlePlayPause = () => { Vibration.vibrate(15); setIsPlaying(!isPlaying); };
  const handleSpeedToggle = () => { Vibration.vibrate(10); setSpeed(prev => (prev === 1 ? 1.5 : prev === 1.5 ? 2 : 1)); };

  const sentimentData = summary ? (SENTIMENT_COLORS[summary.sentiment] || SENTIMENT_COLORS.neutral) : SENTIMENT_COLORS.neutral;
  const progress = currentSeconds / totalDuration;

  return (
    <View
      style={{
        paddingTop: Math.max(insets.top, 16),
        paddingBottom: Math.max(insets.bottom, 16),
        flex: 1,
        backgroundColor: tokens.surfacePage,
      }}
    >
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 24,
          paddingBottom: 16,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
        }}
      >
        <Pressable
          onPress={() => navigation.goBack()}
          style={{
            width: 40,
            height: 40,
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: tokens.surfaceRaised,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 20,
          }}
        >
          <Icon name="arrow-left" size={18} color={tokens.textPrimary} />
        </Pressable>
        <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1}}>
          Call Details
        </Text>
        <Pressable
          onPress={handleShare}
          style={{
            width: 40,
            height: 40,
            alignItems: 'center',
            justifyContent: 'center',
            backgroundColor: tokens.surfaceRaised,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 20,
          }}
        >
          <Icon name="share-2" size={18} color={tokens.textPrimary} />
        </Pressable>
      </View>

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{paddingHorizontal: 20, paddingTop: 20, paddingBottom: 30}}
        style={{flex: 1}}
      >
        {/* Contact banner */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'space-between',
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginBottom: 24,
          }}
        >
          <View style={{flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 16}}>
            <View
              style={{
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: `${tokens.brandPrimary}1A`,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}33`,
                alignItems: 'center',
                justifyContent: 'center',
                marginRight: 14,
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 14}}>{initials}</Text>
            </View>
            <View style={{flex: 1}}>
              <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', lineHeight: 20}}>{displayName}</Text>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontFamily: 'monospace', marginTop: 2}}>
                {formatCallTime(call.started_at)}
              </Text>
            </View>
          </View>
          <View style={{alignItems: 'flex-end', gap: 6}}>
            {summary?.sentiment && (
              <View
                style={{
                  paddingHorizontal: 10,
                  paddingVertical: 4,
                  borderRadius: 999,
                  backgroundColor: sentimentData.bg,
                  borderWidth: 1,
                  borderColor: `${sentimentData.dotColor}33`,
                }}
              >
                <Text style={{color: sentimentData.text, fontSize: 9, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8}}>
                  {summary.sentiment}
                </Text>
              </View>
            )}
            {hasLinkedTasks && (
              <View
                style={{
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  borderWidth: 1,
                  borderColor: `${tokens.brandPrimary}33`,
                  paddingHorizontal: 8,
                  paddingVertical: 2,
                  borderRadius: 999,
                  flexDirection: 'row',
                  alignItems: 'center',
                  gap: 4,
                }}
              >
                <Icon name="check-circle" size={9} color={tokens.brandPrimary} />
                <Text style={{color: tokens.brandPrimary, fontSize: 8, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5}}>
                  Tasks Created
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* Unconfirmed tasks warning */}
        {hasUnconfirmedTasks && (
          <View
            style={{
              backgroundColor: '#F59E0B1A',
              borderWidth: 1,
              borderColor: '#F59E0B33',
              borderRadius: 16,
              padding: 16,
              marginBottom: 24,
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
            }}
          >
            <View style={{flex: 1, marginRight: 12}}>
              <Text style={{color: '#F59E0B', fontSize: 10, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1, marginBottom: 4}}>
                Unsaved Tasks
              </Text>
              <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 16}}>
                {summary?.action_items?.length} action items were not added as tasks.
              </Text>
            </View>
            <Pressable
              onPress={() => { Vibration.vibrate(15); navigation.navigate('PostCallSummary', {callId}); }}
              style={({pressed}) => [{
                backgroundColor: '#F59E0B',
                borderRadius: 12,
                paddingHorizontal: 16,
                paddingVertical: 8,
                transform: [{scale: pressed ? 0.95 : 1}],
              }]}
            >
              <Text style={{color: '#FFFFFF', fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8}}>
                Review Now
              </Text>
            </Pressable>
          </View>
        )}

        {/* Audio player */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginBottom: 24,
          }}
        >
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
            <Pressable
              onPress={handlePlayPause}
              style={({pressed}) => [{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: tokens.brandPrimary,
                alignItems: 'center',
                justifyContent: 'center',
                transform: [{scale: pressed ? 0.95 : 1}],
              }]}
            >
              <Icon name={isPlaying ? 'pause' : 'play'} size={16} color="#FAFAFA" style={!isPlaying ? {marginLeft: 2} : {}} />
            </Pressable>

            {/* Waveform */}
            <View style={{flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', height: 32, gap: 3}}>
              {WAVEFORM_BARS.map((height, i) => {
                const isFinished = (i / WAVEFORM_BARS.length) <= progress;
                return (
                  <View
                    key={i}
                    style={{
                      height: height - 10,
                      flex: 1,
                      borderRadius: 999,
                      backgroundColor: isFinished ? tokens.brandPrimary : tokens.borderStrong,
                    }}
                  />
                );
              })}
            </View>

            <View style={{alignItems: 'flex-end', gap: 2, minWidth: 45}}>
              <Text style={{color: tokens.textPrimary, fontSize: 10, fontFamily: 'monospace', fontWeight: '600'}}>
                {formatTime(currentSeconds)}
              </Text>
              <Pressable
                onPress={handleSpeedToggle}
                style={{
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  paddingHorizontal: 6,
                  paddingVertical: 2,
                  borderRadius: 4,
                }}
              >
                <Text style={{color: tokens.brandPrimary, fontSize: 9, fontWeight: '700'}}>{speed}x</Text>
              </Pressable>
            </View>
          </View>
        </View>

        {/* AI Summary */}
        {summary && (
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderLeftWidth: 3,
              borderLeftColor: tokens.brandPrimary,
              borderTopWidth: 1,
              borderBottomWidth: 1,
              borderRightWidth: 1,
              borderTopColor: tokens.borderDefault,
              borderBottomColor: tokens.borderDefault,
              borderRightColor: tokens.borderDefault,
              borderRadius: 8,
              borderTopLeftRadius: 0,
              borderBottomLeftRadius: 0,
              padding: 16,
              marginBottom: 24,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8, marginBottom: 8}}>
              ✦ AI Summary
            </Text>
            <Text style={{color: tokens.textPrimary, fontSize: 12, lineHeight: 20}}>
              {summary.summary_text}
            </Text>
          </View>
        )}

        {/* Key Points */}
        {summary && (summary.key_points ?? []).length > 0 && (
          <View style={{marginBottom: 24}}>
            <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8, marginBottom: 10}}>
              Key Points
            </Text>
            <View style={{borderTopWidth: 1, borderTopColor: tokens.borderSubtle}}>
              {summary.key_points.map((point, i) => (
                <View key={i} style={{flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle}}>
                  <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: tokens.brandPrimary, marginRight: 12}} />
                  <Text style={{color: tokens.textPrimary, fontSize: 12, lineHeight: 20, flex: 1}}>{point}</Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* Action Items */}
        {summary && (summary.action_items ?? []).length > 0 && (
          <View style={{marginBottom: 24}}>
            <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8, marginBottom: 10}}>
              Action Items
            </Text>
            <View style={{gap: 10}}>
              {summary.action_items.map((item, i) => (
                <View
                  key={i}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 12,
                    padding: 12,
                  }}
                >
                  <View
                    style={{
                      width: 20,
                      height: 20,
                      borderRadius: 4,
                      borderWidth: 1,
                      alignItems: 'center',
                      justifyContent: 'center',
                      marginRight: 12,
                      backgroundColor: hasLinkedTasks ? tokens.brandPrimary : tokens.surfaceInput,
                      borderColor: hasLinkedTasks ? tokens.brandPrimary : tokens.borderDefault,
                    }}
                  >
                    {hasLinkedTasks && <Icon name="check" size={12} color="#FAFAFA" />}
                  </View>
                  <Text style={{fontSize: 12, lineHeight: 16, flex: 1, color: hasLinkedTasks ? tokens.textPrimary : tokens.textTertiary}}>
                    {item}
                  </Text>
                </View>
              ))}
            </View>
          </View>
        )}

        {/* View transcript */}
        <Pressable
          onPress={() => { Vibration.vibrate(10); navigation.navigate('CallTranscript', {callId}); }}
          style={({pressed}) => [{
            backgroundColor: tokens.surfaceRaised,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 12,
            paddingVertical: 16,
            alignItems: 'center',
            width: '100%',
            transform: [{scale: pressed ? 0.98 : 1}],
          }]}
        >
          <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 12, textTransform: 'uppercase', letterSpacing: 2}}>
            View Full Transcript
          </Text>
        </Pressable>
      </ScrollView>
    </View>
  );
}
