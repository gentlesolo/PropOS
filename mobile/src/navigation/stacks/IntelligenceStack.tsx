import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {CallAnalyticsScreen} from '../../screens/intelligence/CallAnalyticsScreen';
import {ManagerDashboardScreen} from '../../screens/intelligence/ManagerDashboardScreen';
import {TeamBenchmarkScreen} from '../../screens/intelligence/TeamBenchmarkScreen';

export type IntelligenceStackParamList = {
  Analytics:        undefined;
  Benchmark:        undefined;
  ManagerDashboard: undefined;
};

const Stack = createNativeStackNavigator<IntelligenceStackParamList>();

export function IntelligenceStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="Analytics"        component={CallAnalyticsScreen} />
      <Stack.Screen name="Benchmark"        component={TeamBenchmarkScreen} />
      <Stack.Screen name="ManagerDashboard" component={ManagerDashboardScreen} />
    </Stack.Navigator>
  );
}
