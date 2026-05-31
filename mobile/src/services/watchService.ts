import {Platform} from 'react-native';

/**
 * Apple Watch connectivity service.
 *
 * Installation:
 *   npm install react-native-watch-connectivity
 *   cd ios && pod install
 *
 * This module is iOS-only; on Android it no-ops gracefully.
 */

interface WatchStats {
  callsToday: number;
  tasksToday: number;
  tasksOverdue: number;
  avgSentiment: number;
}

interface WatchTask {
  id: number;
  title: string;
  dueAt?: string;
}

export const watchService = {
  isSupported: Platform.OS === 'ios',

  async pushStats(stats: WatchStats, tasks: WatchTask[]): Promise<void> {
    if (!watchService.isSupported) return;

    try {
      const WatchConnectivity = require('react-native-watch-connectivity');

      await WatchConnectivity.updateApplicationContext({
        stats: JSON.stringify(stats),
        tasks: JSON.stringify(tasks.slice(0, 5)),
      });
    } catch {
      // Watch not paired or app not installed — fail silently
    }
  },

  /**
   * Listen for messages from the Watch (e.g., task completions).
   * Returns an unsubscribe function.
   */
  listenForMessages(
    onTaskComplete: (taskId: number) => void,
  ): () => void {
    if (!watchService.isSupported) return () => {};

    try {
      const WatchConnectivity = require('react-native-watch-connectivity');

      const sub = WatchConnectivity.watchEvents.addListener(
        'message',
        (message: {action: string; id: number}) => {
          if (message.action === 'completeTask') {
            onTaskComplete(message.id);
          }
        },
      );

      return () => sub.remove();
    } catch {
      return () => {};
    }
  },
};
