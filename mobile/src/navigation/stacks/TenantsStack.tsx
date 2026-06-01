import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {TenantsScreen} from '../../screens/tenants/TenantsScreen';
import {TenantDetailScreen} from '../../screens/tenants/TenantDetailScreen';

export type TenantsStackParamList = {
  TenantsList:  undefined;
  TenantDetail: {tenantId: number};
};

const Stack = createNativeStackNavigator<TenantsStackParamList>();

export function TenantsStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="TenantsList"  component={TenantsScreen} />
      <Stack.Screen name="TenantDetail" component={TenantDetailScreen} />
    </Stack.Navigator>
  );
}
