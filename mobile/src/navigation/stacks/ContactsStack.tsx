import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {ContactsScreen} from '../../screens/contacts/ContactsScreen';
import {ContactDetailScreen} from '../../screens/contacts/ContactDetailScreen';
import {InCallScreen} from '../../screens/calls/InCallScreen';

export type ContactsStackParamList = {
  ContactsList: undefined;
  ContactDetail: {contactId: number};
  InCall: {contactId?: number; phoneNumber: string; callSid?: string};
};

const Stack = createNativeStackNavigator<ContactsStackParamList>();

export function ContactsStack() {
  return (
    <Stack.Navigator
      screenOptions={{headerShown: false}}>
      <Stack.Screen name="ContactsList" component={ContactsScreen} />
      <Stack.Screen name="ContactDetail" component={ContactDetailScreen} />
      <Stack.Screen
        name="InCall"
        component={InCallScreen}
        options={{presentation: 'fullScreenModal'}}
      />
    </Stack.Navigator>
  );
}
