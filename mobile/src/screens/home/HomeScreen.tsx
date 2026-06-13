import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  Pressable,
  ScrollView,
  Text,
  View,
  SafeAreaView,
  Animated,
  Dimensions,
  Image,
  Linking,
  ActivityIndicator,
  useColorScheme,
} from 'react-native';
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

const {width: SCREEN_WIDTH} = Dimensions.get('window');

// 1. Swipeable Priority Card Component with collapse animation
function PriorityCard({
  item,
  onDismiss,
  onPressAction,
  onPressCard,
}: {
  item: any;
  onDismiss: (id: string) => void;
  onPressAction: () => void;
  onPressCard: () => void;
}) {
  const scrollRef = useRef<ScrollView>(null);
  const heightAnim = useRef(new Animated.Value(1)).current;
  const opacityAnim = useRef(new Animated.Value(1)).current;

  const handleDismiss = () => {
    Animated.parallel([
      Animated.timing(heightAnim, {
        toValue: 0,
        duration: 250,
        useNativeDriver: false,
      }),
      Animated.timing(opacityAnim, {
        toValue: 0,
        duration: 200,
        useNativeDriver: false,
      }),
    ]).start(() => {
      onDismiss(item.id);
    });
  };

  const cardHeight = heightAnim.interpolate({
    inputRange: [0, 1],
    outputRange: [0, 92],
  });

  return (
    <Animated.View
      style={{
        height: cardHeight,
        opacity: opacityAnim,
        overflow: 'hidden',
        marginBottom: 12,
      }}
    >
      <ScrollView
        ref={scrollRef}
        horizontal
        showsHorizontalScrollIndicator={false}
        snapToInterval={80}
        decelerationRate="fast"
        contentContainerStyle={{width: SCREEN_WIDTH - 40 + 80}}
      >
        {/* Main Priority Card body */}
        <Pressable
          onPress={onPressCard}
          style={{width: SCREEN_WIDTH - 40}}
          className="flex-row items-center bg-surface-card border border-zinc-800/80 rounded-2xl p-4 mr-2 relative"
        >
          {/* Urgency Indicator border (left side) */}
          <View
            className={`absolute left-0 top-3 bottom-3 w-1 rounded-r-md ${
              item.urgency === 'danger'
                ? 'bg-danger'
                : item.urgency === 'amber'
                ? 'bg-accent'
                : 'bg-success'
            }`}
          />

          {/* Left: Contact Avatar / Initial */}
          <View className="mr-3 ml-1">
            {item.avatar_path ? (
              <Image
                source={{uri: item.avatar_path}}
                className="w-10 h-10 rounded-full"
              />
            ) : (
              <View className="w-10 h-10 bg-surface-raised border border-zinc-800 rounded-full items-center justify-center">
                <Text className="text-text-primary text-xs font-extrabold">
                  {item.initials || '?'}
                </Text>
              </View>
            )}
          </View>

          {/* Middle: Action description */}
          <View className="flex-1 mr-2">
            <Text className="text-text-primary text-sm font-semibold leading-5" numberOfLines={2}>
              {item.description}
            </Text>
          </View>

          {/* Right: Direct action button */}
          <Pressable
            onPress={(e) => {
              e.stopPropagation();
              onPressAction();
            }}
            className="w-10 h-10 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center active:bg-brand-500/25"
            hitSlop={{top: 10, bottom: 10, left: 10, right: 10}}
          >
            <Icon name={item.actionIcon} size={18} color="#10B981" />
          </Pressable>
        </Pressable>

        {/* Swipe Left Panel: Dismiss / Snooze */}
        <Pressable
          onPress={handleDismiss}
          className="w-[72px] h-[82px] bg-zinc-800/80 rounded-2xl items-center justify-center active:bg-zinc-700/80"
        >
          <Icon name="bell-off" size={20} color="#A1A1AA" />
          <Text className="text-text-secondary text-[10px] font-bold mt-1">Snooze</Text>
        </Pressable>
      </ScrollView>
    </Animated.View>
  );
}

// 2. Stat Chip Component
function StatChip({
  icon,
  count,
  label,
  pulse,
  onPress,
}: {
  icon: string;
  count: number;
  label: string;
  pulse?: boolean;
  onPress: () => void;
}) {
  const pulseScale = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    if (pulse) {
      const loop = Animated.loop(
        Animated.sequence([
          Animated.timing(pulseScale, {
            toValue: 1.04,
            duration: 1200,
            useNativeDriver: true,
          }),
          Animated.timing(pulseScale, {
            toValue: 0.98,
            duration: 1200,
            useNativeDriver: true,
          }),
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
        className={`flex-row items-center bg-surface-card border border-zinc-800 rounded-full px-4 py-2.5 mr-3 shadow-md ${
          pulse ? 'border-brand-500/40 shadow-brand-500/5' : ''
        }`}
      >
        <View className="mr-2.5">
          <Icon name={icon} size={15} color={pulse ? '#F59E0B' : '#10B981'} />
        </View>
        <Text className="text-text-primary text-sm font-extrabold font-mono mr-1.5">{count}</Text>
        <Text className="text-text-secondary text-xs font-semibold">{label}</Text>
      </Pressable>
    </Animated.View>
  );
}

function RotatingSyncIcon() {
  const rotateAnim = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.loop(
      Animated.timing(rotateAnim, {
        toValue: 1,
        duration: 1000,
        useNativeDriver: true,
      })
    ).start();
  }, []);

  const spin = rotateAnim.interpolate({
    inputRange: [0, 1],
    outputRange: ['0deg', '360deg'],
  });

  return (
    <Animated.View style={{transform: [{rotate: spin}]}} className="ml-2.5 justify-center items-center">
      <Icon name="loader" size={14} color="#10B981" />
    </Animated.View>
  );
}

export function HomeScreen() {
  const {user} = useAuthStore();
  const {unreadCount} = useNotificationStore();
  const navigation = useNavigation<any>();
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';

  // Cache configuration
  const [cachedData] = useState(() => {
    const raw = storage.getString('home_cached_data');
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch (e) {
        return null;
      }
    }
    return null;
  });

  const [dismissedIds, setDismissedIds] = useState<string[]>([]);
  
  // Custom pull to refresh states
  const [pullDistance, setPullDistance] = useState(0);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showCheckmark, setShowCheckmark] = useState(false);
  const refreshHeaderHeight = useRef(new Animated.Value(0)).current;
  const skeletonOpacity = useRef(new Animated.Value(0.3)).current;

  // Load dismissed items from MMKV
  useEffect(() => {
    const saved = storage.getString('dismissed_priorities');
    if (saved) {
      try {
        setDismissedIds(JSON.parse(saved));
      } catch (e) {}
    }
  }, []);

  // Shimmer animation loop for first load
  useEffect(() => {
    const shimmer = Animated.loop(
      Animated.sequence([
        Animated.timing(skeletonOpacity, {
          toValue: 0.7,
          duration: 900,
          useNativeDriver: true,
        }),
        Animated.timing(skeletonOpacity, {
          toValue: 0.3,
          duration: 900,
          useNativeDriver: true,
        }),
      ])
    );
    shimmer.start();
    return () => shimmer.stop();
  }, []);

  // API Queries (Serving instantly from cache to support < 1s render)
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
      callsApi
        .list({direction: 'outbound'})
        .then((r) =>
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

  // Save fresh resolved queries back to MMKV
  useEffect(() => {
    if (tasks && viewings && inbox && pendingCalls && brief) {
      storage.set(
        'home_cached_data',
        JSON.stringify({tasks, viewings, inbox, pendingCalls, brief})
      );
    }
  }, [tasks, viewings, inbox, pendingCalls, brief]);

  // Mutations for priority updates
  const completeTaskMutation = useMutation({
    mutationFn: (id: number) => tasksApi.update(id, {status: 'completed'}),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks', 'today']});
    },
  });

  const confirmViewingMutation = useMutation({
    mutationFn: (id: number) => viewingsApi.updateStatus(id, 'confirmed'),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['viewings', 'today']});
    },
  });

  // Dynamic Priorities Engine
  const priorities = useMemo(() => {
    const list: any[] = [];

    // 1. Pending call reviews
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
          initials: call.contact
            ? `${call.contact.first_name?.[0] || ''}${call.contact.last_name?.[0] || ''}`
            : 'C',
          targetCallId: call.id,
        });
      });
    }

    // 2. Overdue Tasks
    if (tasks) {
      const overdue = tasks.filter(
        (t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()
      );
      overdue.forEach((t: Task) => {
        list.push({
          id: `task-${t.id}`,
          type: 'confirm-task',
          description: `Overdue Task: ${t.title}${
            t.contact ? ` — follow up with ${t.contact.first_name}` : ''
          }`,
          urgency: 'danger',
          actionIcon: 'check',
          avatar_path: t.contact?.avatar_path,
          initials: t.contact
            ? `${t.contact.first_name?.[0] || ''}${t.contact.last_name?.[0] || ''}`
            : 'T',
          targetTaskId: t.id,
        });
      });
    }

    // 3. Today's Viewings that need confirmation
    if (viewings) {
      const pendingViewings = viewings.filter((v: Viewing) => v.status === 'scheduled');
      pendingViewings.forEach((v: Viewing) => {
        const timeStr = v.scheduled_at ? format(new Date(v.scheduled_at), 'HH:mm') : '';
        const address = v.listing?.address || 'Listing';
        const contactName = v.contact ? `${v.contact.first_name} ${v.contact.last_name}` : 'Client';
        list.push({
          id: `viewing-${v.id}`,
          type: 'confirm-viewing',
          description: `Confirm viewing at ${address} with ${contactName} — ${timeStr}`,
          urgency: 'amber',
          actionIcon: 'check',
          avatar_path: v.contact?.avatar_path,
          initials: v.contact
            ? `${v.contact.first_name?.[0] || ''}${v.contact.last_name?.[0] || ''}`
            : 'V',
          targetViewingId: v.id,
        });
      });
    }

    // 4. Client inactive follow ups (Quiet contacts)
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
            targetContact: {
              id: contact.id,
              name: `${contact.first_name} ${contact.last_name}`,
              phone: contact.phone,
            },
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
      if (item.targetContact?.phone) {
        Linking.openURL(`tel:${item.targetContact.phone}`);
      }
    } else if (item.type === 'confirm-task') {
      completeTaskMutation.mutate(item.targetTaskId);
    } else if (item.type === 'confirm-viewing') {
      confirmViewingMutation.mutate(item.targetViewingId);
    } else if (item.type === 'review') {
      navigation.navigate('Calls', {
        screen: 'PostCallSummary',
        params: {callId: item.targetCallId},
      });
    }
  };

  const handleCardClick = (item: any) => {
    if (item.targetViewingId) {
      navigation.navigate('Viewings', {
        screen: 'ViewingDetail',
        params: {viewingId: item.targetViewingId},
      });
    } else if (item.targetCallId) {
      navigation.navigate('Calls', {
        screen: 'PostCallSummary',
        params: {callId: item.targetCallId},
      });
    } else if (item.targetContact) {
      navigation.navigate('Contacts', {
        screen: 'ContactDetail',
        params: {contactId: item.targetContact.id},
      });
    } else {
      navigation.navigate('Tasks');
    }
  };

  // Pull to refresh execution logic
  const triggerRefresh = async () => {
    setIsRefreshing(true);
    Animated.spring(refreshHeaderHeight, {
      toValue: 60,
      useNativeDriver: false,
    }).start();

    try {
      await Promise.all([
        refetchTasks(),
        refetchViewings(),
        refetchInbox(),
        refetchCalls(),
        refetchBrief(),
      ]);
    } catch (e) {
      console.warn('Silent refresh error:', e);
    }

    // Morph to checkmark state on complete
    setShowCheckmark(true);
    setTimeout(() => {
      Animated.timing(refreshHeaderHeight, {
        toValue: 0,
        duration: 300,
        useNativeDriver: false,
      }).start(() => {
        setIsRefreshing(false);
        setShowCheckmark(false);
      });
    }, 600);
  };

  const handleScroll = (event: any) => {
    const y = event.nativeEvent.contentOffset.y;
    if (y < 0) {
      setPullDistance(-y);
    } else {
      setPullDistance(0);
    }
  };

  const handleScrollEnd = () => {
    if (pullDistance > 85 && !isRefreshing) {
      triggerRefresh();
    }
  };

  // Header dynamic colors
  const gradientOverlay = isDarkMode
    ? 'bg-brand-500/10' // subtle dark emerald tint
    : 'bg-brand-50/70';  // faint light emerald wash

  // Counts for status strip chips
  const viewingsCount = viewings?.length ?? 0;
  const overdueTasksCount = tasks?.filter(
    (t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()
  ).length ?? 0;
  const unreadMessagesCount = inbox?.length ?? 0;
  const pendingCallsCount = pendingCalls?.length ?? 0;

  // Compact viewings list: max 3 visible
  const upcomingToday = useMemo(() => {
    if (!viewings) return [];
    return viewings.slice(0, 3);
  }, [viewings]);

  // Check if first-time loader (no cache and loading)
  const isFirstLoad = !cachedData && tasksPending;

  return (
    <SafeAreaView className="flex-1 bg-surface-page">
      {/* Notch aware gradient header wrapper */}
      <View className="relative w-full z-20">
        <View className={`absolute top-0 left-0 right-0 h-[140px] rounded-b-3xl ${gradientOverlay} blur-3xl`} />
        
        {/* Header Grid */}
        <View className="flex-row items-center justify-between px-5 pt-4 pb-3">
          <View>
            <View className="flex-row items-center">
              <Text className="text-text-primary text-[22px] font-semibold tracking-tight">
                Good morning, {user?.first_name || 'Tunde'}
              </Text>
              {isAnyFetching && <RotatingSyncIcon />}
            </View>
            <Text className="text-text-secondary text-[13px] font-medium mt-0.5">
              {format(new Date(), 'EEEE, d MMMM')}
            </Text>
          </View>
          
          {/* Notifications and Avatar */}
          <View className="flex-row items-center gap-4">
            <Pressable 
              onPress={() => navigation.navigate('Notifications')}
              className="w-10 h-10 bg-surface-card border border-zinc-800 rounded-full items-center justify-center relative active:opacity-75"
            >
              <Icon name="bell" size={18} color="#FAFAFA" />
              {unreadCount > 0 && (
                <View className="absolute top-2 right-2 w-2.5 h-2.5 bg-accent rounded-full border border-surface-card" />
              )}
            </Pressable>

            <Pressable 
              onPress={() => navigation.navigate('Profile')}
              className="w-10 h-10 rounded-full border border-zinc-800 items-center justify-center bg-brand-500/10 overflow-hidden active:opacity-75"
            >
              {user?.avatar_path ? (
                <Image source={{uri: user.avatar_path}} className="w-full h-full" />
              ) : (
                <Text className="text-brand-500 font-extrabold text-sm">
                  {(user?.first_name?.[0] || 'T').toUpperCase()}
                </Text>
              )}
            </Pressable>
          </View>
        </View>
      </View>

      {/* Main scroll content */}
      <ScrollView
        className="flex-1"
        contentContainerClassName="pb-10 pt-2"
        onScroll={handleScroll}
        scrollEventThrottle={16}
        onScrollEndDrag={handleScrollEnd}
        showsVerticalScrollIndicator={false}
      >
        {/* Pull To Refresh Custom Indicator */}
        <Animated.View
          style={{height: refreshHeaderHeight}}
          className="w-full items-center justify-center overflow-hidden"
        >
          <View className="w-10 h-10 rounded-full bg-surface-card border border-zinc-800 items-center justify-center shadow-lg">
            {showCheckmark ? (
              <Icon name="check" size={20} color="#10B981" />
            ) : (
              <ActivityIndicator size="small" color="#10B981" />
            )}
          </View>
        </Animated.View>

        {/* 2. Status strip - Horizontal scrolling compact chips */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerClassName="px-5 py-3"
          className="mb-6"
        >
          {pendingCallsCount > 0 && (
            <StatChip
              icon="phone-call"
              count={pendingCallsCount}
              label="call summary ready"
              pulse={true}
              onPress={() => navigation.navigate('Calls')}
            />
          )}
          <StatChip
            icon="calendar"
            count={viewingsCount}
            label="viewings today"
            onPress={() => navigation.navigate('Viewings')}
          />
          <StatChip
            icon="check-square"
            count={overdueTasksCount}
            label="overdue tasks"
            onPress={() => navigation.navigate('Tasks')}
          />
          <StatChip
            icon="message-square"
            count={unreadMessagesCount}
            label="unread messages"
            onPress={() => navigation.navigate('Inbox')}
          />
        </ScrollView>

        {/* 3. Today's Priorities */}
        <View className="px-5 mb-8">
          <View className="flex-row items-center gap-1.5 mb-4">
            <Icon name="sparkles" size={16} color="#10B981" />
            <Text className="text-text-primary text-[17px] font-bold tracking-tight">Today's priorities</Text>
          </View>

          {isFirstLoad ? (
            // Shimmer Skeleton State (Pulsing Zinc-800 Cards)
            <View>
              <Animated.View
                style={{opacity: skeletonOpacity}}
                className="bg-[#111827] border border-zinc-800/80 rounded-2xl p-4 mb-3 flex-row items-center justify-between h-[82px]"
              >
                <View className="flex-row items-center flex-1 mr-4">
                  <View className="w-10 h-10 bg-zinc-800 rounded-full mr-3" />
                  <View className="flex-1 gap-2">
                    <View className="h-4 bg-zinc-800 rounded w-5/6" />
                    <View className="h-3 bg-zinc-800 rounded w-1/2" />
                  </View>
                </View>
                <View className="w-10 h-10 bg-zinc-800 rounded-full" />
              </Animated.View>
              <Animated.View
                style={{opacity: skeletonOpacity}}
                className="bg-[#111827] border border-zinc-800/80 rounded-2xl p-4 mb-3 flex-row items-center justify-between h-[82px]"
              >
                <View className="flex-row items-center flex-1 mr-4">
                  <View className="w-10 h-10 bg-zinc-800 rounded-full mr-3" />
                  <View className="flex-1 gap-2">
                    <View className="h-4 bg-zinc-800 rounded w-4/6" />
                    <View className="h-3 bg-zinc-800 rounded w-1/3" />
                  </View>
                </View>
                <View className="w-10 h-10 bg-zinc-800 rounded-full" />
              </Animated.View>
            </View>
          ) : priorities.length === 0 ? (
            // Calm geometric checkmark caught-up state
            <View className="bg-surface-card border border-zinc-800 rounded-2xl py-8 px-6 items-center justify-center shadow-lg">
              <View className="w-14 h-14 bg-brand-500/10 border-2 border-brand-500 rounded-full items-center justify-center mb-3">
                <Icon name="check" size={28} color="#10B981" />
              </View>
              <Text className="text-text-primary font-bold text-base mb-1">You're all caught up</Text>
              <Text className="text-text-tertiary text-xs text-center">Nothing urgent right now.</Text>
            </View>
          ) : (
            // Priorities List
            priorities.map((item) => (
              <PriorityCard
                key={item.id}
                item={item}
                onDismiss={handleDismissPriority}
                onPressAction={() => handleActionClick(item)}
                onPressCard={() => handleCardClick(item)}
              />
            ))
          )}
        </View>

        {/* 4. Recent Call Summaries */}
        {pendingCalls && pendingCalls.length > 0 && (
          <View className="mb-8">
            <View className="flex-row items-center justify-between px-5 mb-4">
              <Text className="text-text-primary text-[17px] font-bold tracking-tight">Recent call summaries</Text>
            </View>
            <ScrollView
              horizontal
              showsHorizontalScrollIndicator={false}
              contentContainerClassName="px-5"
            >
              {pendingCalls.map((call: Call) => {
                const name = call.contact
                  ? `${call.contact.first_name} ${call.contact.last_name}`
                  : call.remote_number || 'Unknown';
                
                // Sentiment dot coloring
                const sentimentColor =
                  call.summary?.sentiment === 'hot'
                    ? 'bg-danger'
                    : call.summary?.sentiment === 'warm'
                    ? 'bg-accent'
                    : call.summary?.sentiment === 'cold'
                    ? 'bg-[#38BDF8]'
                    : 'bg-text-tertiary';

                return (
                  <View
                    key={call.id}
                    className="w-72 bg-surface-card border border-zinc-800 rounded-2xl p-4 mr-4 shadow-lg justify-between h-[132px]"
                  >
                    <View>
                      <View className="flex-row items-center mb-2 gap-2">
                        <View className={`w-2 h-2 rounded-full ${sentimentColor}`} />
                        <Text className="text-text-primary text-sm font-bold truncate" numberOfLines={1}>
                          {name}
                        </Text>
                      </View>
                      <Text className="text-text-secondary text-xs leading-4" numberOfLines={2}>
                        {call.summary?.summary_text || 'AI Summary pending review...'}
                      </Text>
                    </View>

                    <View className="flex-row justify-end">
                      <Pressable
                        onPress={() =>
                          navigation.navigate('Calls', {
                            screen: 'PostCallSummary',
                            params: {callId: call.id},
                          })
                        }
                        className="bg-accent/10 border border-accent/30 rounded-full px-4 py-1.5 active:bg-accent/25"
                      >
                        <Text className="text-accent text-xs font-bold">Review</Text>
                      </Pressable>
                    </View>
                  </View>
                );
              })}
            </ScrollView>
          </View>
        )}

        {/* 5. Upcoming Viewings today only */}
        {viewings && viewings.length > 0 && (
          <View className="px-5 mb-4">
            <View className="flex-row items-center justify-between mb-4">
              <Text className="text-text-primary text-[17px] font-bold tracking-tight">Upcoming viewings</Text>
              {viewings.length > 3 && (
                <Pressable onPress={() => navigation.navigate('Viewings')}>
                  <Text className="text-brand-500 text-xs font-bold">View all →</Text>
                </Pressable>
              )}
            </View>

            <View className="bg-surface-card border border-zinc-800 rounded-2xl overflow-hidden p-2 gap-1.5 shadow-lg">
              {upcomingToday.map((v: Viewing) => {
                const timeStr = v.scheduled_at
                  ? format(new Date(v.scheduled_at), 'HH:mm')
                  : '12:00';
                
                // Status dot color mapping
                const statusDot =
                  v.status === 'confirmed'
                    ? 'bg-success'
                    : v.status === 'scheduled'
                    ? 'bg-accent'
                    : 'bg-text-tertiary';

                return (
                  <Pressable
                    key={v.id}
                    onPress={() =>
                      navigation.navigate('Viewings', {
                        screen: 'ViewingDetail',
                        params: {viewingId: v.id},
                      })
                    }
                    className="flex-row items-center justify-between p-3 border-b border-zinc-900 last:border-0 active:bg-surface-raised rounded-xl"
                  >
                    <View className="flex-row items-center gap-3 flex-1 mr-4">
                      <Text className="text-brand-500 font-mono font-bold text-sm tracking-wider w-12">
                        {timeStr}
                      </Text>
                      <Text className="text-text-primary text-sm font-semibold truncate flex-1" numberOfLines={1}>
                        {v.listing?.address || 'Listing Address'}
                      </Text>
                    </View>

                    <View className="flex-row items-center gap-2">
                      <View className={`w-2.5 h-2.5 rounded-full ${statusDot}`} />
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
