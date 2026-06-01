import {useCallback} from 'react';
import {useNavigation} from '@react-navigation/native';
import {twilioService} from '../services/twilioService';
import {callsApi} from '../api/calls';
import {useCallStore} from '../store/callStore';

/**
 * Encapsulates the full outbound call flow.
 * Screens call `initiateCall(phoneNumber, contactId)` and navigate to InCall automatically.
 */
export function useCall() {
  const navigation = useNavigation<any>();
  const {activeCallState} = useCallStore();

  const initiateCall = useCallback(
    async (phoneNumber: string, contactId?: number) => {
      navigation.navigate('InCall', {phoneNumber, contactId});
    },
    [navigation],
  );

  const hangup = useCallback(async (callId?: number, elapsed?: number) => {
    twilioService.hangup();
    if (callId && elapsed) {
      await callsApi.updateStatus(callId, 'completed', elapsed);
    }
  }, []);

  return {initiateCall, hangup, activeCallState};
}
