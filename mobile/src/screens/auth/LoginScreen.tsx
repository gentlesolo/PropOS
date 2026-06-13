import React, {useState, useEffect, useRef} from 'react';
import {
  KeyboardAvoidingView,
  Platform,
  Pressable,
  Text,
  TextInput,
  View,
  ActivityIndicator,
  Animated,
  ScrollView,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation} from '@tanstack/react-query';
import Icon from 'react-native-vector-icons/Feather';
import * as Keychain from 'react-native-keychain';
import {authApi} from '../../api/auth';
import {useAuthStore} from '../../store/authStore';
import {apiClient} from '../../api/client';

export function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [emailFocused, setEmailFocused] = useState(false);
  const [passwordFocused, setPasswordFocused] = useState(false);
  const [localError, setLocalError] = useState('');

  const {setAuth} = useAuthStore();

  // Animation values
  const glowOpacity = useRef(new Animated.Value(0.12)).current;
  const formShake = useRef(new Animated.Value(0)).current;

  // Slowly pulse the background glow
  useEffect(() => {
    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(glowOpacity, {
          toValue: 0.28,
          duration: 3500,
          useNativeDriver: true,
        }),
        Animated.timing(glowOpacity, {
          toValue: 0.12,
          duration: 3500,
          useNativeDriver: true,
        }),
      ])
    );
    pulse.start();
    return () => pulse.stop();
  }, []);

  const triggerFormShake = () => {
    Animated.sequence([
      Animated.timing(formShake, {toValue: -12, duration: 50, useNativeDriver: true}),
      Animated.timing(formShake, {toValue: 12, duration: 100, useNativeDriver: true}),
      Animated.timing(formShake, {toValue: -8, duration: 100, useNativeDriver: true}),
      Animated.timing(formShake, {toValue: 8, duration: 100, useNativeDriver: true}),
      Animated.timing(formShake, {toValue: 0, duration: 50, useNativeDriver: true}),
    ]).start();
  };

  const login = useMutation({
    mutationFn: () =>
      authApi.login({
        email: email.trim().toLowerCase(),
        password,
        device_name: 'PropOSMobile',
        platform: Platform.OS as 'ios' | 'android',
      }),
    onSuccess: async ({data}) => {
      // Store credentials securely for biometric unlock re-auth
      await Keychain.setGenericPassword(email.trim().toLowerCase(), password, {
        service: 'villacrm_credentials',
      });
      setAuth(data.token, data.user);
    },
    onError: (err: any) => {
      triggerFormShake();
      const message = err.response?.data?.message || 'Login failed. Please verify your credentials.';
      setLocalError(message);
    },
  });

  const handleSignIn = () => {
    setLocalError('');
    if (!email.trim()) {
      setLocalError('Email address is required.');
      triggerFormShake();
      return;
    }
    if (!password) {
      setLocalError('Password is required.');
      triggerFormShake();
      return;
    }
    login.mutate();
  };

  // Get Tenant/Agency Name from Subdomain
  const getAgencyName = () => {
    const baseUrl = apiClient.defaults.baseURL || '';
    if (baseUrl.includes('localhost') || baseUrl.includes('127.0.0.1') || baseUrl.match(/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/)) {
      return 'Dev Agency';
    }
    const match = baseUrl.match(/https?:\/\/([^/:]+)/);
    if (match && match[1]) {
      const parts = match[1].split('.');
      if (parts.length > 2) {
        const sub = parts[0];
        return sub.charAt(0).toUpperCase() + sub.slice(1);
      }
    }
    return 'PropOS HQ';
  };

  return (
    <SafeAreaView className="flex-1 bg-surface-page">
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        className="flex-1"
      >
        <ScrollView
          className="flex-1"
          contentContainerClassName="flex-grow justify-between px-6 pt-10 pb-6"
          keyboardShouldPersistTaps="handled"
        >
          {/* Top Third: Brand Wordmark with subtle Pulsing Glow */}
          <View className="items-center justify-center pt-8 relative min-h-[160px]">
            {/* Pulsing Emerald Glow */}
            <Animated.View
              style={{opacity: glowOpacity}}
              className="absolute w-48 h-48 bg-brand-500 rounded-full blur-[48px]"
            />
            <Text className="text-text-primary text-5xl font-black tracking-tight z-10 text-center">
              PropOS
            </Text>
            <Text className="text-brand-500 font-mono text-xs uppercase tracking-widest font-bold mt-1 z-10">
              Agent Field Companion
            </Text>
          </View>

          {/* Middle: Inputs Form Container with Shake Animation */}
          <Animated.View
            style={{transform: [{translateX: formShake}]}}
            className="w-full gap-5 my-auto justify-center"
          >
            {/* Email Input */}
            <View className="gap-2">
              <Text className="text-text-secondary text-sm font-bold ml-1">Email Address</Text>
              <TextInput
                className="w-full bg-surface-input text-text-primary px-5 rounded-[10px] text-base border"
                style={{
                  height: 52,
                  borderColor: emailFocused ? '#10B981' : '#27272a',
                  shadowColor: emailFocused ? '#10B981' : 'transparent',
                  shadowOpacity: emailFocused ? 0.15 : 0,
                  shadowRadius: 8,
                  shadowOffset: {width: 0, height: 0},
                }}
                placeholder="agent@propos.com"
                placeholderTextColor="#71717A"
                keyboardType="email-address"
                autoCapitalize="none"
                autoCorrect={false}
                value={email}
                onChangeText={setEmail}
                onFocus={() => {
                  setEmailFocused(true);
                  setLocalError('');
                }}
                onBlur={() => setEmailFocused(false)}
              />
            </View>

            {/* Password Input */}
            <View className="gap-2">
              <Text className="text-text-secondary text-sm font-bold ml-1">Password</Text>
              <View className="relative justify-center">
                <TextInput
                  className="w-full bg-surface-input text-text-primary pl-5 pr-14 rounded-[10px] text-base border"
                  style={{
                    height: 52,
                    borderColor: passwordFocused ? '#10B981' : '#27272a',
                    shadowColor: passwordFocused ? '#10B981' : 'transparent',
                    shadowOpacity: passwordFocused ? 0.15 : 0,
                    shadowRadius: 8,
                    shadowOffset: {width: 0, height: 0},
                  }}
                  placeholder="••••••••"
                  placeholderTextColor="#71717A"
                  secureTextEntry={!showPassword}
                  autoCapitalize="none"
                  autoCorrect={false}
                  value={password}
                  onChangeText={setPassword}
                  onFocus={() => {
                    setPasswordFocused(true);
                    setLocalError('');
                  }}
                  onBlur={() => setPasswordFocused(false)}
                  onSubmitEditing={handleSignIn}
                />
                <Pressable
                  onPress={() => setShowPassword(!showPassword)}
                  className="absolute right-4 h-full justify-center px-1 active:opacity-70"
                  hitSlop={{top: 10, bottom: 10, left: 10, right: 10}}
                >
                  <Icon
                    name={showPassword ? 'eye-off' : 'eye'}
                    size={20}
                    color="#A1A1AA"
                  />
                </Pressable>
              </View>
            </View>

            {/* Inline Error Message */}
            {localError ? (
              <View className="flex-row items-center gap-2 mt-1 px-1">
                <Icon name="alert-circle" size={16} color="#F43F5E" />
                <Text className="text-danger text-sm font-semibold flex-1">
                  {localError}
                </Text>
              </View>
            ) : null}
          </Animated.View>

          {/* Bottom third: CTA and Tenant details */}
          <View className="w-full gap-5 mt-auto pt-8">
            {/* Sign In Button */}
            <Pressable
              onPress={handleSignIn}
              disabled={login.isPending}
              className="w-full bg-brand-500 rounded-[10px] h-[52px] items-center justify-center shadow-lg shadow-brand-500/25 active:bg-brand-600"
            >
              {login.isPending ? (
                <View className="flex-row items-center justify-center gap-2">
                  <ActivityIndicator color="#FAFAFA" size="small" />
                  <Text className="text-text-primary font-bold text-lg">Signing in...</Text>
                </View>
              ) : (
                <Text className="text-text-primary font-bold text-lg">Sign In</Text>
              )}
            </Pressable>

            {/* Forgot password */}
            <Pressable className="items-center py-2 active:opacity-75">
              <Text className="text-accent font-bold text-sm">Forgot password?</Text>
            </Pressable>

            {/* Agency tenant details */}
            <View className="items-center mt-2">
              <Text className="text-text-tertiary text-xs font-semibold">
                Agency: <Text className="text-text-secondary">{getAgencyName()}</Text>
              </Text>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
