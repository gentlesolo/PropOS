import {Room, RoomEvent} from '@livekit/react-native';
import {Platform} from 'react-native';
import RNCallKeep from 'react-native-callkeep';
import {callsApi} from '../api/calls';
import {useCallStore} from '../store/callStore';

let room: Room | null = null;

export const liveKitService = {
  /**
   * Initiate an outbound call.
   * Backend creates the LiveKit room, starts egress, and dials the lead via SIP.
   * We then connect to the room and publish microphone audio.
   * Returns the database call ID directly (no SID lookup needed).
   */
  async makeCall(toNumber: string, contactId?: number): Promise<{callId: number}> {
    const {data} = await callsApi.store({contact_id: contactId, remote_number: toNumber});
    const {call_id, room_name, token, server_url} = data;

    room = new Room();

    room.on(RoomEvent.ParticipantConnected, participant => {
      if (participant.identity.startsWith('lead_')) {
        useCallStore.getState().updateCallState('active');
      }
    });

    room.on(RoomEvent.ParticipantDisconnected, participant => {
      if (participant.identity.startsWith('lead_')) {
        useCallStore.getState().endCall();
        room = null;
      }
    });

    room.on(RoomEvent.Disconnected, () => {
      useCallStore.getState().endCall();
      room = null;
    });

    await room.connect(server_url, token, {autoSubscribe: true});
    await room.localParticipant.setMicrophoneEnabled(true);

    useCallStore.getState().setActiveCall(room_name, {
      direction: 'outbound',
      remote_number: toNumber,
      contact_id: contactId,
    });
    useCallStore.getState().updateCallState('ringing');

    return {callId: call_id};
  },

  mute(muted: boolean): void {
    room?.localParticipant.setMicrophoneEnabled(!muted);
    useCallStore.getState().toggleMute();
  },

  hangup(): void {
    room?.disconnect();
    useCallStore.getState().endCall();
    room = null;
  },

  setSpeaker(on: boolean): void {
    const uuid = useCallStore.getState().activeCallSid ?? '';
    RNCallKeep.setAudioRoute(uuid, on ? 'speaker' : 'earpiece');
    useCallStore.getState().toggleSpeaker();
  },

  sendDigits(_digit: string): void {
    // DTMF via SIP INFO requires a server-side relay — not yet implemented.
  },
};
