import React, {useEffect, useRef} from 'react';
import {AppState, AppStateStatus} from 'react-native';
import {QueryClient, QueryClientProvider} from '@tanstack/react-query';
import {NavigationContainerRef} from '@react-navigation/native';
import {RootNavigator} from './src/navigation/RootNavigator';
import {useAuthStore} from './src/store/authStore';
import {notificationService} from './src/services/notificationService';
import {twilioService} from './src/services/twilioService';
import {sentryService} from './src/services/sentryService';
import {ErrorBoundary} from './src/components/ErrorBoundary';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {retry: 2, staleTime: 1000 * 60 * 5},
  },
});

// Shared navigation ref so notification handler can navigate from outside components
export const navigationRef = React.createRef<NavigationContainerRef<any>>();

function AppInner() {
  const {isAuthenticated} = useAuthStore();
  const appState = useRef(AppState.currentState);

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

    return () => unsub();
  }, [isAuthenticated]);

  // Invalidate stale data when the app returns to foreground
  useEffect(() => {
    const sub = AppState.addEventListener('change', (next: AppStateStatus) => {
      if (appState.current.match(/inactive|background/) && next === 'active') {
        queryClient.invalidateQueries({queryKey: ['tasks', 'today']});
        queryClient.invalidateQueries({queryKey: ['viewings', 'today']});
        queryClient.invalidateQueries({queryKey: ['inbox']});
      }
      appState.current = next;
    });
    return () => sub.remove();
  }, []);

  return <RootNavigator navigationRef={navigationRef} />;
}

export default function App() {
  sentryService.init();

  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <AppInner />
      </QueryClientProvider>
    </ErrorBoundary>
  );
}
