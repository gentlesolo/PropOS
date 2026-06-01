import React from 'react';
import {Text, View} from 'react-native';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import {HomeScreen} from '../screens/home/HomeScreen';
import {ContactsStack} from './stacks/ContactsStack';
import {CallsStack} from './stacks/CallsStack';
import {MessagingStack} from './stacks/MessagingStack';
import {TasksScreen} from '../screens/tasks/TasksScreen';
import {ViewingsStack} from './stacks/ViewingsStack';
import {IntelligenceStack} from './stacks/IntelligenceStack';
import {ProfileScreen} from '../screens/profile/ProfileScreen';
import {TenantsStack} from './stacks/TenantsStack';
import {FinanceStack} from './stacks/FinanceStack';
import {useAuthStore} from '../store/authStore';
import {useNotificationStore} from '../store/notificationStore';
import {useRealtime} from '../hooks/useRealtime';

export type TabParamList = {
  Home:         undefined;
  Contacts:     undefined;
  Messages:     undefined;
  Calls:        undefined;
  Tasks:        undefined;
  Viewings:     undefined;
  Tenants:      undefined;
  Finance:      undefined;
  Intelligence: undefined;
  Profile:      undefined;
};

const Tab = createBottomTabNavigator<TabParamList>();

function TabIcon({emoji, focused}: {emoji: string; focused: boolean}) {
  return (
    <Text style={{fontSize: focused ? 22 : 20, opacity: focused ? 1 : 0.5}}>
      {emoji}
    </Text>
  );
}

export function TabNavigator() {
  const {user} = useAuthStore();
  const {unreadCount} = useNotificationStore();

  // Wire up real-time push → query invalidation for the entire authenticated session
  useRealtime();

  const isManager = (user as any)?.roles?.some?.(
    (r: string) => r === 'admin' || r === 'manager',
  ) ?? false;

  return (
    <Tab.Navigator
      screenOptions={{
        headerShown: false,
        tabBarStyle: {
          backgroundColor: '#1e293b',
          borderTopColor: '#334155',
          height: 60,
          paddingBottom: 8,
        },
        tabBarActiveTintColor: '#3b82f6',
        tabBarInactiveTintColor: '#64748b',
        tabBarLabelStyle: {fontSize: 10, fontWeight: '600'},
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({focused}) => <TabIcon emoji="🏠" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Contacts"
        component={ContactsStack}
        options={{
          tabBarLabel: 'Contacts',
          tabBarIcon: ({focused}) => <TabIcon emoji="👥" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Messages"
        component={MessagingStack}
        options={{
          tabBarLabel: 'Messages',
          tabBarIcon: ({focused}) => <TabIcon emoji="💬" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Calls"
        component={CallsStack}
        options={{
          tabBarLabel: 'Calls',
          tabBarIcon: ({focused}) => <TabIcon emoji="📞" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Tasks"
        component={TasksScreen}
        options={{
          tabBarLabel: 'Tasks',
          tabBarIcon: ({focused}) => <TabIcon emoji="✅" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Viewings"
        component={ViewingsStack}
        options={{
          tabBarLabel: 'Viewings',
          tabBarIcon: ({focused}) => <TabIcon emoji="🏡" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Tenants"
        component={TenantsStack}
        options={{
          tabBarLabel: 'Tenants',
          tabBarIcon: ({focused}) => <TabIcon emoji="🏘️" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Finance"
        component={FinanceStack}
        options={{
          tabBarLabel: 'Finance',
          tabBarIcon: ({focused}) => <TabIcon emoji="💰" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Intelligence"
        component={IntelligenceStack}
        options={{
          tabBarLabel: 'Intel',
          tabBarIcon: ({focused}) => <TabIcon emoji="📊" focused={focused} />,
          tabBarStyle: isManager
            ? {backgroundColor: '#1e293b', borderTopColor: '#334155', height: 60, paddingBottom: 8}
            : {display: 'none'},
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          tabBarLabel: 'Profile',
          tabBarIcon: ({focused}) => <TabIcon emoji="👤" focused={focused} />,
        }}
      />
    </Tab.Navigator>
  );
}
