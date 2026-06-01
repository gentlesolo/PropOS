import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {InvoicesScreen} from '../../screens/finance/InvoicesScreen';
import {InvoiceDetailScreen} from '../../screens/finance/InvoiceDetailScreen';

export type FinanceStackParamList = {
  InvoicesList:  undefined;
  InvoiceDetail: {invoiceId: number};
};

const Stack = createNativeStackNavigator<FinanceStackParamList>();

export function FinanceStack() {
  return (
    <Stack.Navigator
      screenOptions={{
        headerStyle: {backgroundColor: '#1e293b'},
        headerTintColor: '#f1f5f9',
        headerTitleStyle: {fontWeight: '700'},
      }}>
      <Stack.Screen
        name="InvoicesList"
        component={InvoicesScreen}
        options={{headerShown: false}}
      />
      <Stack.Screen
        name="InvoiceDetail"
        component={InvoiceDetailScreen}
        options={{title: 'Invoice'}}
      />
    </Stack.Navigator>
  );
}
