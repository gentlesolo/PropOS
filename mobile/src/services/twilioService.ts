import {Voice, Call as TwilioCall, CallInvite} from '@twilio/voice-react-native-sdk';
import {Platform} from 'react-native';
import RNCallKeep from 'react-native-callkeep';
import {callsApi} from '../api/calls';
import {useCallStore} from '../store/callStore';

let voice: Voice | null = null;
let activeCall: TwilioCall | null = null;

export const twilioService = {
  async init(): Promise<Voice | null> {
    const {data} = await callsApi.getToken();

    voice = new Voice();

    await voice.register(data.token);

    voice.on(Voice.Event.CallInvite, (invite: CallInvite) => {
      useCallStore.getState().setActiveCall(invite.getCallSid(), {
        direction: 'inbound',
        remote_number: invite.getFrom(),
      });
    });

    return voice;
  },

  async makeCall(toNumber: string, contactId?: number): Promise<string> {
    if (!voice) {
      await twilioService.init();
    }

    if (!voice) {
      throw new Error('Failed to initialise Twilio Voice SDK. Check your network and Twilio credentials.');
    }

    const {data: tokenData} = await callsApi.getToken();

    const call = await voice.connect(tokenData.token, {
      params: {To: toNumber},
    });

    activeCall = call;
    const sid = call.getSid() ?? `local-${Date.now()}`;

    useCallStore.getState().setActiveCall(sid, {
      direction: 'outbound',
      remote_number: toNumber,
      contact_id: contactId,
    });

    await callsApi.store({
      contact_id: contactId,
      remote_number: toNumber,
      provider_call_sid: sid,
    });

    call.on(TwilioCall.Event.Ringing, () => {
      useCallStore.getState().updateCallState('ringing');
    });

    call.on(TwilioCall.Event.Connected, () => {
      useCallStore.getState().updateCallState('active');
    });

    call.on(TwilioCall.Event.Disconnected, () => {
      useCallStore.getState().endCall();
      activeCall = null;
    });

    return sid;
  },

  mute(muted: boolean): void {
    activeCall?.mute(muted);
    useCallStore.getState().toggleMute();
  },

  hangup(): void {
    activeCall?.disconnect();
    useCallStore.getState().endCall();
    activeCall = null;
  },

  setSpeaker(on: boolean): void {
    const uuid = useCallStore.getState().activeCallSid || '';
    if (Platform.OS === 'ios') {
      RNCallKeep.setAudioRoute(uuid, on ? 'speaker' : 'earpiece');
    } else {
      // Android: InCallManager handles audio routing via CallKeep's underlying ConnectionService
      RNCallKeep.setAudioRoute(uuid, on ? 'speaker' : 'earpiece');
    }
    useCallStore.getState().toggleSpeaker();
  },

  sendDigits(digit: string): void {
    activeCall?.sendDigits(digit);
  },
};
