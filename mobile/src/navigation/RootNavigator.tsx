import React, {useEffect} from 'react';
import {NavigationContainer, NavigationContainerRef} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {useAuthStore} from '../store/authStore';
import {TabNavigator} from './TabNavigator';
import {LoginScreen} from '../screens/auth/LoginScreen';
import {OnboardingScreen} from '../screens/auth/OnboardingScreen';
import {NotificationsScreen} from '../screens/notifications/NotificationsScreen';
import {CallsStack} from './stacks/CallsStack';
import {ViewingsStack} from './stacks/ViewingsStack';
import {TenantsStack} from './stacks/TenantsStack';
import {FinanceStack} from './stacks/FinanceStack';
import {IntelligenceStack} from './stacks/IntelligenceStack';
import {ProfileScreen} from '../screens/profile/ProfileScreen';

export type RootStackParamList = {
  Onboarding: undefined;
  Auth: undefined;
  Main: undefined;
  Notifications: undefined;
  Calls: undefined;
  Viewings: undefined;
  Tenants: undefined;
  Finance: undefined;
  Intelligence: undefined;
  Profile: undefined;
  // Deep-link targets reachable from notifications
  PostCallSummary: {callId: number};
  ContactDetail:   {contactId: number};
};

const Stack = createNativeStackNavigator<RootStackParamList>();

interface Props {
  navigationRef?: React.RefObject<NavigationContainerRef<any> | null>;
}

export function RootNavigator({navigationRef}: Props) {
  const {isAuthenticated, hasSeenOnboarding, hydrate} = useAuthStore();

  useEffect(() => {
    hydrate();
  }, [hydrate]);

  return (
    <NavigationContainer ref={navigationRef}>
      <Stack.Navigator screenOptions={{headerShown: false}}>
        {isAuthenticated ? (
          <>
            <Stack.Screen name="Main" component={TabNavigator} />
            <Stack.Screen name="Notifications" component={NotificationsScreen} />
            <Stack.Screen name="Calls" component={CallsStack} />
            <Stack.Screen name="Viewings" component={ViewingsStack} />
            <Stack.Screen name="Tenants" component={TenantsStack} />
            <Stack.Screen name="Finance" component={FinanceStack} />
            <Stack.Screen name="Intelligence" component={IntelligenceStack} />
            <Stack.Screen name="Profile" component={ProfileScreen} />
          </>
        ) : !hasSeenOnboarding ? (
          <Stack.Screen name="Onboarding" component={OnboardingScreen} />
        ) : (
          <Stack.Screen name="Auth" component={LoginScreen} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
