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
import {Icons} from '../theme/icons';

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
  badgeVariant = 'brand',
  tokens,
}: {
  name: string;
  focused: boolean;
  badgeCount?: number;
  badgeVariant?: 'brand' | 'danger';
  tokens: ReturnType<typeof useTheme>['tokens'];
}) {
  const badgeBg = badgeVariant === 'danger' ? tokens.dangerText : tokens.brandPrimary;

  return (
    <View style={{alignItems: 'center', justifyContent: 'center', paddingTop: 8, width: 48, height: 40}}>
      {/* Active indicator dot — 4px, positioned 2px above icon */}
      {focused && (
        <View
          style={{
            position: 'absolute',
            top: 0,
            width: 4,
            height: 4,
            borderRadius: 2,
            backgroundColor: tokens.brandPrimary,
          }}
        />
      )}
      <Icon
        name={name}
        size={20}
        color={focused ? tokens.brandPrimary : tokens.textTertiary}
      />
      {badgeCount !== undefined && badgeCount > 0 && (
        <View
          style={{
            position: 'absolute',
            top: 0,
            right: 0,
            backgroundColor: badgeBg,
            borderRadius: 8,
            paddingHorizontal: 4,
            paddingVertical: 1,
            minWidth: 16,
            alignItems: 'center',
            justifyContent: 'center',
          }}>
          <Text style={{color: '#FFFFFF', fontSize: 8, fontWeight: '900', lineHeight: 12}}>
            {badgeCount > 9 ? '9+' : badgeCount}
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

  // Tasks badge is overdue count — shown in danger color, not brand
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
        tabBarLabelStyle: {fontSize: 11, marginTop: 2},
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: ({focused}) => (
            <Text style={{fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? tokens.brandPrimary : tokens.textTertiary, marginTop: 2}}>
              Home
            </Text>
          ),
          tabBarIcon: ({focused}) => (
            <TabIcon name={Icons.navHome} focused={focused} tokens={tokens} />
          ),
        }}
      />
      <Tab.Screen
        name="Contacts"
        component={ContactsStack}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: ({focused}) => (
            <Text style={{fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? tokens.brandPrimary : tokens.textTertiary, marginTop: 2}}>
              Contacts
            </Text>
          ),
          tabBarIcon: ({focused}) => (
            <TabIcon name={Icons.navContacts} focused={focused} tokens={tokens} />
          ),
        }}
      />
      <Tab.Screen
        name="Inbox"
        component={MessagingStack}
        listeners={{tabPress: () => Vibration.vibrate(10)}}
        options={{
          tabBarLabel: ({focused}) => (
            <Text style={{fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? tokens.brandPrimary : tokens.textTertiary, marginTop: 2}}>
              Inbox
            </Text>
          ),
          tabBarIcon: ({focused}) => (
            <TabIcon
              name={Icons.navInbox}
              focused={focused}
              badgeCount={inboxBadge}
              badgeVariant="brand"
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
          tabBarLabel: ({focused}) => (
            <Text style={{fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? tokens.brandPrimary : tokens.textTertiary, marginTop: 2}}>
              Tasks
            </Text>
          ),
          tabBarIcon: ({focused}) => (
            <TabIcon
              name={Icons.navTasks}
              focused={focused}
              badgeCount={tasksBadge}
              badgeVariant="danger"
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
          tabBarLabel: ({focused}) => (
            <Text style={{fontSize: 11, fontWeight: focused ? '700' : '500', color: focused ? tokens.brandPrimary : tokens.textTertiary, marginTop: 2}}>
              More
            </Text>
          ),
          tabBarIcon: ({focused}) => (
            <TabIcon
              name={Icons.navMore}
              focused={focused}
              badgeCount={moreBadge}
              badgeVariant="brand"
              tokens={tokens}
            />
          ),
        }}
      />
    </Tab.Navigator>
  );
}
