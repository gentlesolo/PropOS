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
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

function SwipeableNotificationRow({
  children,
  onClear,
  tokens,
}: {
  children: React.ReactNode;
  onClear: () => void;
  tokens: ThemeTokens;
}) {
  const translateX = useRef(new Animated.Value(0)).current;
  const rowHeight = useRef(new Animated.Value(86)).current;
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
      onMoveShouldSetPanResponder: (_, g) => Math.abs(g.dx) > 10 && g.dx < 0,
      onPanResponderMove: (_, g) => { translateX.setValue(Math.max(-120, g.dx)); },
      onPanResponderRelease: (_, g) => {
        if (g.dx < -60) {
          Animated.spring(translateX, {toValue: -80, useNativeDriver: true}).start();
        } else {
          Animated.spring(translateX, {toValue: 0, useNativeDriver: true}).start();
        }
      },
    })
  ).current;

  const triggerClear = () => {
    Animated.parallel([
      Animated.timing(translateX, {toValue: -SCREEN_WIDTH, duration: 200, useNativeDriver: true}),
      Animated.timing(opacity, {toValue: 0, duration: 200, useNativeDriver: true}),
    ]).start(() => {
      Animated.timing(rowHeight, {toValue: 0, duration: 200, useNativeDriver: false}).start(() => {
        onClear();
      });
    });
  };

  return (
    <Animated.View
      style={{height: rowHeight, opacity, overflow: 'hidden', marginBottom: 8, position: 'relative'}}
      onLayout={handleLayout}
    >
      {/* Swipe-reveal clear panel */}
      <View
        style={{
          position: 'absolute',
          top: 0, bottom: 0, right: 0,
          width: 80,
          backgroundColor: tokens.surfaceRaised,
          borderRadius: 16,
          justifyContent: 'center',
          alignItems: 'center',
          borderWidth: 1,
          borderColor: tokens.borderDefault,
        }}
      >
        <Pressable onPress={triggerClear} style={{width: '100%', height: '100%', justifyContent: 'center', alignItems: 'center'}}>
          <Icon name="x" size={20} color={tokens.textSecondary} />
          <Text style={{fontSize: 10, color: tokens.textTertiary, fontWeight: '700', marginTop: 4}}>Clear</Text>
        </Pressable>
      </View>

      <Animated.View style={{transform: [{translateX}]}} {...panResponder.panHandlers}>
        {children}
      </Animated.View>
    </Animated.View>
  );
}

function NewItemAnimator({children}: {children: React.ReactNode}) {
  const heightVal = useRef(new Animated.Value(0)).current;
  const opacityVal = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.timing(heightVal, {toValue: 86, duration: 350, useNativeDriver: false}),
      Animated.timing(opacityVal, {toValue: 1, duration: 400, useNativeDriver: true}),
    ]).start();
  }, []);

  return (
    <Animated.View style={{height: heightVal, opacity: opacityVal, overflow: 'hidden'}}>
      {children}
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
    <Animated.View style={{transform: [{rotate: spin}], marginLeft: 8, justifyContent: 'center', alignItems: 'center'}}>
      <Icon name="loader" size={14} color={color} />
    </Animated.View>
  );
}

function SkeletonRow({tokens}: {tokens: ThemeTokens}) {
  const pulse = useRef(new Animated.Value(0.4)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(pulse, {toValue: 0.8, duration: 800, useNativeDriver: true}),
        Animated.timing(pulse, {toValue: 0.4, duration: 800, useNativeDriver: true}),
      ])
    ).start();
  }, []);

  return (
    <Animated.View
      style={{opacity: pulse, flexDirection: 'row', alignItems: 'center', borderRadius: 16, padding: 12, marginBottom: 8}}
    >
      <View style={{width: 40, height: 40, borderRadius: 20, backgroundColor: tokens.surfaceRaised, marginRight: 12}} />
      <View style={{flex: 1, gap: 6}}>
        <View style={{height: 12, width: 112, borderRadius: 6, backgroundColor: tokens.surfaceRaised}} />
        <View style={{height: 10, width: 192, borderRadius: 6, backgroundColor: tokens.surfaceRaised}} />
        <View style={{height: 8, width: 64, borderRadius: 4, backgroundColor: tokens.surfaceRaised, marginTop: 4}} />
      </View>
    </Animated.View>
  );
}

function NotificationsSkeleton({tokens}: {tokens: ThemeTokens}) {
  return (
    <ScrollView style={{flex: 1, paddingHorizontal: 16, paddingTop: 12}} scrollEnabled={false}>
      <View style={{marginBottom: 16}}>
        <View style={{height: 10, width: 48, borderRadius: 4, backgroundColor: tokens.surfaceRaised, marginBottom: 12}} />
        <SkeletonRow tokens={tokens} />
        <SkeletonRow tokens={tokens} />
        <SkeletonRow tokens={tokens} />
      </View>
      <View style={{marginBottom: 16}}>
        <View style={{height: 10, width: 64, borderRadius: 4, backgroundColor: tokens.surfaceRaised, marginBottom: 12}} />
        <SkeletonRow tokens={tokens} />
        <SkeletonRow tokens={tokens} />
      </View>
    </ScrollView>
  );
}

// Fixed semantic badge config — not theme-dependent for colors
const TYPE_CONFIG: Record<string, {icon: string; color: string; bg: string}> = {
  call_summary:  {icon: 'zap',           color: '#10B981', bg: '#10B9811A'},
  missed_call:   {icon: 'phone-missed',  color: '#F43F5E', bg: '#F43F5E1A'},
  overdue:       {icon: 'phone-missed',  color: '#F43F5E', bg: '#F43F5E1A'},
  lead:          {icon: 'user-plus',     color: '#0EA5E9', bg: '#0EA5E91A'},
  joined:        {icon: 'user-plus',     color: '#0EA5E9', bg: '#0EA5E91A'},
  message:       {icon: 'message-square',color: '#10B981', bg: '#10B9811A'},
  email:         {icon: 'message-square',color: '#10B981', bg: '#10B9811A'},
  chat:          {icon: 'message-square',color: '#10B981', bg: '#10B9811A'},
  task:          {icon: 'clock',         color: '#F59E0B', bg: '#F59E0B1A'},
  expiry:        {icon: 'clock',         color: '#F59E0B', bg: '#F59E0B1A'},
  deadline:      {icon: 'clock',         color: '#F59E0B', bg: '#F59E0B1A'},
  viewing:       {icon: 'calendar',      color: '#F59E0B', bg: '#F59E0B1A'},
  default:       {icon: 'bell',          color: '#0EA5E9', bg: '#0EA5E91A'},
};

function getTypeConfig(typeStr: string) {
  const type = typeStr.toLowerCase();
  for (const key of Object.keys(TYPE_CONFIG)) {
    if (key !== 'default' && type.includes(key)) return TYPE_CONFIG[key];
  }
  return TYPE_CONFIG.default;
}

export function NotificationsScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const queryClient = useQueryClient();

  const [permissionGranted, setPermissionGranted] = useState(true);
  const [bannerVisible, setBannerVisible] = useState(false);
  const [localDismissedIds, setLocalDismissedIds] = useState<string[]>([]);
  const [prevIds, setPrevIds] = useState<string[]>([]);
  const [newIds, setNewIds] = useState<string[]>([]);

  const {setUnreadCount} = useNotificationStore();

  const {data, isLoading, isFetching} = useQuery({
    queryKey: ['notifications'],
    queryFn: () => notificationsApi.list().then((r) => r.data),
  });

  useEffect(() => {
    const checkPermission = async () => {
      try {
        const authStatus = await messaging().hasPermission();
        const enabled =
          authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
          authStatus === messaging.AuthorizationStatus.PROVISIONAL;
        setPermissionGranted(enabled);
        setBannerVisible(!enabled);
      } catch {
        setPermissionGranted(false);
        setBannerVisible(true);
      }
    };
    checkPermission();
  }, []);

  useEffect(() => {
    if (data?.data) {
      const currentIds = data.data.map((n) => n.id);
      if (prevIds.length > 0) {
        const added = currentIds.filter((id) => !prevIds.includes(id));
        if (added.length > 0) setNewIds((prev) => [...prev, ...added]);
      }
      setPrevIds(currentIds);
      const unreadCount = data.data.filter((n) => !n.read_at).length;
      setUnreadCount(unreadCount);
    }
  }, [data]);

  const markReadMutation = useMutation({
    mutationFn: (id: string) => notificationsApi.markRead(id),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['notifications']});
      notificationsApi.unreadCount().then((r) => setUnreadCount(r.data.count));
    },
  });

  const markAllReadMutation = useMutation({
    mutationFn: () => notificationsApi.markAllRead(),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['notifications']});
      setUnreadCount(0);
    },
  });

  const handleClearRow = (id: string) => setLocalDismissedIds((prev) => [...prev, id]);

  const groupedNotifications = useMemo(() => {
    if (!data?.data) return {unread: [], read: []};
    const activeList = data.data.filter((n) => !localDismissedIds.includes(n.id));
    return {unread: activeList.filter((n) => !n.read_at), read: activeList.filter((n) => n.read_at)};
  }, [data, localDismissedIds]);

  const hasUnread = groupedNotifications.unread.length > 0;

  const handleRowTap = (notification: AppNotification) => {
    if (!notification.read_at) markReadMutation.mutate(notification.id);
    const type = notification.type.toLowerCase();
    const url = notification.action_url || '';
    const matchId = url.match(/\d+/);
    const resolvedId = matchId ? Number(matchId[0]) : 1;

    if (type.includes('call_summary_ready')) navigation.navigate('PostCallSummary', {callId: resolvedId});
    else if (type.includes('missed_call')) navigation.navigate('Calls');
    else if (type.includes('lead')) navigation.navigate('ContactDetail', {contactId: resolvedId});
    else if (type.includes('message') || type.includes('email')) navigation.navigate('Inbox', {screen: 'Thread', params: {contactId: resolvedId}});
    else if (type.includes('task')) navigation.navigate('Tasks');
    else if (type.includes('viewing')) navigation.navigate('Viewings', {screen: 'ViewingDetail', params: {viewingId: resolvedId}});
    else if (type.includes('lease') || type.includes('tenant')) navigation.navigate('Tenants');
    else if (type.includes('finance') || type.includes('rent') || type.includes('expense')) navigation.navigate('Finance');
    else navigation.navigate('Home');
  };

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
    queryClient.setQueryData(['notifications'], (old: any) => ({
      ...old,
      data: [mockNotification, ...(old?.data ?? [])],
    }));
  };

  const renderNotificationRow = (notification: AppNotification, isRead: boolean) => {
    const config = getTypeConfig(notification.type);
    return (
      <SwipeableNotificationRow
        key={notification.id}
        onClear={() => handleClearRow(notification.id)}
        tokens={tokens}
      >
        <Pressable
          onPress={() => handleRowTap(notification)}
          style={({pressed}) => ({
            flexDirection: 'row',
            alignItems: 'center',
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            backgroundColor: tokens.surfaceCard,
            borderRadius: 16,
            padding: 12,
            opacity: pressed ? 0.85 : isRead ? 0.7 : 1,
          })}
        >
          {/* Icon circle */}
          <View
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: isRead ? tokens.surfaceRaised : config.bg,
              alignItems: 'center',
              justifyContent: 'center',
              marginRight: 12,
              borderWidth: 1,
              borderColor: tokens.borderSubtle,
            }}
          >
            <Icon name={config.icon} size={16} color={isRead ? tokens.textTertiary : config.color} />
          </View>

          {/* Text */}
          <View style={{flex: 1, marginRight: 8}}>
            <Text style={{color: isRead ? tokens.textSecondary : tokens.textPrimary, fontSize: 12, fontWeight: isRead ? '600' : '800', lineHeight: 20}}>
              {notification.title}
            </Text>
            <Text style={{color: isRead ? tokens.textTertiary : tokens.textSecondary, fontSize: 11, lineHeight: 16, marginTop: 2}}>
              {notification.body}
            </Text>
            <Text style={{color: tokens.textTertiary, fontSize: 9, fontFamily: 'monospace', fontWeight: '700', marginTop: 6}}>
              {formatDistanceToNow(new Date(notification.created_at), {addSuffix: true})}
            </Text>
          </View>

          {/* Unread dot */}
          {!isRead && (
            <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: tokens.brandPrimary, marginRight: 4}} />
          )}
        </Pressable>
      </SwipeableNotificationRow>
    );
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 20,
          paddingTop: 16,
          paddingBottom: 16,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          flexDirection: 'row',
          justifyContent: 'space-between',
          alignItems: 'center',
          zIndex: 10,
        }}
      >
        <View style={{flexDirection: 'row', alignItems: 'center'}}>
          <Pressable onPress={() => navigation.goBack()} style={{marginRight: 12, padding: 4}}>
            <Icon name="arrow-left" size={20} color={tokens.textPrimary} />
          </Pressable>
          <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '800', letterSpacing: -0.5}}>Notifications</Text>
          {isFetching && !isLoading && <RotatingSyncIcon color={tokens.brandPrimary} />}
        </View>

        {hasUnread && (
          <Pressable
            onPress={() => markAllReadMutation.mutate()}
            style={{backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, paddingHorizontal: 14, paddingVertical: 6, borderRadius: 999}}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>Mark all read</Text>
          </Pressable>
        )}
      </View>

      {/* Permissions banner */}
      {bannerVisible && (
        <View style={{backgroundColor: '#F59E0B1A', borderBottomWidth: 1, borderBottomColor: '#F59E0B33', paddingHorizontal: 20, paddingVertical: 14, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
          <View style={{flexDirection: 'row', alignItems: 'center', flex: 1, marginRight: 12}}>
            <Icon name="alert-triangle" size={16} color="#F59E0B" />
            <Text style={{color: '#F59E0B', fontWeight: '700', fontSize: 12, marginLeft: 8, flex: 1, lineHeight: 20}}>
              Notifications are off — you'll miss incoming calls and urgent alerts
            </Text>
          </View>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
            <Pressable onPress={() => Linking.openSettings()} style={{backgroundColor: '#F59E0B', borderRadius: 8, paddingHorizontal: 12, paddingVertical: 6}}>
              <Text style={{color: '#ffffff', fontWeight: '800', fontSize: 10}}>Enable</Text>
            </Pressable>
            <Pressable onPress={() => setBannerVisible(false)} style={{padding: 6}}>
              <Icon name="x" size={14} color="#F59E0B" />
            </Pressable>
          </View>
        </View>
      )}

      {/* Content */}
      {isLoading ? (
        <NotificationsSkeleton tokens={tokens} />
      ) : groupedNotifications.unread.length === 0 && groupedNotifications.read.length === 0 ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingHorizontal: 32}}>
          <View style={{width: 64, height: 64, backgroundColor: `${tokens.brandPrimary}1A`, borderRadius: 32, alignItems: 'center', justifyContent: 'center', marginBottom: 16, borderWidth: 1, borderColor: `${tokens.brandPrimary}1A`}}>
            <Icon name="bell" size={24} color={tokens.brandPrimary} />
          </View>
          <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 4}}>No notifications yet</Text>
          <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center'}}>
            We'll notify you when you receive missed calls, tasks, lead allocations, and other system actions.
          </Text>
          <Pressable
            onPress={simulateNotification}
            style={{marginTop: 24, backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 12, paddingHorizontal: 16, paddingVertical: 10}}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>+ Simulate Real-time Alert</Text>
          </Pressable>
        </View>
      ) : (
        <ScrollView style={{flex: 1, paddingHorizontal: 16, paddingTop: 12}} showsVerticalScrollIndicator={false}>
          {/* Unread section */}
          {groupedNotifications.unread.length > 0 && (
            <View style={{marginBottom: 16}}>
              <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 2, marginBottom: 8, marginLeft: 4}}>New</Text>
              {groupedNotifications.unread.map((notification) => {
                const isNew = newIds.includes(notification.id);
                const row = renderNotificationRow(notification, false);
                return isNew ? <NewItemAnimator key={notification.id}>{row}</NewItemAnimator> : row;
              })}
            </View>
          )}

          {/* Read section */}
          {groupedNotifications.read.length > 0 && (
            <View style={{marginBottom: 24}}>
              <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 2, marginBottom: 8, marginLeft: 4}}>Earlier</Text>
              {groupedNotifications.read.map((notification) => renderNotificationRow(notification, true))}
            </View>
          )}

          {/* Simulate button */}
          <Pressable
            onPress={simulateNotification}
            style={({pressed}) => ({marginVertical: 12, backgroundColor: tokens.surfaceRaised, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 12, paddingVertical: 12, alignItems: 'center', opacity: pressed ? 0.75 : 1})}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>+ Simulate Real-time Alert</Text>
          </Pressable>

          {/* Notification settings link */}
          <View style={{marginTop: 32, marginBottom: 40, alignItems: 'center'}}>
            <Pressable onPress={() => navigation.navigate('Profile')} style={{paddingVertical: 8, paddingHorizontal: 16}}>
              <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 12, textAlign: 'center'}}>
                Manage notification preferences
              </Text>
            </Pressable>
          </View>
        </ScrollView>
      )}
    </SafeAreaView>
  );
}
