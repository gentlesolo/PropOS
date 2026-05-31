import React from 'react';
import {createNativeStackNavigator} from '@react-navigation/native-stack';
import {ViewingsScreen} from '../../screens/viewings/ViewingsScreen';
import {ViewingDetailScreen} from '../../screens/viewings/ViewingDetailScreen';

export type ViewingsStackParamList = {
  ViewingsList: undefined;
  ViewingDetail: {viewingId: number};
};

const Stack = createNativeStackNavigator<ViewingsStackParamList>();

export function ViewingsStack() {
  return (
    <Stack.Navigator screenOptions={{headerShown: false}}>
      <Stack.Screen name="ViewingsList" component={ViewingsScreen} />
      <Stack.Screen name="ViewingDetail" component={ViewingDetailScreen} />
    </Stack.Navigator>
  );
}
