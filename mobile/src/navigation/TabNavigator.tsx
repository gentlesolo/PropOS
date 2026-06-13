import React from 'react';
import {Text, View} from 'react-native';
import {createBottomTabNavigator} from '@react-navigation/bottom-tabs';
import Icon from 'react-native-vector-icons/Feather';
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

function TabIcon({name, focused}: {name: string; focused: boolean}) {
  return (
    <View className="items-center justify-center pt-1">
      <Icon 
        name={name} 
        size={22} 
        color={focused ? '#10b981' : '#94a3b8'} 
      />
    </View>
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
          backgroundColor: '#ffffff',
          borderTopColor: '#f1f5f9',
          height: 60,
          paddingBottom: 8,
          shadowColor: '#000',
          shadowOffset: {width: 0, height: -2},
          shadowOpacity: 0.05,
          shadowRadius: 4,
          elevation: 5,
        },
        tabBarActiveTintColor: '#10b981',
        tabBarInactiveTintColor: '#94a3b8',
        tabBarLabelStyle: {fontSize: 10, fontWeight: '700', marginTop: 4},
      }}>
      <Tab.Screen
        name="Home"
        component={HomeScreen}
        options={{
          tabBarLabel: 'Home',
          tabBarIcon: ({focused}) => <TabIcon name="home" focused={focused} />,
          tabBarBadge: unreadCount > 0 ? unreadCount : undefined,
          tabBarBadgeStyle: {backgroundColor: '#F59E0B', color: '#ffffff', fontSize: 10},
        }}
      />
      <Tab.Screen
        name="Contacts"
        component={ContactsStack}
        options={{
          tabBarLabel: 'Contacts',
          tabBarIcon: ({focused}) => <TabIcon name="users" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Messages"
        component={MessagingStack}
        options={{
          tabBarLabel: 'Messages',
          tabBarIcon: ({focused}) => <TabIcon name="message-square" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Calls"
        component={CallsStack}
        options={{
          tabBarLabel: 'Calls',
          tabBarIcon: ({focused}) => <TabIcon name="phone-call" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Tasks"
        component={TasksScreen}
        options={{
          tabBarLabel: 'Tasks',
          tabBarIcon: ({focused}) => <TabIcon name="check-square" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Viewings"
        component={ViewingsStack}
        options={{
          tabBarLabel: 'Viewings',
          tabBarIcon: ({focused}) => <TabIcon name="calendar" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Tenants"
        component={TenantsStack}
        options={{
          tabBarLabel: 'Tenants',
          tabBarIcon: ({focused}) => <TabIcon name="key" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Finance"
        component={FinanceStack}
        options={{
          tabBarLabel: 'Finance',
          tabBarIcon: ({focused}) => <TabIcon name="dollar-sign" focused={focused} />,
        }}
      />
      <Tab.Screen
        name="Intelligence"
        component={IntelligenceStack}
        options={{
          tabBarLabel: 'Intel',
          tabBarIcon: ({focused}) => <TabIcon name="bar-chart-2" focused={focused} />,
          tabBarStyle: isManager
            ? {backgroundColor: '#ffffff', borderTopColor: '#f1f5f9', height: 60, paddingBottom: 8}
            : {display: 'none'},
        }}
      />
      <Tab.Screen
        name="Profile"
        component={ProfileScreen}
        options={{
          tabBarLabel: 'Profile',
          tabBarIcon: ({focused}) => <TabIcon name="user" focused={focused} />,
        }}
      />
    </Tab.Navigator>
  );
}
