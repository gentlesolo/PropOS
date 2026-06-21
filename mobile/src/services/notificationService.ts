import {Platform} from 'react-native';
import messaging from '@react-native-firebase/messaging';
import {authApi} from '../api/auth';

export const notificationService = {
  async requestPermission(): Promise<boolean> {
    const authStatus = await messaging().requestPermission();
    return (
      authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
      authStatus === messaging.AuthorizationStatus.PROVISIONAL
    );
  },

  async registerDeviceToken(): Promise<void> {
    const granted = await notificationService.requestPermission();
    if (!granted) return;

    const token = await messaging().getToken();

    await authApi.registerDevice({
      platform: Platform.OS as 'ios' | 'android',
      push_token: token,
      push_type: Platform.OS === 'ios' ? 'apns' : 'fcm',
      device_name: 'VillaCRMMobile',
    });

    // Re-register if FCM rotates the token
    messaging().onTokenRefresh(async newToken => {
      await authApi.registerDevice({
        platform: Platform.OS as 'ios' | 'android',
        push_token: newToken,
        push_type: Platform.OS === 'ios' ? 'apns' : 'fcm',
        device_name: 'VillaCRMMobile',
      });
    });
  },

  // Returns the navigation target when a notification is tapped
  getInitialNotificationRoute(): Promise<{screen: string; params: object} | null> {
    return messaging()
      .getInitialNotification()
      .then(remoteMessage => {
        if (!remoteMessage?.data) return null;
        return notificationService.resolveRoute(remoteMessage.data as Record<string, string>);
      });
  },

  onForegroundMessage(handler: (data: Record<string, string>) => void): () => void {
    return messaging().onMessage(async remoteMessage => {
      if (remoteMessage.data) {
        handler(remoteMessage.data as Record<string, string>);
      }
    });
  },

  resolveRoute(data: Record<string, string>): {screen: string; params: object} | null {
    switch (data.type) {
      case 'call_summary_ready':
        return {screen: 'PostCallSummary', params: {callId: Number(data.call_id)}};
      case 'new_lead_assigned':
        return {screen: 'ContactDetail', params: {contactId: Number(data.contact_id)}};
      case 'new_message':
        return {screen: 'ContactDetail', params: {contactId: Number(data.contact_id)}};
      default:
        return null;
    }
  },
};
