import React, {useEffect} from 'react';
import {NavigationContainer, NavigationContainerRef} from '@react-navigation/native';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {useAuthStore} from '../store/authStore';
import {TabNavigator} from './TabNavigator';
import {LoginScreen} from '../screens/auth/LoginScreen';

export type RootStackParamList = {
  Auth: undefined;
  Main: undefined;
  // Deep-link targets reachable from notifications
  PostCallSummary: {callId: number};
  ContactDetail:   {contactId: number};
};

const Stack = createNativeStackNavigator<RootStackParamList>();

interface Props {
  navigationRef?: React.RefObject<NavigationContainerRef<any> | null>;
}

export function RootNavigator({navigationRef}: Props) {
  const {isAuthenticated, hydrate} = useAuthStore();

  useEffect(() => {
    hydrate();
  }, [hydrate]);

  return (
    <NavigationContainer ref={navigationRef}>
      <Stack.Navigator screenOptions={{headerShown: false}}>
        {isAuthenticated ? (
          <Stack.Screen name="Main" component={TabNavigator} />
        ) : (
          <Stack.Screen name="Auth" component={LoginScreen} />
        )}
      </Stack.Navigator>
    </NavigationContainer>
  );
}
