import Pusher, {Channel} from 'pusher-js/react-native';
import {intelligenceApi} from '../api/intelligence';
import {storage} from '../api/client';

export interface TranscriptSegment {
  speaker: 'Agent' | 'Contact' | string;
  text: string;
  is_final: boolean;
  start: number;
}

type SegmentCallback = (segment: TranscriptSegment) => void;

let pusherClient: Pusher | null = null;
let activeChannel: Channel | null = null;

function getPusher(): Pusher {
  if (!pusherClient) {
    pusherClient = new Pusher(process.env.PUSHER_APP_KEY ?? '', {
      cluster: process.env.PUSHER_APP_CLUSTER ?? 'mt1',
      authEndpoint: `${process.env.API_BASE_URL}/broadcasting/auth`,
      auth: {
        headers: {Authorization: `Bearer ${storage.getString('auth_token') ?? ''}`},
      },
    });
  }
  return pusherClient;
}

export const liveTranscriptService = {
  /**
   * Subscribe to the Pusher channel for a call's live transcript.
   * Returns an unsubscribe function.
   */
  async subscribe(callId: number, onSegment: SegmentCallback): Promise<() => void> {
    // Ask backend for the channel name and trigger stream start
    const [{data: channelData}] = await Promise.all([
      intelligenceApi.getChannel(callId),
      intelligenceApi.startStream(callId),
    ]);

    const channelName = channelData.channel;
    const pusher      = getPusher();

    activeChannel = pusher.subscribe(channelName);

    activeChannel.bind('transcript.segment', (data: TranscriptSegment) => {
      onSegment(data);
    });

    return () => liveTranscriptService.unsubscribe(channelName);
  },

  unsubscribe(channelName: string): void {
    if (pusherClient) {
      pusherClient.unsubscribe(channelName);
    }
    activeChannel = null;
  },

  disconnect(): void {
    pusherClient?.disconnect();
    pusherClient   = null;
    activeChannel  = null;
  },
};
