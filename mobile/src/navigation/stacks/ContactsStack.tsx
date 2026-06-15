import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {ContactsScreen} from '../../screens/contacts/ContactsScreen';
import {ContactDetailScreen} from '../../screens/contacts/ContactDetailScreen';
import {InCallScreen} from '../../screens/calls/InCallScreen';
import {PostCallSummaryScreen} from '../../screens/calls/PostCallSummaryScreen';
import {CallDetailScreen} from '../../screens/calls/CallDetailScreen';
import {CallTranscriptScreen} from '../../screens/calls/CallTranscriptScreen';

export type ContactsStackParamList = {
  ContactsList: undefined;
  ContactDetail: {contactId: number};
  InCall: {contactId?: number; phoneNumber: string};
  PostCallSummary: {callId: number};
  CallDetail: {callId: number};
  CallTranscript: {callId: number};
};

const Stack = createNativeStackNavigator<ContactsStackParamList>();

export function ContactsStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="ContactsList"      component={ContactsScreen} />
      <Stack.Screen name="ContactDetail"     component={ContactDetailScreen} />
      <Stack.Screen name="CallDetail"        component={CallDetailScreen} />
      <Stack.Screen
        name="InCall"
        component={InCallScreen}
        options={{presentation: 'fullScreenModal'}}
      />
      <Stack.Screen
        name="PostCallSummary"
        component={PostCallSummaryScreen}
        options={{presentation: 'fullScreenModal'}}
      />
      <Stack.Screen name="CallTranscript" component={CallTranscriptScreen} />
    </Stack.Navigator>
  );
}
