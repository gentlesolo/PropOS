import * as Sentry from '@sentry/react-native';

const SENTRY_DSN = process.env.SENTRY_DSN ?? '';

export const sentryService = {
  init(): void {
    if (!SENTRY_DSN) return;

    Sentry.init({
      dsn: SENTRY_DSN,
      environment: __DEV__ ? 'development' : 'production',
      tracesSampleRate: 0.2,
      enableNative: true,
    });
  },

  captureException(error: unknown, context?: Record<string, unknown>): void {
    if (__DEV__) {
      console.error('[Sentry]', error, context);
      return;
    }
    Sentry.captureException(error, {extra: context});
  },

  setUser(id: number, email: string): void {
    Sentry.setUser({id: String(id), email});
  },

  clearUser(): void {
    Sentry.setUser(null);
  },

  addBreadcrumb(message: string, data?: Record<string, unknown>): void {
    Sentry.addBreadcrumb({message, data, level: 'info'});
  },
};
