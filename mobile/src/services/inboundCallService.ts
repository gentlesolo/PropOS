import {Platform} from 'react-native';
import RNCallKeep from 'react-native-callkeep';
import messaging from '@react-native-firebase/messaging';
import {Voice, Call, CallInvite} from '@twilio/voice-react-native-sdk';
import {callsApi} from '../api/calls';
import {contactsApi} from '../api/contacts';
import {useCallStore} from '../store/callStore';

let voice: Voice | null = null;

const CALLKEEP_OPTIONS = {
  ios: {
    appName: 'VillaCRM',
    supportsVideo: false,
    maximumCallGroups: '1',
    maximumCallsPerCallGroup: '1',
    includesCallsInRecents: false,
  },
  android: {
    alertTitle: 'Permissions required',
    alertDescription: 'VillaCRM needs to manage phone calls',
    cancelButton: 'Cancel',
    okButton: 'Allow',
    imageName: 'ic_launcher',
    additionalPermissions: [],
    foregroundService: {
      channelId: 'villacrm_calls',
      channelName: 'VillaCRM Calls',
      notificationTitle: 'VillaCRM is running a call',
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

    (voiceInstance as any).on('cancelledCallInvite', () => {
      RNCallKeep.endAllCalls();
      useCallStore.getState().endCall();
    });
  },

  handleCallInvite(invite: CallInvite): void {
    const callUUID = invite.getCallSid() ?? `inv-${Date.now()}`;
    const from = invite.getFrom() ?? 'Unknown';

    // Show native incoming call UI immediately (CallKit on iOS, ConnectionService on Android)
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

    // Perform CRM lookup and update CallKeep display
    contactsApi
      .list({search: from})
      .then(res => {
        const contacts = res.data?.data ?? [];
        const match = contacts.find(c => {
          const p1 = c.phone?.replace(/\D/g, '') ?? '';
          const p2 = from.replace(/\D/g, '') ?? '';
          return p1.endsWith(p2) || p2.endsWith(p1);
        });
        if (match) {
          const stage = match.status === 'qualified' ? 'Qualified Buyer' : match.status.charAt(0).toUpperCase() + match.status.slice(1);
          const displayName = `${match.first_name} ${match.last_name} · ${stage}`;
          RNCallKeep.updateDisplay(callUUID, displayName, from);
          useCallStore.getState().setActiveCall(callUUID, {
            direction: 'inbound',
            remote_number: from,
            contact_id: match.id,
          });
        } else {
          RNCallKeep.updateDisplay(callUUID, `Unknown · ${from}`, from);
        }
      })
      .catch(err => {
        console.warn('CRM contact lookup failed on incoming call', err);
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

      call.on(Call.Event.Disconnected, () => {
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
