import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {TenantsScreen} from '../../screens/tenants/TenantsScreen';
import {TenantDetailScreen} from '../../screens/tenants/TenantDetailScreen';
import {QuitNoticeDetailScreen} from '../../screens/tenants/QuitNoticeDetailScreen';
import {CreateQuitNoticeScreen} from '../../screens/tenants/CreateQuitNoticeScreen';

export type TenantsStackParamList = {
  TenantsList:       undefined;
  TenantDetail:      {tenantId: number};
  QuitNoticeDetail:  {noticeId: number; tenantId: number};
  CreateQuitNotice:  {tenantId: number; leaseId: number; tenantName: string};
};

const Stack = createNativeStackNavigator<TenantsStackParamList>();

export function TenantsStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="TenantsList"      component={TenantsScreen} />
      <Stack.Screen name="TenantDetail"     component={TenantDetailScreen} />
      <Stack.Screen name="QuitNoticeDetail" component={QuitNoticeDetailScreen} />
      <Stack.Screen name="CreateQuitNotice" component={CreateQuitNoticeScreen} />
    </Stack.Navigator>
  );
}
