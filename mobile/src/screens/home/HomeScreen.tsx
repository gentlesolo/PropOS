import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  Pressable,
  ScrollView,
  Text,
  View,
  Animated,
  Dimensions,
  Image,
  Linking,
  ActivityIndicator,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import Icon from 'react-native-vector-icons/Feather';
import {format, isToday} from 'date-fns';
import {tasksApi} from '../../api/tasks';
import {viewingsApi, Viewing} from '../../api/viewings';
import {callsApi} from '../../api/calls';
import {messagingApi} from '../../api/messaging';
import {briefApi} from '../../api/brief';
import {useAuthStore} from '../../store/authStore';
import {useNotificationStore} from '../../store/notificationStore';
import {storage} from '../../api/client';
import {Task, Call} from '../../types';
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

function PriorityCard({
  item,
  onDismiss,
  onPressAction,
  onPressCard,
  tokens,
}: {
  item: any;
  onDismiss: (id: string) => void;
  onPressAction: () => void;
  onPressCard: () => void;
  tokens: ThemeTokens;
}) {
  const scrollRef = useRef<ScrollView>(null);
  const heightAnim = useRef(new Animated.Value(1)).current;
  const opacityAnim = useRef(new Animated.Value(1)).current;

  const handleDismiss = () => {
    Animated.parallel([
      Animated.timing(heightAnim, {toValue: 0, duration: 250, useNativeDriver: false}),
      Animated.timing(opacityAnim, {toValue: 0, duration: 200, useNativeDriver: false}),
    ]).start(() => onDismiss(item.id));
  };

  const cardHeight = heightAnim.interpolate({inputRange: [0, 1], outputRange: [0, 92]});

  const urgencyColor =
    item.urgency === 'danger' ? '#F43F5E' : item.urgency === 'amber' ? '#F59E0B' : '#10B981';

  return (
    <Animated.View style={{height: cardHeight, opacity: opacityAnim, overflow: 'hidden', marginBottom: 12}}>
      <ScrollView
        ref={scrollRef}
        horizontal
        showsHorizontalScrollIndicator={false}
        snapToInterval={80}
        decelerationRate="fast"
        contentContainerStyle={{width: SCREEN_WIDTH - 40 + 80}}
      >
        {/* Card body */}
        <Pressable
          onPress={onPressCard}
          style={{
            width: SCREEN_WIDTH - 40,
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginRight: 8,
          }}
        >
          {/* Urgency bar */}
          <View
            style={{
              position: 'absolute',
              left: 0,
              top: 12,
              bottom: 12,
              width: 4,
              borderRadius: 2,
              backgroundColor: urgencyColor,
            }}
          />

          {/* Avatar */}
          <View style={{marginRight: 12, marginLeft: 4}}>
            {item.avatar_path ? (
              <Image source={{uri: item.avatar_path}} style={{width: 40, height: 40, borderRadius: 20}} />
            ) : (
              <View
                style={{
                  width: 40,
                  height: 40,
                  borderRadius: 20,
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '800'}}>
                  {item.initials || '?'}
                </Text>
              </View>
            )}
          </View>

          {/* Description */}
          <View style={{flex: 1, marginRight: 8}}>
            <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '600', lineHeight: 20}} numberOfLines={2}>
              {item.description}
            </Text>
          </View>

          {/* Action button */}
          <Pressable
            onPress={(e) => {e.stopPropagation(); onPressAction();}}
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: `${tokens.brandPrimary}1A`,
              borderWidth: 1,
              borderColor: `${tokens.brandPrimary}33`,
              alignItems: 'center',
              justifyContent: 'center',
            }}
            hitSlop={{top: 10, bottom: 10, left: 10, right: 10}}
          >
            <Icon name={item.actionIcon} size={18} color={tokens.brandPrimary} />
          </Pressable>
        </Pressable>

        {/* Dismiss panel */}
        <Pressable
          onPress={handleDismiss}
          style={{
            width: 72,
            height: 82,
            backgroundColor: tokens.surfaceRaised,
            borderRadius: 16,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Icon name="bell-off" size={20} color={tokens.textTertiary} />
          <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '700', marginTop: 4}}>Snooze</Text>
        </Pressable>
      </ScrollView>
    </Animated.View>
  );
}

function StatChip({
  icon,
  count,
  label,
  pulse,
  onPress,
  tokens,
}: {
  icon: string;
  count: number;
  label: string;
  pulse?: boolean;
  onPress: () => void;
  tokens: ThemeTokens;
}) {
  const pulseScale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (pulse) {
      const loop = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseScale, {toValue: 1.04, duration: 1200, useNativeDriver: true}),
          Animated.timing(pulseScale, {toValue: 0.98, duration: 1200, useNativeDriver: true}),
        ])
      );
      loop.start();
      return () => loop.stop();
    }
  }, [pulse]);

  return (
    <Animated.View style={pulse ? {transform: [{scale: pulseScale}]} : {}}>
      <Pressable
        onPress={onPress}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          backgroundColor: tokens.surfaceCard,
          borderWidth: 1,
          borderColor: pulse ? `${tokens.brandPrimary}66` : tokens.borderDefault,
          borderRadius: 999,
          paddingHorizontal: 16,
          paddingVertical: 10,
          marginRight: 12,
          ...tokens.shadowSm,
        }}
      >
        <Icon name={icon} size={15} color={pulse ? '#F59E0B' : tokens.brandPrimary} style={{marginRight: 10}} />
        <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800', fontVariant: ['tabular-nums'], marginRight: 6}}>
          {count}
        </Text>
        <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '600'}}>{label}</Text>
      </Pressable>
    </Animated.View>
  );
}

function RotatingSyncIcon({color}: {color: string}) {
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.timing(rotateAnim, {toValue: 1, duration: 1000, useNativeDriver: true})
    ).start();
  }, []);

  const spin = rotateAnim.interpolate({inputRange: [0, 1], outputRange: ['0deg', '360deg']});

  return (
    <Animated.View style={{transform: [{rotate: spin}], marginLeft: 10, justifyContent: 'center', alignItems: 'center'}}>
      <Icon name="loader" size={14} color={color} />
    </Animated.View>
  );
}

export function HomeScreen() {
  const {tokens} = useTheme();
  const {user} = useAuthStore();
  const {unreadCount} = useNotificationStore();
  const navigation = useNavigation<any>();
  const queryClient = useQueryClient();

  const [cachedData] = useState(() => {
    const raw = storage.getString('home_cached_data');
    if (raw) {
      try {return JSON.parse(raw);} catch (e) {return null;}
    }
    return null;
  });

  const [dismissedIds, setDismissedIds] = useState<string[]>([]);
  const [pullDistance, setPullDistance] = useState(0);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showCheckmark, setShowCheckmark] = useState(false);
  const refreshHeaderHeight = useRef(new Animated.Value(0)).current;
  const skeletonOpacity = useRef(new Animated.Value(0.3)).current;

  useEffect(() => {
    const saved = storage.getString('dismissed_priorities');
    if (saved) {
      try {setDismissedIds(JSON.parse(saved));} catch (e) {}
    }
  }, []);

  useEffect(() => {
    const shimmer = Animated.loop(
      Animated.sequence([
        Animated.timing(skeletonOpacity, {toValue: 0.7, duration: 900, useNativeDriver: true}),
        Animated.timing(skeletonOpacity, {toValue: 0.3, duration: 900, useNativeDriver: true}),
      ])
    );
    shimmer.start();
    return () => shimmer.stop();
  }, []);

  const {data: tasks, refetch: refetchTasks, isPending: tasksPending, isFetching: isFetchingTasks} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then((r) => r.data),
    initialData: cachedData?.tasks,
  });

  const {data: viewings, refetch: refetchViewings, isFetching: isFetchingViewings} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then((r) => r.data),
    initialData: cachedData?.viewings,
  });

  const {data: inbox, refetch: refetchInbox, isFetching: isFetchingInbox} = useQuery({
    queryKey: ['inbox'],
    queryFn: () => messagingApi.inbox().then((r) => r.data),
    initialData: cachedData?.inbox,
    staleTime: 60_000,
  });

  const {data: pendingCalls, refetch: refetchCalls, isFetching: isFetchingCalls} = useQuery({
    queryKey: ['calls', 'pending-review'],
    queryFn: () =>
      callsApi.list({direction: 'outbound'}).then((r) =>
        r.data.data.filter(
          (c) =>
            c.status === 'completed' &&
            c.summary &&
            !c.summary.agent_confirmed_at &&
            c.started_at &&
            isToday(new Date(c.started_at))
        )
      ),
    initialData: cachedData?.pendingCalls,
    staleTime: 60_000,
  });

  const {data: brief, refetch: refetchBrief, isFetching: isFetchingBrief} = useQuery({
    queryKey: ['brief'],
    queryFn: () => briefApi.get().then((r) => r.data),
    initialData: cachedData?.brief,
    staleTime: 30 * 60_000,
  });

  const isAnyFetching = isFetchingTasks || isFetchingViewings || isFetchingInbox || isFetchingCalls || isFetchingBrief;

  useEffect(() => {
    if (tasks && viewings && inbox && pendingCalls && brief) {
      storage.set('home_cached_data', JSON.stringify({tasks, viewings, inbox, pendingCalls, brief}));
    }
  }, [tasks, viewings, inbox, pendingCalls, brief]);

  const completeTaskMutation = useMutation({
    mutationFn: (id: number) => tasksApi.update(id, {status: 'completed'}),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['tasks', 'today']}),
  });

  const confirmViewingMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.updateStatus(id, 'confirmed'),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['viewings', 'today']}),
  });

  const priorities = useMemo(() => {
    const list: any[] = [];

    if (pendingCalls && pendingCalls.length > 0) {
      pendingCalls.forEach((call: Call) => {
        const name = call.contact
          ? `${call.contact.first_name} ${call.contact.last_name}`
          : call.remote_number || 'Client';
        list.push({
          id: `call-review-${call.id}`,
          type: 'review',
          description: `Review AI transcription & summary for call with ${name}`,
          urgency: 'amber',
          actionIcon: 'eye',
          avatar_path: call.contact?.avatar_path,
          initials: call.contact ? `${call.contact.first_name?.[0] || ''}${call.contact.last_name?.[0] || ''}` : 'C',
          targetCallId: call.id,
        });
      });
    }

    if (tasks) {
      const overdue = tasks.filter(
        (t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()
      );
      overdue.forEach((t: Task) => {
        list.push({
          id: `task-${t.id}`,
          type: 'confirm-task',
          description: `Overdue Task: ${t.title}${t.contact ? ` — follow up with ${t.contact.first_name}` : ''}`,
          urgency: 'danger',
          actionIcon: 'check',
          avatar_path: t.contact?.avatar_path,
          initials: t.contact ? `${t.contact.first_name?.[0] || ''}${t.contact.last_name?.[0] || ''}` : 'T',
          targetTaskId: t.id,
        });
      });
    }

    if (viewings) {
      viewings
        .filter((v: Viewing) => v.status === 'scheduled')
        .forEach((v: Viewing) => {
          const timeStr = v.scheduled_at ? format(new Date(v.scheduled_at), 'HH:mm') : '';
          list.push({
            id: `viewing-${v.id}`,
            type: 'confirm-viewing',
            description: `Confirm viewing at ${v.listing?.address || 'Listing'} with ${
              v.contact ? `${v.contact.first_name} ${v.contact.last_name}` : 'Client'
            } — ${timeStr}`,
            urgency: 'amber',
            actionIcon: 'check',
            avatar_path: v.contact?.avatar_path,
            initials: v.contact ? `${v.contact.first_name?.[0] || ''}${v.contact.last_name?.[0] || ''}` : 'V',
            targetViewingId: v.id,
          });
        });
    }

    if (tasks) {
      const active = tasks.filter((t: Task) => t.status !== 'completed');
      if (active.length > 0 && active[0].contact) {
        const contact = active[0].contact;
        if (contact.phone) {
          list.push({
            id: `quiet-${contact.id}`,
            type: 'call',
            description: `Call ${contact.first_name} ${contact.last_name} — she's been quiet for 4 days`,
            urgency: 'emerald',
            actionIcon: 'phone',
            avatar_path: contact.avatar_path,
            initials: `${contact.first_name?.[0] || ''}${contact.last_name?.[0] || ''}`,
            targetContact: {id: contact.id, name: `${contact.first_name} ${contact.last_name}`, phone: contact.phone},
          });
        }
      }
    }

    return list.filter((item) => !dismissedIds.includes(item.id));
  }, [tasks, viewings, pendingCalls, dismissedIds]);

  const handleDismissPriority = (id: string) => {
    const updated = [...dismissedIds, id];
    setDismissedIds(updated);
    storage.set('dismissed_priorities', JSON.stringify(updated));
  };

  const handleActionClick = (item: any) => {
    if (item.type === 'call') {
      if (item.targetContact?.phone) Linking.openURL(`tel:${item.targetContact.phone}`);
    } else if (item.type === 'confirm-task') {
      completeTaskMutation.mutate(item.targetTaskId);
    } else if (item.type === 'confirm-viewing') {
      confirmViewingMutation.mutate(item.targetViewingId);
    } else if (item.type === 'review') {
      navigation.navigate('Calls', {screen: 'PostCallSummary', params: {callId: item.targetCallId}});
    }
  };

  const handleCardClick = (item: any) => {
    if (item.targetViewingId) {
      navigation.navigate('Viewings', {screen: 'ViewingDetail', params: {viewingId: item.targetViewingId}});
    } else if (item.targetCallId) {
      navigation.navigate('Calls', {screen: 'PostCallSummary', params: {callId: item.targetCallId}});
    } else if (item.targetContact) {
      navigation.navigate('Contacts', {screen: 'ContactDetail', params: {contactId: item.targetContact.id}});
    } else {
      navigation.navigate('Tasks');
    }
  };

  const triggerRefresh = async () => {
    setIsRefreshing(true);
    Animated.spring(refreshHeaderHeight, {toValue: 60, useNativeDriver: false}).start();
    try {
      await Promise.all([refetchTasks(), refetchViewings(), refetchInbox(), refetchCalls(), refetchBrief()]);
    } catch (e) {
      console.warn('Silent refresh error:', e);
    }
    setShowCheckmark(true);
    setTimeout(() => {
      Animated.timing(refreshHeaderHeight, {toValue: 0, duration: 300, useNativeDriver: false}).start(() => {
        setIsRefreshing(false);
        setShowCheckmark(false);
      });
    }, 600);
  };

  const handleScroll = (event: any) => {
    const y = event.nativeEvent.contentOffset.y;
    setPullDistance(y < 0 ? -y : 0);
  };

  const handleScrollEnd = () => {
    if (pullDistance > 85 && !isRefreshing) triggerRefresh();
  };

  const viewingsCount = viewings?.length ?? 0;
  const overdueTasksCount =
    tasks?.filter((t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()).length ?? 0;
  const unreadMessagesCount = inbox?.length ?? 0;
  const pendingCallsCount = pendingCalls?.length ?? 0;

  const upcomingToday = useMemo(() => (viewings ? viewings.slice(0, 3) : []), [viewings]);

  const isFirstLoad = !cachedData && tasksPending;

  const getTimeOfDay = () => {
    const hour = new Date().getHours();
    if (hour < 12) return 'morning';
    if (hour < 17) return 'afternoon';
    return 'evening';
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          paddingHorizontal: 20,
          paddingTop: 12,
          paddingBottom: 12,
        }}
      >
        <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between'}}>
          <View style={{flex: 1, marginRight: 12}}>
            <View style={{flexDirection: 'row', alignItems: 'center'}}>
              <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', letterSpacing: -0.5}} numberOfLines={1}>
                Good {getTimeOfDay()}, {user?.first_name || 'Agent'}
              </Text>
              {isAnyFetching && <RotatingSyncIcon color={tokens.brandPrimary} />}
            </View>
            <Text style={{color: tokens.textTertiary, fontSize: 13, fontWeight: '500', marginTop: 2}}>
              {format(new Date(), 'EEEE, d MMMM')}
            </Text>
          </View>

          <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
            {/* Bell */}
            <Pressable
              onPress={() => navigation.navigate('Notifications')}
              style={{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: tokens.surfaceRaised,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Icon name="bell" size={18} color={unreadCount > 0 ? tokens.brandPrimary : tokens.textTertiary} />
              {unreadCount > 0 && (
                <View
                  style={{
                    position: 'absolute',
                    top: 7,
                    right: 7,
                    width: 8,
                    height: 8,
                    borderRadius: 4,
                    backgroundColor: '#F59E0B',
                    borderWidth: 1.5,
                    borderColor: tokens.surfaceCard,
                  }}
                />
              )}
            </Pressable>

            {/* Avatar */}
            <Pressable
              onPress={() => navigation.navigate('Profile')}
              style={{
                width: 40,
                height: 40,
                borderRadius: 20,
                backgroundColor: `${tokens.brandPrimary}1A`,
                borderWidth: 1.5,
                borderColor: `${tokens.brandPrimary}40`,
                alignItems: 'center',
                justifyContent: 'center',
                overflow: 'hidden',
              }}
            >
              {user?.avatar_path ? (
                <Image source={{uri: user.avatar_path}} style={{width: 40, height: 40}} />
              ) : (
                <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 14}}>
                  {(user?.first_name?.[0] || 'A').toUpperCase()}
                </Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>

      {/* Scroll content */}
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 40, paddingTop: 8}}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        onScrollEndDrag={handleScrollEnd}
        showsVerticalScrollIndicator={false}
      >
        {/* Pull to refresh indicator */}
        <Animated.View style={{height: refreshHeaderHeight, width: '100%', alignItems: 'center', justifyContent: 'center', overflow: 'hidden'}}>
          <View
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              alignItems: 'center',
              justifyContent: 'center',
              ...tokens.shadowSm,
            }}
          >
            {showCheckmark ? (
              <Icon name="check" size={20} color={tokens.brandPrimary} />
            ) : (
              <ActivityIndicator size="small" color={tokens.brandPrimary} />
            )}
          </View>
        </Animated.View>

        {/* Stat chips */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, paddingVertical: 12}}
          style={{marginBottom: 24}}
        >
          {pendingCallsCount > 0 && (
            <StatChip icon="phone-call" count={pendingCallsCount} label="call summary ready" pulse tokens={tokens} onPress={() => navigation.navigate('Calls')} />
          )}
          <StatChip icon="calendar" count={viewingsCount} label="viewings today" tokens={tokens} onPress={() => navigation.navigate('Viewings')} />
          <StatChip icon="check-square" count={overdueTasksCount} label="overdue tasks" tokens={tokens} onPress={() => navigation.navigate('Tasks')} />
          <StatChip icon="message-square" count={unreadMessagesCount} label="unread messages" tokens={tokens} onPress={() => navigation.navigate('Inbox')} />
        </ScrollView>

        {/* Priorities */}
        <View style={{paddingHorizontal: 20, marginBottom: 32}}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 16}}>
            <Icon name="zap" size={16} color={tokens.brandPrimary} />
            <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '700', letterSpacing: -0.3}}>
              Today's priorities
            </Text>
          </View>

          {isFirstLoad ? (
            // Skeleton
            <View>
              {[0, 1].map((i) => (
                <Animated.View
                  key={i}
                  style={{
                    opacity: skeletonOpacity,
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    marginBottom: 12,
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    height: 82,
                  }}
                >
                  <View style={{flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 16}}>
                    <View style={{width: 40, height: 40, borderRadius: 20, backgroundColor: tokens.surfaceRaised, marginRight: 12}} />
                    <View style={{flex: 1, gap: 8}}>
                      <View style={{height: 16, backgroundColor: tokens.surfaceRaised, borderRadius: 8, width: i === 0 ? '83%' : '67%'}} />
                      <View style={{height: 12, backgroundColor: tokens.surfaceRaised, borderRadius: 8, width: i === 0 ? '50%' : '33%'}} />
                    </View>
                  </View>
                  <View style={{width: 40, height: 40, borderRadius: 20, backgroundColor: tokens.surfaceRaised}} />
                </Animated.View>
              ))}
            </View>
          ) : priorities.length === 0 ? (
            <View
              style={{
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                paddingVertical: 32,
                paddingHorizontal: 24,
                alignItems: 'center',
                justifyContent: 'center',
                ...tokens.shadowSm,
              }}
            >
              <View
                style={{
                  width: 56,
                  height: 56,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  borderWidth: 2,
                  borderColor: tokens.brandPrimary,
                  borderRadius: 28,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 12,
                }}
              >
                <Icon name="check" size={28} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontWeight: '700', fontSize: 16, marginBottom: 4}}>
                You're all caught up
              </Text>
              <Text style={{color: tokens.textTertiary, fontSize: 12, textAlign: 'center'}}>
                Nothing urgent right now.
              </Text>
            </View>
          ) : (
            priorities.map((item) => (
              <PriorityCard
                key={item.id}
                item={item}
                tokens={tokens}
                onDismiss={handleDismissPriority}
                onPressAction={() => handleActionClick(item)}
                onPressCard={() => handleCardClick(item)}
              />
            ))
          )}
        </View>

        {/* Recent call summaries */}
        {pendingCalls && pendingCalls.length > 0 && (
          <View style={{marginBottom: 32}}>
            <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, marginBottom: 16}}>
              <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '700', letterSpacing: -0.3}}>
                Recent call summaries
              </Text>
            </View>
            <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={{paddingHorizontal: 20}}>
              {pendingCalls.map((call: Call) => {
                const name = call.contact
                  ? `${call.contact.first_name} ${call.contact.last_name}`
                  : call.remote_number || 'Unknown';
                const sentimentColor =
                  call.summary?.sentiment === 'hot' ? '#F43F5E'
                  : call.summary?.sentiment === 'warm' ? '#F59E0B'
                  : call.summary?.sentiment === 'cold' ? '#38BDF8'
                  : tokens.textTertiary;

                return (
                  <View
                    key={call.id}
                    style={{
                      width: 288,
                      backgroundColor: tokens.surfaceCard,
                      borderWidth: 1,
                      borderColor: tokens.borderDefault,
                      borderRadius: 16,
                      padding: 16,
                      marginRight: 16,
                      justifyContent: 'space-between',
                      height: 132,
                      ...tokens.shadowSm,
                    }}
                  >
                    <View>
                      <View style={{flexDirection: 'row', alignItems: 'center', marginBottom: 8, gap: 8}}>
                        <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: sentimentColor}} />
                        <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700', flex: 1}} numberOfLines={1}>
                          {name}
                        </Text>
                      </View>
                      <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 16}} numberOfLines={2}>
                        {call.summary?.summary_text || 'AI Summary pending review...'}
                      </Text>
                    </View>
                    <View style={{flexDirection: 'row', justifyContent: 'flex-end'}}>
                      <Pressable
                        onPress={() => navigation.navigate('Calls', {screen: 'PostCallSummary', params: {callId: call.id}})}
                        style={{
                          backgroundColor: '#F59E0B1A',
                          borderWidth: 1,
                          borderColor: '#F59E0B4D',
                          borderRadius: 999,
                          paddingHorizontal: 16,
                          paddingVertical: 6,
                        }}
                      >
                        <Text style={{color: '#F59E0B', fontSize: 12, fontWeight: '700'}}>Review</Text>
                      </Pressable>
                    </View>
                  </View>
                );
              })}
            </ScrollView>
          </View>
        )}

        {/* Upcoming viewings */}
        {viewings && viewings.length > 0 && (
          <View style={{paddingHorizontal: 20, marginBottom: 16}}>
            <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 16}}>
              <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '700', letterSpacing: -0.3}}>
                Upcoming viewings
              </Text>
              {viewings.length > 3 && (
                <Pressable onPress={() => navigation.navigate('Viewings')}>
                  <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700'}}>View all →</Text>
                </Pressable>
              )}
            </View>

            <View
              style={{
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                overflow: 'hidden',
                padding: 8,
                gap: 6,
                ...tokens.shadowSm,
              }}
            >
              {upcomingToday.map((v: Viewing, idx: number) => {
                const timeStr = v.scheduled_at ? format(new Date(v.scheduled_at), 'HH:mm') : '12:00';
                const statusDot =
                  v.status === 'confirmed' ? '#22C55E' : v.status === 'scheduled' ? '#F59E0B' : tokens.textTertiary;

                return (
                  <Pressable
                    key={v.id}
                    onPress={() => navigation.navigate('Viewings', {screen: 'ViewingDetail', params: {viewingId: v.id}})}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      justifyContent: 'space-between',
                      padding: 12,
                      borderBottomWidth: idx < upcomingToday.length - 1 ? 1 : 0,
                      borderBottomColor: tokens.borderSubtle,
                      borderRadius: 12,
                    }}
                  >
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 12, flex: 1, marginRight: 16}}>
                      <Text style={{color: tokens.brandPrimary, fontFamily: 'monospace', fontWeight: '700', fontSize: 14, width: 48}}>
                        {timeStr}
                      </Text>
                      <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '600', flex: 1}} numberOfLines={1}>
                        {v.listing?.address || 'Listing Address'}
                      </Text>
                    </View>
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
                      <View style={{width: 10, height: 10, borderRadius: 5, backgroundColor: statusDot}} />
                    </View>
                  </Pressable>
                );
              })}
            </View>
          </View>
        )}
      </ScrollView>
    </SafeAreaView>
  );
}
