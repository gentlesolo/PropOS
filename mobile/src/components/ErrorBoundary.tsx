import React, {Component, ErrorInfo, ReactNode} from 'react';
import {Pressable, Text, View} from 'react-native';

interface Props {
  children: ReactNode;
  fallback?: ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
}

export class ErrorBoundary extends Component<Props, State> {
  state: State = {hasError: false, error: null};

  static getDerivedStateFromError(error: Error): State {
    return {hasError: true, error};
  }

  componentDidCatch(error: Error, info: ErrorInfo): void {
    // In production this would call Sentry.captureException
    console.error('[ErrorBoundary]', error, info.componentStack);
  }

  reset = () => this.setState({hasError: false, error: null});

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback;

      return (
        <View className="flex-1 bg-surface items-center justify-center px-8">
          <Text className="text-4xl mb-4">⚠️</Text>
          <Text className="text-white text-lg font-semibold text-center mb-2">
            Something went wrong
          </Text>
          <Text className="text-slate-400 text-sm text-center mb-6">
            {this.state.error?.message ?? 'An unexpected error occurred.'}
          </Text>
          <Pressable
            className="bg-brand-600 rounded-xl px-6 py-3"
            onPress={this.reset}>
            <Text className="text-white font-semibold">Try again</Text>
          </Pressable>
        </View>
      );
    }

    return this.props.children;
  }
}
