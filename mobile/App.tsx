import React, {useEffect, useRef} from 'react';
import {AppState, AppStateStatus} from 'react-native';
import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {NavigationContainer, NavigationContainerRef} from '@react-navigation/native';
import {SafeAreaProvider} from 'react-native-safe-area-context';
import {RootNavigator} from './src/navigation/RootNavigator';
import {useAuthStore} from './src/store/authStore';
import {useCallStore} from './src/store/callStore';
import {notificationService} from './src/services/notificationService';
import {twilioService} from './src/services/twilioService';
import {sentryService} from './src/services/sentryService';
import {ErrorBoundary} from './src/components/ErrorBoundary';
import {BiometricUnlockScreen} from './src/screens/auth/BiometricUnlockScreen';
import {OfflineIndicator} from './src/components/OfflineIndicator';
import {ThemeProvider} from './src/theme/ThemeProvider';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {retry: 2, staleTime: 1000 * 60 * 5},
  },
});

// Shared navigation ref so notification handler can navigate from outside components
export const navigationRef = React.createRef<NavigationContainerRef<any>>();

function AppInner() {
  const {isAuthenticated, isLocked} = useAuthStore();
  const appState = useRef(AppState.currentState);
  const backgroundTimeRef = useRef<number | null>(null);

  useEffect(() => {
    if (!isAuthenticated) return;

    // Register FCM/APNs token
    notificationService.registerDeviceToken().catch(console.warn);

    // Require inboundCallService lazily to avoid circular dependencies
    const {inboundCallService} = require('./src/services/inboundCallService');

    // Initialise Twilio Voice SDK and CallKeep for inbound calls
    twilioService.init().then(voice => {
      if (voice) inboundCallService.setup(voice);
    }).catch(console.warn);

    appState.current = AppState.currentState;

    inboundCallService.setupAndroidInboundHandler();

    // Handle tap on a notification that opened the app from background/quit
    notificationService.getInitialNotificationRoute().then(route => {
      if (route && navigationRef.current) {
        navigationRef.current.navigate(route.screen as any, route.params as any);
      }
    });

    // Handle foreground notifications — show a toast or navigate directly
    const unsub = notificationService.onForegroundMessage(data => {
      const route = notificationService.resolveRoute(data);
      if (route && navigationRef.current) {
        // For call summaries navigate automatically; for others, rely on the notification badge
        if (data.type === 'call_summary_ready') {
          navigationRef.current.navigate(route.screen as any, route.params as any);
        }
      }
      // Invalidate relevant query caches so data refreshes when the user opens the screen
      if (data.type === 'new_message') queryClient.invalidateQueries({queryKey: ['inbox']});
      if (data.type === 'call_summary_ready') queryClient.invalidateQueries({queryKey: ['calls']});
      if (data.type === 'new_lead_assigned') queryClient.invalidateQueries({queryKey: ['contacts']});
    });

    const unsubCall = useCallStore.subscribe(state => {
      const activeCallState = state.activeCallState;
      if (
        (activeCallState === 'connecting' || activeCallState === 'ringing' || activeCallState === 'active') &&
        navigationRef.current
      ) {
        const currentRoute = navigationRef.current.getCurrentRoute();
        if (currentRoute && currentRoute.name !== 'InCall') {
          const activeCall = state.activeCall;
          const contactId = activeCall?.contact_id;
          const phoneNumber = activeCall?.remote_number ?? '';
          const callSid = state.activeCallSid ?? undefined;

          navigationRef.current.navigate('InCall', {
            contactId,
            phoneNumber,
            callSid,
          });
        }
      }
    });

    return () => {
      unsub();
      unsubCall();
    };
  }, [isAuthenticated]);

  // Invalidate stale data and trigger lock check when the app returns to foreground
  useEffect(() => {
    const sub = AppState.addEventListener('change', (next: AppStateStatus) => {
      if (next.match(/inactive|background/)) {
        backgroundTimeRef.current = Date.now();
      } else if (appState.current.match(/inactive|background/) && next === 'active') {
        queryClient.invalidateQueries({queryKey: ['tasks', 'today']});
        queryClient.invalidateQueries({queryKey: ['viewings', 'today']});
        queryClient.invalidateQueries({queryKey: ['inbox']});

        // Trigger lock if backgrounded for more than 5 minutes
        if (backgroundTimeRef.current) {
          const timeElapsed = Date.now() - backgroundTimeRef.current;
          if (timeElapsed >= 5 * 60 * 1000) {
            useAuthStore.getState().setLocked(true);
          }
          backgroundTimeRef.current = null;
        }
      }
      appState.current = next;
    });
    return () => sub.remove();
  }, []);

  return (
    <>
      <OfflineIndicator />
      <RootNavigator />
      {isAuthenticated && isLocked && <BiometricUnlockScreen />}
    </>
  );
}

export default function App() {
  sentryService.init();

  return (
    <SafeAreaProvider>
      <ErrorBoundary>
        <QueryClientProvider client={queryClient}>
          <ThemeProvider>
            <NavigationContainer ref={navigationRef}>
              <AppInner />
            </NavigationContainer>
          </ThemeProvider>
        </QueryClientProvider>
      </ErrorBoundary>
    </SafeAreaProvider>
  );
}
