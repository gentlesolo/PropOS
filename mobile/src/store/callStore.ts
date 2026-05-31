import {create} from 'zustand';
import {Call} from '../types';

type ActiveCallState = 'idle' | 'connecting' | 'ringing' | 'active' | 'ending';

interface CallStore {
  activeCallSid: string | null;
  activeCallState: ActiveCallState;
  activeCall: Partial<Call> | null;
  isMuted: boolean;
  isSpeaker: boolean;
  startTime: number | null;

  setActiveCall: (sid: string, call: Partial<Call>) => void;
  updateCallState: (state: ActiveCallState) => void;
  toggleMute: () => void;
  toggleSpeaker: () => void;
  endCall: () => void;
}

export const useCallStore = create<CallStore>((set) => ({
  activeCallSid: null,
  activeCallState: 'idle',
  activeCall: null,
  isMuted: false,
  isSpeaker: false,
  startTime: null,

  setActiveCall: (sid, call) =>
    set({activeCallSid: sid, activeCall: call, activeCallState: 'connecting', startTime: Date.now()}),

  updateCallState: (state) => set({activeCallState: state}),

  toggleMute: () => set(s => ({isMuted: !s.isMuted})),

  toggleSpeaker: () => set(s => ({isSpeaker: !s.isSpeaker})),

  endCall: () =>
    set({
      activeCallSid: null,
      activeCallState: 'idle',
      activeCall: null,
      isMuted: false,
      isSpeaker: false,
      startTime: null,
    }),
}));
