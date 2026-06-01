import {useCallStore} from '../src/store/callStore';

describe('callStore', () => {
  beforeEach(() => useCallStore.getState().endCall());

  it('starts idle', () => {
    expect(useCallStore.getState().activeCallState).toBe('idle');
    expect(useCallStore.getState().activeCallSid).toBeNull();
  });

  it('setActiveCall transitions to connecting', () => {
    useCallStore.getState().setActiveCall('sid_123', {direction: 'outbound', remote_number: '+2348000000'});
    expect(useCallStore.getState().activeCallState).toBe('connecting');
    expect(useCallStore.getState().activeCallSid).toBe('sid_123');
    expect(useCallStore.getState().startTime).not.toBeNull();
  });

  it('toggleMute flips isMuted', () => {
    expect(useCallStore.getState().isMuted).toBe(false);
    useCallStore.getState().toggleMute();
    expect(useCallStore.getState().isMuted).toBe(true);
    useCallStore.getState().toggleMute();
    expect(useCallStore.getState().isMuted).toBe(false);
  });

  it('endCall resets all state', () => {
    useCallStore.getState().setActiveCall('sid_123', {direction: 'outbound'});
    useCallStore.getState().toggleMute();
    useCallStore.getState().endCall();

    const s = useCallStore.getState();
    expect(s.activeCallSid).toBeNull();
    expect(s.activeCallState).toBe('idle');
    expect(s.isMuted).toBe(false);
    expect(s.startTime).toBeNull();
  });
});
