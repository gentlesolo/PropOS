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
import {useTheme} from '../../theme/ThemeProvider';

export function LoginScreen() {
  const {tokens, resolvedTheme} = useTheme();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [emailFocused, setEmailFocused] = useState(false);
  const [passwordFocused, setPasswordFocused] = useState(false);
  const [localError, setLocalError] = useState('');

  const {setAuth} = useAuthStore();

  const glowOpacity = useRef(new Animated.Value(0.12)).current;
  const formShake = useRef(new Animated.Value(0)).current;

  // Light mode: much fainter glow (emerald-50 wash)
  const glowMax = resolvedTheme === 'light' ? 0.06 : 0.28;
  const glowMin = resolvedTheme === 'light' ? 0.02 : 0.12;

  useEffect(() => {
    glowOpacity.setValue(glowMin);
    const pulse = Animated.loop(
      Animated.sequence([
        Animated.timing(glowOpacity, {
          toValue: glowMax,
          duration: 3500,
          useNativeDriver: true,
        }),
        Animated.timing(glowOpacity, {
          toValue: glowMin,
          duration: 3500,
          useNativeDriver: true,
        }),
      ])
    );
    pulse.start();
    return () => pulse.stop();
  }, [resolvedTheme]);

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

  const getAgencyName = () => {
    const baseUrl = apiClient.defaults.baseURL || '';
    if (
      baseUrl.includes('localhost') ||
      baseUrl.includes('127.0.0.1') ||
      baseUrl.match(/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/)
    ) {
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
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : undefined}
        className="flex-1"
      >
        <ScrollView
          className="flex-1"
          contentContainerClassName="flex-grow justify-between px-6 pt-10 pb-6"
          keyboardShouldPersistTaps="handled"
        >
          {/* Brand Wordmark with subtle Pulsing Glow */}
          <View className="items-center justify-center pt-8 relative min-h-[160px]">
            <Animated.View
              style={{opacity: glowOpacity}}
              className="absolute w-48 h-48 bg-brand-500 rounded-full blur-[48px]"
            />
            <Text style={{color: tokens.textPrimary}} className="text-5xl font-black tracking-tight z-10 text-center">
              PropOS
            </Text>
            <Text className="text-brand-500 font-mono text-xs uppercase tracking-widest font-bold mt-1 z-10">
              Agent Field Companion
            </Text>
          </View>

          {/* Inputs Form */}
          <Animated.View
            style={{transform: [{translateX: formShake}]}}
            className="w-full gap-5 my-auto justify-center"
          >
            {/* Email Input */}
            <View className="gap-2">
              <Text style={{color: tokens.textSecondary}} className="text-sm font-bold ml-1">
                Email Address
              </Text>
              <TextInput
                style={{
                  height: 52,
                  backgroundColor: tokens.surfaceInput,
                  color: tokens.textPrimary,
                  borderColor: emailFocused ? tokens.brandPrimary : tokens.borderStrong,
                  borderWidth: 1,
                  borderRadius: 10,
                  paddingHorizontal: 20,
                  fontSize: 16,
                  shadowColor: emailFocused ? tokens.brandPrimary : 'transparent',
                  shadowOpacity: emailFocused ? 0.15 : 0,
                  shadowRadius: 8,
                  shadowOffset: {width: 0, height: 0},
                }}
                placeholder="agent@propos.com"
                placeholderTextColor={tokens.textTertiary}
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
              <Text style={{color: tokens.textSecondary}} className="text-sm font-bold ml-1">
                Password
              </Text>
              <View style={{position: 'relative', justifyContent: 'center'}}>
                <TextInput
                  style={{
                    height: 52,
                    backgroundColor: tokens.surfaceInput,
                    color: tokens.textPrimary,
                    borderColor: passwordFocused ? tokens.brandPrimary : tokens.borderStrong,
                    borderWidth: 1,
                    borderRadius: 10,
                    paddingLeft: 20,
                    paddingRight: 56,
                    fontSize: 16,
                    shadowColor: passwordFocused ? tokens.brandPrimary : 'transparent',
                    shadowOpacity: passwordFocused ? 0.15 : 0,
                    shadowRadius: 8,
                    shadowOffset: {width: 0, height: 0},
                  }}
                  placeholder="••••••••"
                  placeholderTextColor={tokens.textTertiary}
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
                  hitSlop={{top: 10, bottom: 10, left: 10, right: 10}}
                  style={{
                    position: 'absolute',
                    right: 16,
                    height: 52,
                    justifyContent: 'center',
                    alignItems: 'center',
                    paddingHorizontal: 4,
                  }}
                >
                  <Icon
                    name={showPassword ? 'eye-off' : 'eye'}
                    size={20}
                    color={showPassword ? tokens.brandPrimary : tokens.textTertiary}
                  />
                </Pressable>
              </View>
            </View>

            {/* Inline Error */}
            {localError ? (
              <View className="flex-row items-center gap-2 mt-1 px-1">
                <Icon name="alert-circle" size={16} color={tokens.dangerText} />
                <Text style={{color: tokens.dangerText}} className="text-sm font-semibold flex-1">
                  {localError}
                </Text>
              </View>
            ) : null}
          </Animated.View>

          {/* CTA and Tenant details */}
          <View className="w-full gap-5 mt-auto pt-8">
            <Pressable
              onPress={handleSignIn}
              disabled={login.isPending}
              className="w-full bg-brand-500 rounded-[10px] h-[52px] items-center justify-center shadow-lg shadow-brand-500/25 active:bg-brand-600"
            >
              {login.isPending ? (
                <View className="flex-row items-center justify-center gap-2">
                  <ActivityIndicator color="#FAFAFA" size="small" />
                  <Text style={{color: tokens.textInverse}} className="font-bold text-lg">
                    Signing in...
                  </Text>
                </View>
              ) : (
                <Text style={{color: tokens.textInverse}} className="font-bold text-lg">
                  Sign In
                </Text>
              )}
            </Pressable>

            <Pressable className="items-center py-2 active:opacity-75">
              <Text style={{color: tokens.brandAccent}} className="font-bold text-sm">
                Forgot password?
              </Text>
            </Pressable>

            <View className="items-center mt-2">
              <Text style={{color: tokens.textTertiary}} className="text-xs font-semibold">
                Agency:{' '}
                <Text style={{color: tokens.textSecondary}}>{getAgencyName()}</Text>
              </Text>
            </View>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
