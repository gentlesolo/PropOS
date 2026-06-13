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
import {useTheme} from '../../theme/ThemeProvider';

type RoutePropType = RouteProp<CallsStackParamList, 'PostCallSummary'>;
type NavProp = NativeStackNavigationProp<any>;

const SENTIMENT_COLORS: Record<string, {bg: string; text: string; dotColor: string}> = {
  hot:     {bg: '#F43F5E1A', text: '#F43F5E', dotColor: '#F43F5E'},
  warm:    {bg: '#F59E0B1A', text: '#F59E0B', dotColor: '#F59E0B'},
  cold:    {bg: '#0EA5E91A', text: '#0EA5E9', dotColor: '#0EA5E9'},
  neutral: {bg: '#64748B1A', text: '#94A3B8', dotColor: '#64748B'},
};

const SENTIMENT_RANKS: Record<string, number> = {cold: 1, neutral: 2, warm: 3, hot: 4};

const hasImpliedDueDate = (text: string) => {
  const keywords = [
    'monday','tuesday','wednesday','thursday','friday','saturday','sunday',
    'tomorrow','next week','week','month','today','due',
    'january','february','march','april','may','june','july','august','september','october','november','december',
  ];
  const lower = text.toLowerCase();
  return keywords.some(k => lower.includes(k));
};

export function PostCallSummaryScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();
  const {callId} = route.params;
  const queryClient = useQueryClient();

  const headerAnim = useRef(new Animated.Value(0)).current;
  const summaryAnim = useRef(new Animated.Value(0)).current;
  const keyPointsAnim = useRef(new Animated.Value(0)).current;
  const actionItemsAnim = useRef(new Animated.Value(0)).current;

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

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
    refetchInterval: query => (!query.state.data?.summary ? 5000 : false),
  });

  const summary = call?.summary;
  const contact = call?.contact;

  const {data: contactData} = useQuery({
    queryKey: ['contact', call?.contact_id],
    queryFn: () => contactsApi.get(call!.contact_id!).then(r => r.data),
    enabled: !!call?.contact_id,
  });
  const recentCalls = contactData?.recent_calls;

  useEffect(() => {
    if (summary) {
      Animated.stagger(60, [
        Animated.timing(headerAnim, {toValue: 1, duration: 300, useNativeDriver: true}),
        Animated.timing(summaryAnim, {toValue: 1, duration: 300, useNativeDriver: true}),
        Animated.timing(keyPointsAnim, {toValue: 1, duration: 300, useNativeDriver: true}),
        Animated.timing(actionItemsAnim, {toValue: 1, duration: 300, useNativeDriver: true}),
      ]).start();
    }
  }, [summary]);

  useEffect(() => {
    if (summary) {
      if (!editedSummary) setEditedSummary(summary.summary_text);
      if (actionItems.length === 0) {
        setActionItems(summary.action_items ?? []);
        const initialChecked: Record<number, boolean> = {};
        (summary.action_items ?? []).forEach((_, idx) => { initialChecked[idx] = true; });
        setCheckedItems(initialChecked);
      }
      setIsEdited(summary.agent_edited);
    }
  }, [summary]);

  useEffect(() => {
    fetch('https://1.1.1.1', {method: 'HEAD', mode: 'no-cors'}).catch(() => setIsOffline(true));
  }, []);

  useEffect(() => {
    const timer = setTimeout(() => { if (!summary) setShowTimeoutFallback(true); }, 15000);
    return () => clearTimeout(timer);
  }, [summary]);

  const confirm = useMutation({
    mutationFn: async () => {
      const checkedActionItems = actionItems.filter((_, idx) => checkedItems[idx]);
      await callsApi.confirmSummary(callId, {
        summary_text: editedSummary,
        action_items: actionItems,
        suggested_next_step: summary?.suggested_next_step,
      });
      await Promise.all(
        checkedActionItems.map(title =>
          tasksApi.store({title, contact_id: call?.contact_id, call_id: callId})
        )
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      queryClient.invalidateQueries({queryKey: ['call', callId]});
      Vibration.vibrate(30);
      setIsConfirmed(true);
      setTimeout(() => navigation.navigate('CallHistory'), 800);
    },
    onError: () => {
      Alert.alert('Error', 'Some updates failed to sync. We will save details offline.');
      navigation.navigate('CallHistory');
    },
  });

  const getAnimStyle = (anim: Animated.Value) => ({
    opacity: anim,
    transform: [{translateY: anim.interpolate({inputRange: [0, 1], outputRange: [15, 0]})}],
  });

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

  const handleConfirmPress = () => { Vibration.vibrate(20); confirm.mutate(); };
  const toggleCheck = (idx: number) => { Vibration.vibrate(10); setCheckedItems(prev => ({...prev, [idx]: !prev[idx]})); };

  // Loading state
  if (isLoading && !summary) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
        <Text style={{color: tokens.textTertiary, marginTop: 16, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 2}}>
          Loading call details…
        </Text>
      </View>
    );
  }

  // No summary yet
  if (!summary) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, justifyContent: 'center', paddingHorizontal: 24}}>
        {!showTimeoutFallback ? (
          <View style={{alignItems: 'center'}}>
            <ActivityIndicator color={tokens.brandPrimary} size="large" />
            <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '600', marginTop: 16}}>
              Generating summary…
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 14, marginTop: 8, textAlign: 'center', lineHeight: 20}}>
              AI is transcribing and summarising your call. This takes about 60 seconds.
            </Text>
          </View>
        ) : (
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 16,
              padding: 24,
              alignItems: 'center',
              ...tokens.shadowMd,
            }}
          >
            <View
              style={{
                width: 48,
                height: 48,
                borderRadius: 24,
                backgroundColor: '#F59E0B1A',
                borderWidth: 1,
                borderColor: '#F59E0B33',
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 16,
              }}
            >
              <Icon name="alert-triangle" size={22} color="#F59E0B" />
            </View>
            <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', textAlign: 'center', marginBottom: 8}}>
              Summary generation is slow
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center', marginBottom: 24, lineHeight: 16}}>
              Your device may be offline or experiencing connection latency. You can enter call details manually.
            </Text>

            {manualNotesMode ? (
              <View style={{width: '100%'}}>
                <TextInput
                  style={{
                    backgroundColor: tokens.surfaceInput,
                    color: tokens.textPrimary,
                    fontSize: 12,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 12,
                    padding: 14,
                    marginBottom: 16,
                    minHeight: 120,
                    textAlignVertical: 'top',
                  }}
                  placeholder="Type your notes / summary here…"
                  placeholderTextColor={tokens.textTertiary}
                  multiline
                  value={manualNotesText}
                  onChangeText={setManualNotesText}
                />
                <Pressable
                  onPress={async () => {
                    if (!manualNotesText.trim()) return;
                    try {
                      await callsApi.confirmSummary(callId, {summary_text: manualNotesText, action_items: []});
                      queryClient.invalidateQueries({queryKey: ['call', callId]});
                    } catch { Alert.alert('Error', 'Failed to save notes.'); }
                  }}
                  className="bg-brand-500 rounded-xl py-3.5 items-center w-full"
                >
                  <Text style={{color: tokens.textInverse, fontWeight: '700', fontSize: 12, textTransform: 'uppercase', letterSpacing: 1}}>
                    Save Notes
                  </Text>
                </Pressable>
              </View>
            ) : (
              <View style={{width: '100%', gap: 12}}>
                <Pressable
                  onPress={() => setManualNotesMode(true)}
                  className="bg-brand-500 rounded-xl py-3.5 items-center w-full"
                >
                  <Text style={{color: tokens.textInverse, fontWeight: '600', fontSize: 12, textTransform: 'uppercase', letterSpacing: 1}}>
                    Add Notes Yourself
                  </Text>
                </Pressable>
                <Pressable
                  onPress={() => navigation.navigate('CallHistory')}
                  style={{borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 12, paddingVertical: 14, alignItems: 'center'}}
                >
                  <Text style={{color: tokens.textSecondary, fontWeight: '600', fontSize: 12, textTransform: 'uppercase', letterSpacing: 1}}>
                    Go to History
                  </Text>
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
    <View style={{paddingBottom: Math.max(insets.bottom, 16), flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Offline banner */}
      {isOffline && (
        <View
          style={{
            backgroundColor: '#F59E0B1A',
            borderBottomWidth: 1,
            borderBottomColor: '#F59E0B33',
            paddingHorizontal: 16,
            paddingVertical: 12,
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 30,
          }}
        >
          <Icon name="wifi-off" size={14} color="#F59E0B" />
          <Text style={{color: '#F59E0B', fontSize: 11, fontWeight: '600', letterSpacing: 1, textTransform: 'uppercase', marginLeft: 8}}>
            Some details will sync when you're back online
          </Text>
        </View>
      )}

      <ScrollView
        showsVerticalScrollIndicator={false}
        contentContainerStyle={{
          paddingTop: Math.max(insets.top, 20),
          paddingHorizontal: 20,
          paddingBottom: 110,
        }}
        style={{flex: 1}}
      >
        {/* Header */}
        <Animated.View style={[getAnimStyle(headerAnim), {flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 20}]}>
          <View style={{flex: 1, marginRight: 16}}>
            <Text style={{color: tokens.textPrimary, fontSize: 20, fontWeight: '700', letterSpacing: -0.5}}>
              Call with {displayName}
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontFamily: 'monospace', marginTop: 4}}>
              {duration} · {callTimeStr}
            </Text>
          </View>
          {contact && (
            <Pressable
              onPress={() => navigation.navigate('ContactDetail', {contactId: contact.id})}
              style={{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: `${tokens.brandPrimary}33`,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}4D`,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 14}}>{initials}</Text>
            </Pressable>
          )}
        </Animated.View>

        {/* Sentiment badge */}
        <Animated.View style={[getAnimStyle(headerAnim), {flexDirection: 'row', alignItems: 'center', marginBottom: 24}]}>
          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: sentimentData.bg,
              borderWidth: 1,
              borderColor: `${sentimentData.dotColor}33`,
              borderRadius: 999,
              paddingHorizontal: 14,
              paddingVertical: 6,
            }}
          >
            <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: sentimentData.dotColor, marginRight: 8}} />
            <Text style={{color: sentimentData.text, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8}}>
              {summary.sentiment}
            </Text>
            {trend && (
              <Text style={{color: tokens.textSecondary, fontSize: 10, marginLeft: 8, fontWeight: '500', letterSpacing: 0.5}}>
                {trend}
              </Text>
            )}
          </View>
        </Animated.View>

        {/* AI Summary card */}
        <Animated.View
          style={[
            getAnimStyle(summaryAnim),
            {
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
            },
          ]}
        >
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10}}>
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
              <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8}}>
                ✦ AI Summary
              </Text>
              {isEdited && (
                <Text style={{color: tokens.textTertiary, fontSize: 10, textTransform: 'uppercase', letterSpacing: 2, marginLeft: 8, fontWeight: '500'}}>
                  · Edited
                </Text>
              )}
            </View>
            {!isEditingSummary && (
              <Pressable onPress={() => { setIsEditingSummary(true); setTempSummaryText(editedSummary); }} style={{paddingHorizontal: 8, paddingVertical: 2}}>
                <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700', textTransform: 'uppercase'}}>Edit</Text>
              </Pressable>
            )}
          </View>

          {isEditingSummary ? (
            <View style={{width: '100%', marginTop: 4}}>
              <TextInput
                style={{
                  backgroundColor: tokens.surfaceInput,
                  color: tokens.textPrimary,
                  fontSize: 12,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 8,
                  padding: 10,
                  lineHeight: 20,
                  minHeight: 80,
                  textAlignVertical: 'top',
                }}
                multiline
                value={tempSummaryText}
                onChangeText={setTempSummaryText}
              />
              <View style={{flexDirection: 'row', gap: 8, marginTop: 12, justifyContent: 'flex-end'}}>
                <Pressable
                  onPress={() => setIsEditingSummary(false)}
                  style={{
                    backgroundColor: tokens.surfaceRaised,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    paddingHorizontal: 12,
                    paddingVertical: 6,
                    borderRadius: 8,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', textTransform: 'uppercase'}}>Cancel</Text>
                </Pressable>
                <Pressable
                  onPress={() => { setEditedSummary(tempSummaryText); setIsEditingSummary(false); setIsEdited(true); }}
                  className="bg-brand-500 px-3 py-1.5 rounded-lg"
                >
                  <Text style={{color: tokens.textInverse, fontSize: 12, fontWeight: '700', textTransform: 'uppercase'}}>Save</Text>
                </Pressable>
              </View>
            </View>
          ) : (
            <Text style={{color: tokens.textPrimary, fontSize: 12, lineHeight: 20}}>{editedSummary}</Text>
          )}
        </Animated.View>

        {/* Key Points */}
        {(summary.key_points ?? []).length > 0 && (
          <Animated.View style={[getAnimStyle(keyPointsAnim), {marginBottom: 24}]}>
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
          </Animated.View>
        )}

        {/* Action Items */}
        {actionItems.length > 0 && (
          <Animated.View style={[getAnimStyle(actionItemsAnim), {marginBottom: 24}]}>
            <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.8, marginBottom: 10}}>
              Action Items — confirm to create tasks
            </Text>
            <View style={{gap: 10}}>
              {actionItems.map((item, idx) => {
                const checked = checkedItems[idx];
                const hasDate = hasImpliedDueDate(item);
                return (
                  <View
                    key={idx}
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
                    <Pressable
                      onPress={() => toggleCheck(idx)}
                      style={{
                        width: 20,
                        height: 20,
                        borderRadius: 4,
                        borderWidth: 1,
                        alignItems: 'center',
                        justifyContent: 'center',
                        marginRight: 12,
                        backgroundColor: checked ? tokens.brandPrimary : tokens.surfaceInput,
                        borderColor: checked ? tokens.brandPrimary : tokens.borderDefault,
                      }}
                    >
                      {checked && <Icon name="check" size={12} color="#FAFAFA" />}
                    </Pressable>

                    {editingItemIdx === idx ? (
                      <TextInput
                        autoFocus
                        value={editingItemText}
                        onChangeText={setEditingItemText}
                        onBlur={() => { const updated = [...actionItems]; updated[idx] = editingItemText; setActionItems(updated); setEditingItemIdx(null); }}
                        onSubmitEditing={() => { const updated = [...actionItems]; updated[idx] = editingItemText; setActionItems(updated); setEditingItemIdx(null); }}
                        style={{
                          flex: 1,
                          color: tokens.textPrimary,
                          fontSize: 12,
                          backgroundColor: tokens.surfaceRaised,
                          borderWidth: 1,
                          borderColor: tokens.borderDefault,
                          borderRadius: 8,
                          paddingHorizontal: 8,
                          paddingVertical: 4,
                        }}
                      />
                    ) : (
                      <Pressable
                        onPress={() => { setEditingItemIdx(idx); setEditingItemText(item); }}
                        style={{flex: 1, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between'}}
                      >
                        <Text
                          style={{
                            fontSize: 12,
                            lineHeight: 16,
                            flex: 1,
                            color: checked ? tokens.textPrimary : tokens.textTertiary,
                            textDecorationLine: checked ? 'none' : 'line-through',
                          }}
                        >
                          {item}
                        </Text>
                        {hasDate && (
                          <View style={{marginLeft: 8, backgroundColor: '#F59E0B1A', padding: 4, borderRadius: 6, borderWidth: 1, borderColor: '#F59E0B33'}}>
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

      {/* Sticky bottom bar */}
      <View
        style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          right: 0,
          borderTopWidth: 1,
          borderTopColor: tokens.borderDefault,
          backgroundColor: tokens.surfacePage,
          paddingHorizontal: 24,
          paddingVertical: 16,
          zIndex: 20,
          ...tokens.shadowMd,
        }}
      >
        <Pressable
          onPress={handleConfirmPress}
          disabled={confirm.isPending || isConfirmed}
          className="w-full h-14 rounded-xl items-center justify-center flex-row bg-brand-500 shadow-lg"
          style={({pressed}) => [{transform: [{scale: pressed ? 0.96 : 1}]}]}
        >
          {confirm.isPending ? (
            <ActivityIndicator color="#fff" />
          ) : isConfirmed ? (
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
              <Icon name="check-circle" size={18} color="#FAFAFA" />
              <Text style={{color: tokens.textInverse, fontWeight: '700', fontSize: 12, textTransform: 'uppercase', letterSpacing: 2}}>
                ✓ Confirmed &amp; Saved
              </Text>
            </View>
          ) : (
            <Text style={{color: tokens.textInverse, fontWeight: '700', fontSize: 12, textTransform: 'uppercase', letterSpacing: 2}}>
              Confirm &amp; Create {checkedCount} {checkedCount === 1 ? 'Task' : 'Tasks'}
            </Text>
          )}
        </Pressable>

        <View style={{flexDirection: 'row', justifyContent: 'space-between', marginTop: 14, paddingHorizontal: 12}}>
          <Pressable onPress={() => navigation.navigate('CallTranscript', {callId})}>
            <Text style={{color: tokens.brandPrimary, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1}}>
              View Full Transcript
            </Text>
          </Pressable>
          <Pressable onPress={() => navigation.navigate('CallHistory')}>
            <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 1}}>
              Dismiss
            </Text>
          </Pressable>
        </View>
      </View>
    </View>
  );
}
