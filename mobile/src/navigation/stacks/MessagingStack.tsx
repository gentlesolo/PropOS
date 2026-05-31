import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {MessagingInboxScreen} from '../../screens/messaging/MessagingInboxScreen';
import {ConversationScreen} from '../../screens/messaging/ConversationScreen';

export type MessagingStackParamList = {
  MessagingInbox: undefined;
  Conversation: {contactId: number; contactName: string};
};

const Stack = createNativeStackNavigator<MessagingStackParamList>();

export function MessagingStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="MessagingInbox" component={MessagingInboxScreen} />
      <Stack.Screen name="Conversation" component={ConversationScreen} />
    </Stack.Navigator>
  );
}
