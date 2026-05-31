import {Platform} from 'react-native';
import RNCallKeep from 'react-native-callkeep';
import messaging from '@react-native-firebase/messaging';
import {Voice, CallInvite} from '@twilio/voice-react-native-sdk';
import {callsApi} from '../api/calls';
import {useCallStore} from '../store/callStore';

let voice: Voice | null = null;

const CALLKEEP_OPTIONS = {
  ios: {
    appName: 'PropOS',
    supportsVideo: false,
    maximumCallGroups: '1',
    maximumCallsPerCallGroup: '1',
    includesCallsInRecents: false,
  },
  android: {
    alertTitle: 'Permissions required',
    alertDescription: 'PropOS needs to manage phone calls',
    cancelButton: 'Cancel',
    okButton: 'Allow',
    imageName: 'ic_launcher',
    additionalPermissions: [],
    foregroundService: {
      channelId: 'propos_calls',
      channelName: 'PropOS Calls',
      notificationTitle: 'PropOS is running a call',
    },
  },
};

export const inboundCallService = {
  async setup(voiceInstance: Voice): Promise<void> {
    voice = voiceInstance;

    await RNCallKeep.setup(CALLKEEP_OPTIONS);
    RNCallKeep.setAvailable(true);

    // iOS — listen for CallKit answer/end events
    RNCallKeep.addEventListener('answerCall', ({callUUID}) => {
      inboundCallService.answerPendingInvite(callUUID);
    });

    RNCallKeep.addEventListener('endCall', ({callUUID}) => {
      inboundCallService.rejectPendingInvite(callUUID);
      useCallStore.getState().endCall();
    });

    // Twilio CallInvite arrives via VoIP push (iOS) or FCM data message (Android)
    voiceInstance.on(Voice.Event.CallInvite, (invite: CallInvite) => {
      inboundCallService.handleCallInvite(invite);
    });

    voiceInstance.on(Voice.Event.CancelledCallInvite, () => {
      RNCallKeep.endAllCalls();
      useCallStore.getState().endCall();
    });
  },

  handleCallInvite(invite: CallInvite): void {
    const callUUID = invite.getCallSid() ?? `inv-${Date.now()}`;
    const from = invite.getFrom() ?? 'Unknown';

    // Show native incoming call UI (CallKit on iOS, ConnectionService on Android)
    RNCallKeep.displayIncomingCall(
      callUUID,
      from,
      from,
      'number',
      false,
    );

    useCallStore.getState().setActiveCall(callUUID, {
      direction: 'inbound',
      remote_number: from,
    });

    // Store the invite so we can accept/reject it later
    pendingInvites.set(callUUID, invite);
  },

  async answerPendingInvite(callUUID: string): Promise<void> {
    const invite = pendingInvites.get(callUUID);
    if (!invite) return;

    try {
      const call = await invite.accept();
      pendingInvites.delete(callUUID);

      useCallStore.getState().updateCallState('active');

      call.on('disconnected', () => {
        RNCallKeep.endCall(callUUID);
        useCallStore.getState().endCall();
      });
    } catch {
      RNCallKeep.endCall(callUUID);
      useCallStore.getState().endCall();
    }
  },

  rejectPendingInvite(callUUID: string): void {
    const invite = pendingInvites.get(callUUID);
    invite?.reject();
    pendingInvites.delete(callUUID);
  },

  /**
   * Handle FCM high-priority data message for incoming call on Android.
   * On iOS this is handled via PushKit → Twilio SDK automatically.
   */
  setupAndroidInboundHandler(): void {
    if (Platform.OS !== 'android') return;

    messaging().setBackgroundMessageHandler(async remoteMessage => {
      if (remoteMessage.data?.type === 'incoming_call') {
        // Twilio SDK processes the notification payload and fires CallInvite event
      }
    });
  },
};

const pendingInvites = new Map<string, CallInvite>();
