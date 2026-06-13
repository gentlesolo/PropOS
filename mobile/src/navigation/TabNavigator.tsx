import React from 'react';
import {Text, View, useColorScheme} from 'react-native';
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
import {tasksApi} from '../api/tasks';
import {callsApi} from '../api/calls';
import {messagingApi} from '../api/messaging';
import {isToday} from 'date-fns';
import {Task} from '../types';

export type TabParamList = {
  Home:     undefined;
  Contacts: undefined;
  Inbox:    undefined; // renamed from Messages to Inbox
  Tasks:    undefined;
  More:     undefined;
};

const Tab = createBottomTabNavigator<TabParamList>();

function TabIcon({
  name,
  focused,
  badgeCount,
  isDarkMode,
}: {
  name: string;
  focused: boolean;
  badgeCount?: number;
  isDarkMode: boolean;
}) {
  return (
    <View className="items-center justify-center pt-2 relative w-12 h-10">
      {/* Active tab: small emerald dot indicator above the icon */}
      {focused && (
        <View className="absolute top-0 w-1.5 h-1.5 bg-brand-500 rounded-full" />
      )}
      
      <Icon 
        name={name} 
        size={20} 
        color={focused ? '#10b981' : isDarkMode ? '#71717a' : '#94a3b8'} 
      />

      {/* Badge counts: small emerald circle with number, top-right of icon */}
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
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';

  // Wire up real-time push → query invalidation for the entire authenticated session
  useRealtime();

  // Retrieve unread notifications from local store
  const {unreadCount: notificationsCount} = useNotificationStore();

  // 1. Inbox query for unread messages count
  const {data: inbox} = useQuery({
    queryKey: ['inbox'],
    queryFn: () => messagingApi.inbox().then((r) => r.data),
    staleTime: 60_000,
  });

  // 2. Tasks query for overdue count
  const {data: tasks} = useQuery({
    queryKey: ['tasks', 'today'],
    queryFn: () => tasksApi.list().then((r) => r.data),
  });

  // 3. Pending calls query to include in the "More" aggregate badge count
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

  // Calculate live badge counts
  const inboxBadge = inbox?.length ?? 0;
  
  const tasksBadge = tasks?.filter(
    (t: Task) => t.status !== 'completed' && t.due_at && new Date(t.due_at) < new Date()
  ).length ?? 0;

  const pendingCallsCount = pendingCalls?.length ?? 0;
  const moreBadge = notificationsCount + pendingCallsCount;

  // Style tokens
  const styles = {
    // Bar background: --surface-raised
    backgroundColor: isDarkMode ? '#111827' : '#ffffff',
    // 1px top border (--border-subtle)
    borderTopColor: isDarkMode ? '#1f2937' : '#e2e8f0',
    tabActiveColor: '#10b981',
    tabInactiveColor: isDarkMode ? '#71717a' : '#94a3b8',
  };

  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: styles.backgroundColor,
          borderTopColor: styles.borderTopColor,
          borderTopWidth: 1,
          height: 62,
          paddingBottom: 8,
          shadowColor: '#000',
          shadowOffset: {width: 0, height: -2},
          shadowOpacity: 0.05,
          shadowRadius: 4,
          elevation: 5,
        },
        tabBarActiveTintColor: styles.tabActiveColor,
        tabBarInactiveTintColor: styles.tabInactiveColor,
        tabBarLabelStyle: {fontSize: 10, fontWeight: '700', marginTop: 4},
      }}
    >
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({focused}) => (
            <TabIcon name="home" focused={focused} isDarkMode={isDarkMode} />
          ),
        }}
      />
      <Tab.Screen
        name="Contacts"
        component={ContactsStack}
        options={{
          tabBarLabel: 'Contacts',
          tabBarIcon: ({focused}) => (
            <TabIcon name="users" focused={focused} isDarkMode={isDarkMode} />
          ),
        }}
      />
      <Tab.Screen
        name="Inbox"
        component={MessagingStack}
        options={{
          tabBarLabel: 'Inbox',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="message-square"
              focused={focused}
              badgeCount={inboxBadge}
              isDarkMode={isDarkMode}
            />
          ),
        }}
      />
      <Tab.Screen
        name="Tasks"
        component={TasksScreen}
        options={{
          tabBarLabel: 'Tasks',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="check-square"
              focused={focused}
              badgeCount={tasksBadge}
              isDarkMode={isDarkMode}
            />
          ),
        }}
      />
      <Tab.Screen
        name="More"
        component={MoreScreen}
        options={{
          tabBarLabel: 'More',
          tabBarIcon: ({focused}) => (
            <TabIcon
              name="grid"
              focused={focused}
              badgeCount={moreBadge}
              isDarkMode={isDarkMode}
            />
          ),
        }}
      />
    </Tab.Navigator>
  );
}
