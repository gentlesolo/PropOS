import React from 'react';
import {Text, View, Vibration} from 'react-native';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/Feather';
import {HomeScreen} from '../screens/home/HomeScreen';
import {ContactsStack} from './stacks/ContactsStack';
import {MessagingStack} from './stacks/MessagingStack';
import {TasksScreen} from '../screens/tasks/TasksScreen';
import {MoreScreen} from '../screens/more/MoreScreen';
import {useAuthStore} from '../store/authStore';
import {useNotificationStore} from '../store/notificationStore';
import {useRealtime} from '../hooks/useRealtime';
import {useQuery} from '@tanstack/react-query';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import {tasksApi} from '../api/tasks';
import {callsApi} from '../api/calls';
import {messagingApi} from '../api/messaging';
import {isToday} from 'date-fns';
import {Task} from '../types';
import {useTheme} from '../theme/ThemeProvider';

export type TabParamList = {
  Home:     undefined;
  Contacts: undefined;
  Inbox:    undefined;
  Tasks:    undefined;
  More:     undefined;
};

const Tab = createBottomTabNavigator<TabParamList>();

function TabIcon({
  name,
  focused,
  badgeCount,
  tokens,
}: {
  name: string;
  focused: boolean;
  badgeCount?: number;
  tokens: ReturnType<typeof useTheme>['tokens'];
}) {
  return (
    <View className="items-center justify-center pt-2 relative w-12 h-10">
      {focused && (
        <View className="absolute top-0 w-1.5 h-1.5 bg-brand-500 rounded-full" />
      )}
      <Icon
        name={name}
        size={20}
        color={focused ? tokens.brandPrimary : tokens.textTertiary}
      />
      {badgeCount !== undefined && badgeCount > 0 && (
        <View className="absolute top-0 right-0 bg-brand-500 rounded-full px-1.5 py-0.5 min-w-[16px] items-center justify-center">
          <Text className="text-white text-[8px] font-black leading-3">
            {badgeCount}
          </Text>
        </View>
      )}
    </View>
  );
}

export function TabNavigator() {
  const {tokens} = useTheme();
  const insets = useSafeAreaInsets();

  useRealtime();

  const {unreadCount: notificationsCount} = useNotificationStore();

  const {data: inbox} = useQuery({
    queryKey: ['inbox'],
    queryFn: () => messagingApi.inbox().then((r) => r.data),
    staleTime: 60_000,
  });

  const {data: tasks} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then((r) => r.data),
  });

  const {data: pendingCalls} = useQuery({
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
  });

  const inboxBadge = inbox?.length ?? 0;

  const tasksBadge =
    tasks?.filter(
      (t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()
    ).length ?? 0;

  const pendingCallsCount = pendingCalls?.length ?? 0;
  const moreBadge = notificationsCount + pendingCallsCount;

  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: tokens.tabBarBg,
          borderTopColor: tokens.borderDefault,
          borderTopWidth: 1,
          height: 60 + insets.bottom,
          paddingBottom: insets.bottom > 0 ? insets.bottom - 4 : 8,
          ...tokens.shadowSm,
        },
        tabBarActiveTintColor: tokens.brandPrimary,
        tabBarInactiveTintColor: tokens.textTertiary,
        tabBarLabelStyle: {fontSize: 10, fontWeight: '700', marginTop: 4},
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({focused}) => (
            <TabIcon name="home" focused={focused} tokens={tokens} />
          ),
        }}
      />
      <Tab.Screen
        name="Contacts"
        component={ContactsStack}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: 'Contacts',
          tabBarIcon: ({focused}) => (
            <TabIcon name="users" focused={focused} tokens={tokens} />
          ),
        }}
      />
      <Tab.Screen
        name="Inbox"
        component={MessagingStack}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: 'Inbox',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="message-square"
              focused={focused}
              badgeCount={inboxBadge}
              tokens={tokens}
            />
          ),
        }}
      />
      <Tab.Screen
        name="Tasks"
        component={TasksScreen}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: 'Tasks',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="check-square"
              focused={focused}
              badgeCount={tasksBadge}
              tokens={tokens}
            />
          ),
        }}
      />
      <Tab.Screen
        name="More"
        component={MoreScreen}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: 'More',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="grid"
              focused={focused}
              badgeCount={moreBadge}
              tokens={tokens}
            />
          ),
        }}
      />
    </Tab.Navigator>
  );
}
