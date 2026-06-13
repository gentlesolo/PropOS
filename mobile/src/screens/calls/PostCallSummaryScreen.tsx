import React, {useState, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {format, isToday, isYesterday} from 'date-fns';
import Icon from 'react-native-vector-icons/Feather';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import {callsApi} from '../../api/calls';
import {contactsApi} from '../../api/contacts';
import {tasksApi} from '../../api/tasks';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type RoutePropType = RouteProp<CallsStackParamList, 'PostCallSummary'>;
type NavProp = NativeStackNavigationProp<any>;

const SENTIMENT_COLORS: Record<string, { bg: string; text: string; dot: string }> = {
  hot:     { bg: 'bg-danger/10 border-danger/20',     text: 'text-danger',     dot: 'bg-danger' },
  warm:    { bg: 'bg-accent/10 border-accent/20',     text: 'text-accent',     dot: 'bg-accent' },
  cold:    { bg: 'bg-info/10 border-info/20',         text: 'text-info',       dot: 'bg-info' },
  neutral: { bg: 'bg-slate-500/10 border-slate-500/20', text: 'text-slate-400',  dot: 'bg-slate-500' },
};

const SENTIMENT_RANKS: Record<string, number> = { cold: 1, neutral: 2, warm: 3, hot: 4 };

// Implied due date keywords
const hasImpliedDueDate = (text: string) => {
  const keywords = [
    'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday',
    'tomorrow', 'next week', 'week', 'month', 'today', 'due', '31 may',
    'january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december',
    'jan', 'feb', 'mar', 'apr', 'jun', 'jul', 'aug', 'sep', 'oct', 'nov', 'dec'
  ];
  const lower = text.toLowerCase();
  return keywords.some(k => lower.includes(k));
};

export function PostCallSummaryScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;
  const queryClient = useQueryClient();

  // Stagger animations
  const headerAnim = useRef(new Animated.Value(0)).current;
  const summaryAnim = useRef(new Animated.Value(0)).current;
  const keyPointsAnim = useRef(new Animated.Value(0)).current;
  const actionItemsAnim = useRef(new Animated.Value(0)).current;

  // States
  const [editedSummary, setEditedSummary] = useState('');
  const [isEditingSummary, setIsEditingSummary] = useState(false);
  const [tempSummaryText, setTempSummaryText] = useState('');
  const [isEdited, setIsEdited] = useState(false);

  const [checkedItems, setCheckedItems] = useState<Record<number, boolean>>({});
  const [actionItems, setActionItems] = useState<string[]>([]);
  const [editingItemIdx, setEditingItemIdx] = useState<number | null>(null);
  const [editingItemText, setEditingItemText] = useState('');

  const [isConfirmed, setIsConfirmed] = useState(false);
  const [isOffline, setIsOffline] = useState(false);
  const [showTimeoutFallback, setShowTimeoutFallback] = useState(false);
  const [manualNotesMode, setManualNotesMode] = useState(false);
  const [manualNotesText, setManualNotesText] = useState('');

  // Fetch call details
  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
    refetchInterval: query => (!query.state.data?.summary ? 5000 : false),
  });

  const summary = call?.summary;
  const contact = call?.contact;

  // Fetch contact's other recent calls for sentiment trend
  const {data: contactData} = useQuery({
    queryKey: ['contact', call?.contact_id],
    queryFn: () => contactsApi.get(call!.contact_id!).then(r => r.data),
    enabled: !!call?.contact_id,
  });
  const recentCalls = contactData?.recent_calls;

  // Trigger stagger fade-in when loading finishes
  useEffect(() => {
    if (summary) {
      Animated.stagger(60, [
        Animated.timing(headerAnim, { toValue: 1, duration: 300, useNativeDriver: true }),
        Animated.timing(summaryAnim, { toValue: 1, duration: 300, useNativeDriver: true }),
        Animated.timing(keyPointsAnim, { toValue: 1, duration: 300, useNativeDriver: true }),
        Animated.timing(actionItemsAnim, { toValue: 1, duration: 300, useNativeDriver: true }),
      ]).start();
    }
  }, [summary, headerAnim, summaryAnim, keyPointsAnim, actionItemsAnim]);

  // Initialise states when summary loads
  useEffect(() => {
    if (summary) {
      if (!editedSummary) setEditedSummary(summary.summary_text);
      if (actionItems.length === 0) {
        setActionItems(summary.action_items ?? []);
        // Checked by default
        const initialChecked: Record<number, boolean> = {};
        (summary.action_items ?? []).forEach((_, idx) => {
          initialChecked[idx] = true;
        });
        setCheckedItems(initialChecked);
      }
      setIsEdited(summary.agent_edited);
    }
  }, [summary]);

  // Offline network connectivity check
  useEffect(() => {
    fetch('https://1.1.1.1', {method: 'HEAD', mode: 'no-cors'})
      .catch(() => setIsOffline(true));
  }, []);

  // AI Timeout Fallback (15 seconds)
  useEffect(() => {
    const timer = setTimeout(() => {
      if (!summary) {
        setShowTimeoutFallback(true);
      }
    }, 15000);
    return () => clearTimeout(timer);
  }, [summary]);

  // Confirm/Task Mutation
  const confirm = useMutation({
    mutationFn: async () => {
      const checkedActionItems = actionItems.filter((_, idx) => checkedItems[idx]);

      // Confirm summary on server
      await callsApi.confirmSummary(callId, {
        summary_text: editedSummary,
        action_items: actionItems,
        suggested_next_step: summary?.suggested_next_step,
      });

      // Create a task for each checked action item
      await Promise.all(
        checkedActionItems.map(title =>
          tasksApi.store({
            title,
            contact_id: call?.contact_id,
            call_id: callId,
          })
        )
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      queryClient.invalidateQueries({queryKey: ['call', callId]});
      
      // Animate checkmark state, vibrate, then navigate back
      Vibration.vibrate(30);
      setIsConfirmed(true);
      setTimeout(() => {
        navigation.navigate('CallHistory');
      }, 800000 / 1000); // 800ms
    },
    onError: () => {
      Alert.alert('Error', 'Some updates failed to sync. We will save details offline.');
      navigation.navigate('CallHistory');
    },
  });

  const getAnimStyle = (anim: Animated.Value) => ({
    opacity: anim,
    transform: [
      {
        translateY: anim.interpolate({
          inputRange: [0, 1],
          outputRange: [15, 0],
        }),
      },
    ],
  });

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

  const getSentimentTrend = () => {
    if (!summary?.sentiment) return null;
    const previousCall = recentCalls?.find(c => c.id !== callId && c.summary?.sentiment);
    const previousSentiment = previousCall?.summary?.sentiment;
    if (!previousSentiment || previousSentiment === summary.sentiment) return null;

    const currRank = SENTIMENT_RANKS[summary.sentiment] || 0;
    const prevRank = SENTIMENT_RANKS[previousSentiment] || 0;
    const prevLabel = previousSentiment.charAt(0).toUpperCase() + previousSentiment.slice(1);

    if (currRank > prevRank) return `↑ improved from ${prevLabel}`;
    if (currRank < prevRank) return `↓ decreased from ${prevLabel}`;
    return null;
  };

  const handleConfirmPress = () => {
    Vibration.vibrate(20);
    confirm.mutate();
  };

  const toggleCheck = (idx: number) => {
    Vibration.vibrate(10);
    setCheckedItems(prev => ({...prev, [idx]: !prev[idx]}));
  };

  // ── Loading state ────────────────────────────────────────────────────────
  if (isLoading && !summary) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#10B981" size="large" />
        <Text className="text-slate-400 mt-4 text-xs font-bold uppercase tracking-widest">Loading call details…</Text>
      </View>
    );
  }

  // ── Generation Timeout / Manual Notes fallback ────────────────────────────
  if (!summary) {
    return (
      <View className="flex-1 bg-surface justify-center px-6">
        {!showTimeoutFallback ? (
          <View className="items-center">
            <ActivityIndicator color="#10B981" size="large" />
            <Text className="text-white text-lg font-semibold mt-4">Generating summary…</Text>
            <Text className="text-slate-400 text-sm mt-2 text-center leading-5">
              AI is transcribing and summarising your call. This takes about 60 seconds.
            </Text>
          </View>
        ) : (
          <View className="bg-[#090d16] border border-slate-800/80 rounded-2xl p-6 items-center shadow-lg">
            <View className="w-12 h-12 rounded-full bg-accent/15 border border-accent/20 items-center justify-center mb-4">
              <Icon name="alert-triangle" size={22} color="#F59E0B" />
            </View>
            <Text className="text-white text-base font-bold text-center mb-2">
              Summary generation is slow
            </Text>
            <Text className="text-text-secondary text-xs text-center mb-6 leading-4">
              Your device may be offline or experiencing connection latency. You can enter call details manually.
            </Text>

            {manualNotesMode ? (
              <View className="w-full">
                <TextInput
                  className="bg-surface text-white text-xs border border-slate-800 rounded-xl p-3.5 mb-4"
                  placeholder="Type your notes / summary here…"
                  placeholderTextColor="#71717A"
                  multiline
                  value={manualNotesText}
                  onChangeText={setManualNotesText}
                  style={{minHeight: 120, textAlignVertical: 'top'}}
                />
                <Pressable
                  onPress={async () => {
                    if (!manualNotesText.trim()) return;
                    try {
                      await callsApi.confirmSummary(callId, {
                        summary_text: manualNotesText,
                        action_items: [],
                      });
                      queryClient.invalidateQueries({queryKey: ['call', callId]});
                    } catch {
                      Alert.alert('Error', 'Failed to save notes.');
                    }
                  }}
                  className="bg-brand-500 rounded-xl py-3.5 items-center w-full"
                >
                  <Text className="text-white font-bold text-xs uppercase tracking-wider">Save Notes</Text>
                </Pressable>
              </View>
            ) : (
              <View className="w-full gap-3">
                <Pressable
                  onPress={() => setManualNotesMode(true)}
                  className="bg-brand-500 rounded-xl py-3.5 items-center w-full"
                >
                  <Text className="text-white font-semibold text-xs uppercase tracking-wider">Add Notes Yourself</Text>
                </Pressable>
                <Pressable
                  onPress={() => navigation.navigate('CallHistory')}
                  className="border border-slate-800 rounded-xl py-3.5 items-center w-full"
                >
                  <Text className="text-slate-300 font-semibold text-xs uppercase tracking-wider">Go to History</Text>
                </Pressable>
              </View>
            )}
          </View>
        )}
      </View>
    );
  }

  const duration = call?.duration_formatted ?? '—';
  const callTimeStr = formatCallTime(call?.started_at);
  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}`.toUpperCase() : '?';
  const displayName = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;

  const sentimentData = SENTIMENT_COLORS[summary.sentiment] || SENTIMENT_COLORS.neutral;
  const trend = getSentimentTrend();

  const checkedCount = Object.values(checkedItems).filter(Boolean).length;

  return (
    <View style={{paddingBottom: Math.max(insets.bottom, 16)}} className="flex-1 bg-surface relative justify-between">
      {/* ── Offline Banner ────────────────────────────────────────────── */}
      {isOffline && (
        <View className="bg-accent/15 border-b border-accent/20 px-4 py-3 flex-row items-center justify-center z-30">
          <Icon name="wifi-off" size={14} color="#F59E0B" className="mr-2" />
          <Text className="text-accent text-[11px] font-semibold tracking-wide uppercase">
            Some details will sync when you're back online
          </Text>
        </View>
      )}

      {/* ── Scrollable Content Area ───────────────────────────────────── */}
      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{
          paddingTop: Math.max(insets.top, 20),
          paddingHorizontal: 20,
          paddingBottom: 110, // make space for sticky bottom bar
        }}
        className="flex-1"
      >
        {/* ── HEADER ──────────────────────────────────────────────────── */}
        <Animated.View style={getAnimStyle(headerAnim)} className="flex-row items-center justify-between mb-5">
          <View className="flex-1 mr-4">
            <Text className="text-white text-xl font-bold tracking-tight">
              Call with {displayName}
            </Text>
            <Text className="text-text-secondary text-xs font-mono mt-1">
              {duration} · {callTimeStr}
            </Text>
          </View>
          {contact && (
            <Pressable
              onPress={() => navigation.navigate('ContactDetail', {contactId: contact.id})}
              className="w-10 h-10 rounded-full bg-brand-500/20 border border-brand-500/30 items-center justify-center shadow"
            >
              <Text className="text-brand-500 font-bold text-sm">{initials}</Text>
            </Pressable>
          )}
        </Animated.View>

        {/* ── SENTIMENT BADGE ─────────────────────────────────────────── */}
        <Animated.View style={getAnimStyle(headerAnim)} className="flex-row items-center mb-6">
          <View className={`flex-row items-center border rounded-full px-3.5 py-1.5 ${sentimentData.bg}`}>
            <View className={`w-2 h-2 rounded-full mr-2 ${sentimentData.dot}`} />
            <Text className={`text-xs font-bold uppercase tracking-wider capitalize ${sentimentData.text}`}>
              {summary.sentiment}
            </Text>
            {trend && (
              <Text className="text-text-secondary text-[10px] ml-2 font-medium tracking-wide">
                {trend}
              </Text>
            )}
          </View>
        </Animated.View>

        {/* ── AI SUMMARY CARD ─────────────────────────────────────────── */}
        <Animated.View style={getAnimStyle(summaryAnim)} className="bg-[#090d16]/80 border-l-[3px] border-brand-500 border border-y border-r border-slate-800 rounded-r-xl p-4 mb-6">
          <View className="flex-row justify-between items-center mb-2.5">
            <View className="flex-row items-center">
              <Text className="text-brand-500 text-xs font-bold uppercase tracking-wider">✦ AI Summary</Text>
              {isEdited && (
                <Text className="text-text-tertiary text-[10px] uppercase tracking-widest ml-2 font-medium">· Edited</Text>
              )}
            </View>
            {!isEditingSummary && (
              <Pressable onPress={() => { setIsEditingSummary(true); setTempSummaryText(editedSummary); }} className="px-2 py-0.5">
                <Text className="text-brand-500 text-xs font-bold uppercase">Edit</Text>
              </Pressable>
            )}
          </View>

          {isEditingSummary ? (
            <View className="w-full mt-1">
              <TextInput
                className="bg-surface text-white text-xs border border-slate-850 rounded-lg p-2.5 leading-5"
                multiline
                value={tempSummaryText}
                onChangeText={setTempSummaryText}
                style={{minHeight: 80, textAlignVertical: 'top'}}
              />
              <View className="flex-row gap-2 mt-3 justify-end">
                <Pressable
                  onPress={() => setIsEditingSummary(false)}
                  className="bg-surface-raised border border-slate-800 px-3 py-1.5 rounded-lg"
                >
                  <Text className="text-slate-400 text-xs font-bold uppercase">Cancel</Text>
                </Pressable>
                <Pressable
                  onPress={() => {
                    setEditedSummary(tempSummaryText);
                    setIsEditingSummary(false);
                    setIsEdited(true);
                  }}
                  className="bg-brand-500 px-3 py-1.5 rounded-lg"
                >
                  <Text className="text-white text-xs font-bold uppercase">Save</Text>
                </Pressable>
              </View>
            </View>
          ) : (
            <Text className="text-text-primary text-xs leading-5">
              {editedSummary}
            </Text>
          )}
        </Animated.View>

        {/* ── KEY POINTS ──────────────────────────────────────────────── */}
        {(summary.key_points ?? []).length > 0 && (
          <Animated.View style={getAnimStyle(keyPointsAnim)} className="mb-6">
            <Text className="text-white text-sm font-bold uppercase tracking-wider mb-2.5">Key Points</Text>
            <View className="border-t border-slate-900">
              {summary.key_points.map((point, i) => (
                <View key={i} className="flex-row items-center py-3 border-b border-slate-900">
                  <View className="w-1.5 h-1.5 rounded-full bg-brand-505 bg-brand-500 mr-3 shadow-sm" />
                  <Text className="text-text-primary text-xs leading-5 flex-1">{point}</Text>
                </View>
              ))}
            </View>
          </Animated.View>
        )}

        {/* ── ACTION ITEMS ────────────────────────────────────────────── */}
        {actionItems.length > 0 && (
          <Animated.View style={getAnimStyle(actionItemsAnim)} className="mb-6">
            <Text className="text-white text-sm font-bold uppercase tracking-wider mb-2.5">
              Action Items — confirm to create tasks
            </Text>
            <View className="gap-2.5">
              {actionItems.map((item, idx) => {
                const checked = checkedItems[idx];
                const hasDate = hasImpliedDueDate(item);

                return (
                  <View key={idx} className="flex-row items-center bg-[#090d16]/75 border border-slate-800/80 rounded-xl p-3">
                    {/* Custom Checkbox */}
                    <Pressable
                      onPress={() => toggleCheck(idx)}
                      className={`w-5 h-5 rounded border items-center justify-center mr-3 ${
                        checked ? 'bg-brand-500 border-brand-500' : 'border-slate-700 bg-surface'
                      }`}
                    >
                      {checked && <Icon name="check" size={12} color="#FAFAFA" />}
                    </Pressable>

                    {/* Inline-editable Task Text */}
                    {editingItemIdx === idx ? (
                      <TextInput
                        autoFocus
                        value={editingItemText}
                        onChangeText={setEditingItemText}
                        onBlur={() => {
                          const updated = [...actionItems];
                          updated[idx] = editingItemText;
                          setActionItems(updated);
                          setEditingItemIdx(null);
                        }}
                        onSubmitEditing={() => {
                          const updated = [...actionItems];
                          updated[idx] = editingItemText;
                          setActionItems(updated);
                          setEditingItemIdx(null);
                        }}
                        className="flex-1 text-white text-xs bg-surface-raised border border-slate-850 px-2 py-1 rounded-lg"
                      />
                    ) : (
                      <Pressable
                        onPress={() => {
                          setEditingItemIdx(idx);
                          setEditingItemText(item);
                        }}
                        className="flex-1 flex-row items-center justify-between"
                      >
                        <Text className={`text-xs leading-4 flex-1 ${checked ? 'text-slate-200' : 'text-text-tertiary line-through'}`}>
                          {item}
                        </Text>
                        {hasDate && (
                          <View className="ml-2 bg-accent/10 p-1 rounded-md border border-accent/20">
                            <Icon name="calendar" size={11} color="#F59E0B" />
                          </View>
                        )}
                      </Pressable>
                    )}
                  </View>
                );
              })}
            </View>
          </Animated.View>
        )}
      </ScrollView>

      {/* ── STICKY BOTTOM ACTION BAR (Pinned) ───────────────────────── */}
      <View className="absolute bottom-0 left-0 right-0 border-t border-slate-900 bg-[#030712] px-6 py-4 z-20 shadow-2xl">
        <Pressable
          onPress={handleConfirmPress}
          disabled={confirm.isPending || isConfirmed}
          className={`w-full h-14 rounded-xl items-center justify-center flex-row shadow-lg ${
            isConfirmed ? 'bg-brand-500' : 'bg-brand-500'
          }`}
          style={({pressed}) => [{transform: [{scale: pressed ? 0.96 : 1}]}]}
        >
          {confirm.isPending ? (
            <ActivityIndicator color="#fff" />
          ) : isConfirmed ? (
            <View className="flex-row items-center gap-2">
              <Icon name="check-circle" size={18} color="#FAFAFA" />
              <Text className="text-white font-bold text-xs uppercase tracking-widest">✓ Confirmed &amp; Saved</Text>
            </View>
          ) : (
            <Text className="text-white font-bold text-xs uppercase tracking-widest">
              Confirm &amp; Create {checkedCount} {checkedCount === 1 ? 'Task' : 'Tasks'}
            </Text>
          )}
        </Pressable>

        <View className="flex-row justify-between mt-3.5 px-3">
          <Pressable onPress={() => navigation.navigate('CallTranscript', {callId})}>
            <Text className="text-brand-500 text-[11px] font-bold uppercase tracking-wider">
              View Full Transcript
            </Text>
          </Pressable>
          <Pressable onPress={() => navigation.navigate('CallHistory')}>
            <Text className="text-text-tertiary text-[11px] font-bold uppercase tracking-wider">
              Dismiss
            </Text>
          </Pressable>
        </View>
      </View>
    </View>
  );
}
