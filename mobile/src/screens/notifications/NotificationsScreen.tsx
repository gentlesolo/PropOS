import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  ActivityIndicator,
  Animated,
  Dimensions,
  Linking,
  PanResponder,
  Pressable,
  ScrollView,
  Text,
  View,
  useColorScheme,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {notificationsApi, AppNotification} from '../../api/notifications';
import {useNotificationStore} from '../../store/notificationStore';
import Icon from 'react-native-vector-icons/Feather';
import {formatDistanceToNow} from 'date-fns';
import messaging from '@react-native-firebase/messaging';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

// 1. Swipeable row using standard PanResponder (no external gesture library needed)
function SwipeableNotificationRow({
  children,
  onClear,
  isDarkMode,
}: {
  children: React.ReactNode;
  onClear: () => void;
  isDarkMode: boolean;
}) {
  const translateX = useRef(new Animated.Value(0)).current;
  const rowHeight = useRef(new Animated.Value(86)).current; // Default estimate height
  const opacity = useRef(new Animated.Value(1)).current;
  const [measuredHeight, setMeasuredHeight] = useState<number | null>(null);

  const handleLayout = (e: any) => {
    if (measuredHeight === null) {
      const {height} = e.nativeEvent.layout;
      setMeasuredHeight(height);
      rowHeight.setValue(height);
    }
  };

  const panResponder = useRef(
    PanResponder.create({
      onMoveShouldSetPanResponder: (_, gestureState) => {
        // Intercept horizontal swipes going left
        return Math.abs(gestureState.dx) > 10 && gestureState.dx < 0;
      },
      onPanResponderMove: (_, gestureState) => {
        const tx = Math.max(-120, gestureState.dx);
        translateX.setValue(tx);
      },
      onPanResponderRelease: (_, gestureState) => {
        if (gestureState.dx < -60) {
          // Snap open to show clear action
          Animated.spring(translateX, {
            toValue: -80,
            useNativeDriver: true,
          }).start();
        } else {
          // Snap closed
          Animated.spring(translateX, {
            toValue: 0,
            useNativeDriver: true,
          }).start();
        }
      },
    })
  ).current;

  const triggerClear = () => {
    Animated.parallel([
      Animated.timing(translateX, {
        toValue: -SCREEN_WIDTH,
        duration: 200,
        useNativeDriver: true,
      }),
      Animated.timing(opacity, {
        toValue: 0,
        duration: 200,
        useNativeDriver: true,
      }),
    ]).start(() => {
      Animated.timing(rowHeight, {
        toValue: 0,
        duration: 200,
        useNativeDriver: false,
      }).start(() => {
        onClear();
      });
    });
  };

  return (
    <Animated.View
      style={{height: rowHeight, opacity, overflow: 'hidden'}}
      className="mb-2 relative"
      onLayout={handleLayout}
    >
      {/* Background Clear Panel */}
      <View className="absolute inset-y-0 right-0 w-20 bg-zinc-800 rounded-2xl justify-center items-center">
        <Pressable
          onPress={triggerClear}
          className="w-full h-full justify-center items-center active:opacity-85"
        >
          <Icon name="x" size={20} color="#fff" />
          <Text className="text-[10px] text-white/80 font-bold mt-1">Clear</Text>
        </Pressable>
      </View>

      {/* Foreground Row */}
      <Animated.View
        style={{transform: [{translateX}]}}
        {...panResponder.panHandlers}
      >
        {children}
      </Animated.View>
    </Animated.View>
  );
}

// 2. Real-time New Item slide-down + fade animator
function NewItemAnimator({children}: {children: React.ReactNode}) {
  const heightVal = useRef(new Animated.Value(0)).current;
  const opacityVal = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(heightVal, {
        toValue: 86, // Target estimated row height
        duration: 350,
        useNativeDriver: false,
      }),
      Animated.timing(opacityVal, {
        toValue: 1,
        duration: 400,
        useNativeDriver: true,
      }),
    ]).start();
  }, []);

  return (
    <Animated.View style={{height: heightVal, opacity: opacityVal, overflow: 'hidden'}}>
      {children}
    </Animated.View>
  );
}

// 3. Animated Rotating Sync Icon
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
    <Animated.View style={{transform: [{rotate: spin}]}} className="ml-2 justify-center items-center">
      <Icon name="loader" size={14} color="#10B981" />
    </Animated.View>
  );
}

// 4. Custom pulsate Skeleton Rows
function SkeletonRow({isDarkMode}: {isDarkMode: boolean}) {
  const pulse = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, {toValue: 0.8, duration: 800, useNativeDriver: true}),
        Animated.timing(pulse, {toValue: 0.4, duration: 800, useNativeDriver: true}),
      ])
    ).start();
  }, []);

  const bgStyle = isDarkMode ? 'bg-zinc-800' : 'bg-zinc-200';

  return (
    <Animated.View
      style={{opacity: pulse}}
      className="flex-row items-center border border-transparent rounded-2xl p-3 mb-2"
    >
      <View className={`w-10 h-10 rounded-full ${bgStyle} mr-3`} />
      <View className="flex-1 gap-1.5">
        <View className={`h-3 w-28 rounded ${bgStyle}`} />
        <View className={`h-2.5 w-48 rounded ${bgStyle}`} />
        <View className={`h-2 w-16 rounded mt-1 ${bgStyle}`} />
      </View>
    </Animated.View>
  );
}

function NotificationsSkeleton({isDarkMode}: {isDarkMode: boolean}) {
  return (
    <ScrollView className="flex-1 px-4 pt-3" scrollEnabled={false}>
      <View className="mb-4">
        <View className={`h-2.5 w-12 rounded mb-3 ${isDarkMode ? 'bg-zinc-800' : 'bg-zinc-200'}`} />
        <SkeletonRow isDarkMode={isDarkMode} />
        <SkeletonRow isDarkMode={isDarkMode} />
        <SkeletonRow isDarkMode={isDarkMode} />
      </View>
      <View className="mb-4">
        <View className={`h-2.5 w-16 rounded mb-3 ${isDarkMode ? 'bg-zinc-800' : 'bg-zinc-200'}`} />
        <SkeletonRow isDarkMode={isDarkMode} />
        <SkeletonRow isDarkMode={isDarkMode} />
      </View>
    </ScrollView>
  );
}

export function NotificationsScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';

  // Permission & Notification state
  const [permissionGranted, setPermissionGranted] = useState(true);
  const [bannerVisible, setBannerVisible] = useState(false);
  const [localDismissedIds, setLocalDismissedIds] = useState<string[]>([]);
  const [prevIds, setPrevIds] = useState<string[]>([]);
  const [newIds, setNewIds] = useState<string[]>([]);

  // Store
  const {setUnreadCount} = useNotificationStore();

  // Query notifications list
  const {data, isLoading, refetch, isFetching} = useQuery({
    queryKey: ['notifications'],
    queryFn: () => notificationsApi.list().then(r => r.data),
  });

  // Check FCM permission status on screen open
  useEffect(() => {
    const checkPermission = async () => {
      try {
        const authStatus = await messaging().hasPermission();
        const enabled =
          authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
          authStatus === messaging.AuthorizationStatus.PROVISIONAL;
        setPermissionGranted(enabled);
        setBannerVisible(!enabled);
      } catch (err) {
        setPermissionGranted(false);
        setBannerVisible(true);
      }
    };
    checkPermission();
  }, []);

  // Sync incoming real-time notifications with slide down list animation
  useEffect(() => {
    if (data?.data) {
      const currentIds = data.data.map(n => n.id);
      if (prevIds.length > 0) {
        const added = currentIds.filter(id => !prevIds.includes(id));
        if (added.length > 0) {
          setNewIds(prev => [...prev, ...added]);
        }
      }
      setPrevIds(currentIds);

      // Sync tab bar count
      const unreadCount = data.data.filter(n => !n.read_at).length;
      setUnreadCount(unreadCount);
    }
  }, [data]);

  // Mutations
  const markReadMutation = useMutation({
    mutationFn: (id: string) => notificationsApi.markRead(id),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['notifications']});
      notificationsApi.unreadCount().then(r => setUnreadCount(r.data.count));
    },
  });

  const markAllReadMutation = useMutation({
    mutationFn: () => notificationsApi.markAllRead(),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['notifications']});
      setUnreadCount(0);
    },
  });

  // Swipe clear handler (pure frontend dismiss, doesn't mutate db)
  const handleClearRow = (id: string) => {
    setLocalDismissedIds(prev => [...prev, id]);
  };

  // Group notifications into New (unread) and Earlier (read)
  const groupedNotifications = useMemo(() => {
    if (!data?.data) return {unread: [], read: []};
    
    // Filter out rows cleared locally by swipe
    const activeList = data.data.filter(n => !localDismissedIds.includes(n.id));

    return {
      unread: activeList.filter(n => !n.read_at),
      read: activeList.filter(n => n.read_at),
    };
  }, [data, localDismissedIds]);

  const hasUnread = groupedNotifications.unread.length > 0;

  // Notification click handler (Mark read + deep link redirect)
  const handleRowTap = (notification: AppNotification) => {
    if (!notification.read_at) {
      markReadMutation.mutate(notification.id);
    }

    const type = notification.type.toLowerCase();
    const url = notification.action_url || '';
    const matchId = url.match(/\d+/);
    const resolvedId = matchId ? Number(matchId[0]) : 1;

    // Resolve navigation targets
    if (type.includes('call_summary_ready')) {
      navigation.navigate('PostCallSummary', {callId: resolvedId});
    } else if (type.includes('missed_call')) {
      navigation.navigate('Calls');
    } else if (type.includes('lead')) {
      navigation.navigate('ContactDetail', {contactId: resolvedId});
    } else if (type.includes('message') || type.includes('email')) {
      navigation.navigate('Inbox', {
        screen: 'Thread',
        params: {contactId: resolvedId},
      });
    } else if (type.includes('task')) {
      navigation.navigate('Tasks');
    } else if (type.includes('viewing')) {
      navigation.navigate('Viewings', {
        screen: 'ViewingDetail',
        params: {viewingId: resolvedId},
      });
    } else if (type.includes('lease') || type.includes('tenant')) {
      navigation.navigate('Tenants');
    } else if (type.includes('finance') || type.includes('rent') || type.includes('expense')) {
      navigation.navigate('Finance');
    } else {
      navigation.navigate('Home');
    }
  };

  // Mappings for Type Icon and Colors
  const getTypeConfig = (typeStr: string) => {
    const type = typeStr.toLowerCase();
    if (type.includes('call_summary')) {
      return {icon: 'zap', color: '#10B981', bg: 'bg-emerald-500/10'}; // sparkle/AI info
    }
    if (type.includes('missed_call') || type.includes('overdue')) {
      return {icon: 'phone-missed', color: '#F43F5E', bg: 'bg-rose-500/10'}; // danger
    }
    if (type.includes('lead') || type.includes('joined')) {
      return {icon: 'user-plus', color: '#0EA5E9', bg: 'bg-sky-500/10'}; // info
    }
    if (type.includes('message') || type.includes('email') || type.includes('chat')) {
      return {icon: 'message-square', color: '#10B981', bg: 'bg-emerald-500/10'}; // chat message
    }
    if (type.includes('task') || type.includes('expiry') || type.includes('deadline')) {
      return {icon: 'clock', color: '#F59E0B', bg: 'bg-amber-500/10'}; // task due
    }
    if (type.includes('viewing')) {
      return {icon: 'calendar', color: '#F59E0B', bg: 'bg-amber-500/10'}; // calendar reminder
    }
    return {icon: 'bell', color: '#0EA5E9', bg: 'bg-sky-500/10'}; // generic
  };

  // Simulated push notification for developer verification
  const simulateNotification = () => {
    const mockNotification: AppNotification = {
      id: `sim-${Date.now()}`,
      type: 'call_summary_ready',
      title: 'Call Summary Ready',
      body: 'AI has analyzed your call with Sarah Obi. Summary and 3 action items are ready.',
      action_url: '/calls/1',
      severity: 'info',
      read_at: null,
      created_at: new Date().toISOString(),
    };

    // Trigger local state updates to simulate realtime list updates
    queryClient.setQueryData(['notifications'], (old: any) => {
      const list = old?.data ? [...old.data] : [];
      return {
        ...old,
        data: [mockNotification, ...list],
      };
    });
  };

  // Styling helpers
  const styles = {
    bgPage: isDarkMode ? 'bg-[#030712]' : 'bg-slate-50',
    bgCard: isDarkMode ? 'bg-[#090d16]' : 'bg-white',
    borderCard: isDarkMode ? 'border-zinc-800/80' : 'border-slate-100',
    textPrimary: isDarkMode ? 'text-text-primary' : 'text-slate-900',
    textSecondary: isDarkMode ? 'text-text-secondary' : 'text-slate-500',
    textTertiary: isDarkMode ? 'text-text-tertiary' : 'text-slate-400',
    borderHeader: isDarkMode ? 'border-zinc-900' : 'border-slate-200/60',
  };

  return (
    <SafeAreaView className={`flex-1 ${styles.bgPage}`}>
      {/* Header */}
      <View className={`px-5 pt-4 pb-4 ${styles.bgCard} border-b ${styles.borderHeader} flex-row justify-between items-center z-10`}>
        <View className="flex-row items-center">
          <Pressable onPress={() => navigation.goBack()} className="mr-3 p-1 active:opacity-75">
            <Icon name="arrow-left" size={20} color={isDarkMode ? '#FAFAFA' : '#0F172A'} />
          </Pressable>
          <Text className={`${styles.textPrimary} text-2xl font-extrabold tracking-tight`}>Notifications</Text>
          {isFetching && !isLoading && <RotatingSyncIcon />}
        </View>

        {hasUnread && (
          <Pressable
            onPress={() => markAllReadMutation.mutate()}
            className="bg-brand-500/10 border border-brand-500/20 px-3.5 py-1.5 rounded-full active:bg-brand-500/20"
          >
            <Text className="text-brand-500 font-extrabold text-xs">Mark all read</Text>
          </Pressable>
        )}
      </View>

      {/* Permissions Banner */}
      {bannerVisible && (
        <View className="bg-amber-500/10 border-b border-amber-500/20 px-5 py-3.5 flex-row justify-between items-center">
          <View className="flex-row items-center flex-1 mr-3">
            <Icon name="alert-triangle" size={16} color="#F59E0B" />
            <Text className="text-accent font-bold text-xs ml-2 flex-1 leading-5">
              Notifications are off — you'll miss incoming calls and urgent alerts
            </Text>
          </View>
          <View className="flex-row items-center gap-2">
            <Pressable
              onPress={() => Linking.openSettings()}
              className="bg-amber-500 rounded-lg px-3 py-1.5 active:bg-amber-600"
            >
              <Text className="text-white font-extrabold text-[10px]">Enable</Text>
            </Pressable>
            <Pressable
              onPress={() => setBannerVisible(false)}
              className="p-1.5 active:opacity-70"
            >
              <Icon name="x" size={14} color="#F59E0B" />
            </Pressable>
          </View>
        </View>
      )}

      {/* Notifications list */}
      {isLoading ? (
        <NotificationsSkeleton isDarkMode={isDarkMode} />
      ) : groupedNotifications.unread.length === 0 && groupedNotifications.read.length === 0 ? (
        <View className="flex-1 items-center justify-center px-8">
          <View className="w-16 h-16 bg-brand-500/10 rounded-full items-center justify-center mb-4 border border-brand-500/10">
            <Icon name="bell" size={24} color="#10B981" />
          </View>
          <Text className={`${styles.textPrimary} text-base font-bold mb-1`}>No notifications yet</Text>
          <Text className={`${styles.textSecondary} text-xs text-center`}>
            We'll notify you when you receive missed calls, tasks, lead allocations, and other system actions.
          </Text>

          {/* Dev Sim Button */}
          <Pressable
            onPress={simulateNotification}
            className="mt-6 bg-zinc-900/60 border border-zinc-800 rounded-xl px-4 py-2.5 active:bg-zinc-800"
          >
            <Text className="text-brand-500 font-extrabold text-xs">+ Simulate Real-time Alert</Text>
          </Pressable>
        </View>
      ) : (
        <ScrollView className="flex-1 px-4 pt-3" showsVerticalScrollIndicator={false}>
          {/* Unread Section */}
          {groupedNotifications.unread.length > 0 && (
            <View className="mb-4">
              <Text className={`${styles.textTertiary} text-[10px] font-extrabold uppercase tracking-wider mb-2 ml-1`}>
                New
              </Text>
              {groupedNotifications.unread.map((notification) => {
                const config = getTypeConfig(notification.type);
                const isNew = newIds.includes(notification.id);

                const RowContent = (
                  <SwipeableNotificationRow
                    key={notification.id}
                    onClear={() => handleClearRow(notification.id)}
                    isDarkMode={isDarkMode}
                  >
                    <Pressable
                      onPress={() => handleRowTap(notification)}
                      className={`flex-row items-center border ${styles.borderCard} ${styles.bgCard} rounded-2xl p-3 shadow-sm active:opacity-90`}
                    >
                      {/* Left Circle Icon */}
                      <View className={`w-10 h-10 rounded-full ${config.bg} items-center justify-center mr-3 border border-brand-500/10`}>
                        <Icon name={config.icon} size={16} color={config.color} />
                      </View>

                      {/* Middle Text Details */}
                      <View className="flex-1 mr-2">
                        <Text className={`${styles.textPrimary} text-xs font-extrabold leading-5`}>
                          {notification.title}
                        </Text>
                        <Text className={`${styles.textSecondary} text-[11px] leading-4 mt-0.5`}>
                          {notification.body}
                        </Text>
                        <Text className={`${styles.textTertiary} text-[9px] font-bold font-mono mt-1.5`}>
                          {formatDistanceToNow(new Date(notification.created_at), {addSuffix: true})}
                        </Text>
                      </View>

                      {/* Right Unread Indicator Dot */}
                      <View className="w-2 h-2 rounded-full bg-brand-500 mr-1" />
                    </Pressable>
                  </SwipeableNotificationRow>
                );

                return isNew ? (
                  <NewItemAnimator key={notification.id}>{RowContent}</NewItemAnimator>
                ) : (
                  RowContent
                );
              })}
            </View>
          )}

          {/* Read Section */}
          {groupedNotifications.read.length > 0 && (
            <View className="mb-6">
              <Text className={`${styles.textTertiary} text-[10px] font-extrabold uppercase tracking-wider mb-2 ml-1`}>
                Earlier
              </Text>
              {groupedNotifications.read.map((notification) => {
                const config = getTypeConfig(notification.type);
                return (
                  <SwipeableNotificationRow
                    key={notification.id}
                    onClear={() => handleClearRow(notification.id)}
                    isDarkMode={isDarkMode}
                  >
                    <Pressable
                      onPress={() => handleRowTap(notification)}
                      className={`flex-row items-center border ${styles.borderCard} ${styles.bgCard} rounded-2xl p-3 opacity-75 shadow-sm active:opacity-90`}
                    >
                      {/* Left Circle Icon */}
                      <View className={`w-10 h-10 rounded-full ${isDarkMode ? 'bg-zinc-900/50' : 'bg-slate-100'} items-center justify-center mr-3 border border-zinc-800/10`}>
                        <Icon name={config.icon} size={16} color={isDarkMode ? '#71717a' : '#94a3b8'} />
                      </View>

                      {/* Middle Text Details */}
                      <View className="flex-1 mr-2">
                        <Text className={`${styles.textSecondary} text-xs font-semibold leading-5`}>
                          {notification.title}
                        </Text>
                        <Text className={`${styles.textTertiary} text-[11px] leading-4 mt-0.5`}>
                          {notification.body}
                        </Text>
                        <Text className={`${styles.textTertiary} text-[9px] font-mono mt-1.5`}>
                          {formatDistanceToNow(new Date(notification.created_at), {addSuffix: true})}
                        </Text>
                      </View>
                    </Pressable>
                  </SwipeableNotificationRow>
                );
              })}
            </View>
          )}

          {/* Dev Sim Button (Visible at the bottom of the list too) */}
          <Pressable
            onPress={simulateNotification}
            className="my-3 bg-zinc-900/40 border border-zinc-850 rounded-xl py-3 items-center justify-center active:bg-zinc-800/60"
          >
            <Text className="text-brand-500 font-extrabold text-xs">+ Simulate Real-time Alert</Text>
          </Pressable>

          {/* Notification settings link */}
          <View className="mt-8 mb-10 items-center">
            <Pressable
              onPress={() => navigation.navigate('Profile')}
              className="py-2 px-4 border border-zinc-800/10 dark:border-zinc-800 rounded-full active:opacity-75"
            >
              <Text className="text-brand-500 font-bold text-xs text-center">
                Manage notification preferences
              </Text>
            </Pressable>
          </View>
        </ScrollView>
      )}
    </SafeAreaView>
  );
}
