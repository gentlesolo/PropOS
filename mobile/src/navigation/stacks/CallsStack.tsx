import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {CallHistoryScreen} from '../../screens/calls/CallHistoryScreen';
import {CallDetailScreen} from '../../screens/calls/CallDetailScreen';
import {PostCallSummaryScreen} from '../../screens/calls/PostCallSummaryScreen';
import {InCallScreen} from '../../screens/calls/InCallScreen';
import {CallTranscriptScreen} from '../../screens/calls/CallTranscriptScreen';

export type CallsStackParamList = {
  CallHistory: undefined;
  CallDetail: {callId: number};
  PostCallSummary: {callId: number};
  InCall: {contactId?: number; phoneNumber: string};
  CallTranscript: {callId: number};
};

const Stack = createNativeStackNavigator<CallsStackParamList>();

export function CallsStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="CallHistory" component={CallHistoryScreen} />
      <Stack.Screen name="CallDetail" component={CallDetailScreen} />
      <Stack.Screen name="PostCallSummary" component={PostCallSummaryScreen} />
      <Stack.Screen
        name="InCall"
        component={InCallScreen}
        options={{presentation: 'fullScreenModal'}}
      />
      <Stack.Screen name="CallTranscript" component={CallTranscriptScreen} />
    </Stack.Navigator>
  );
}
