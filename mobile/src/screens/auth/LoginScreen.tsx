import React, {useState} from 'react';
import {
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  Text,
  TextInput,
  View,
  ActivityIndicator,
  SafeAreaView,
} from 'react-native';
import {useMutation} from '@tanstack/react-query';
import ReactNativeBiometrics from 'react-native-biometrics';
import * as Keychain from 'react-native-keychain';
import {authApi} from '../../api/auth';
import {useAuthStore} from '../../store/authStore';

const rnb = new ReactNativeBiometrics();

export function LoginScreen() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const {setAuth} = useAuthStore();

  const login = useMutation({
    mutationFn: () =>
      authApi.login({
        email: email.trim().toLowerCase(),
        password,
        device_name: 'VillaCRMMobile',
        platform: Platform.OS as 'ios' | 'android',
      }),
    onSuccess: async ({data}) => {
      // Store credentials for biometric re-auth
      await Keychain.setGenericPassword(email, password, {
        service: 'villacrm_credentials',
      });
      setAuth(data.token, data.user);
    },
    onError: () => {
      Alert.alert('Login failed', 'Check your email and password and try again.');
    },
  });

  const handleBiometricLogin = async () => {
    const {available} = await rnb.isSensorAvailable();
    if (!available) {
      Alert.alert('Biometrics unavailable', 'Please log in with your email and password.');
      return;
    }

    const credentials = await Keychain.getGenericPassword({service: 'villacrm_credentials'});
    if (!credentials) {
      Alert.alert('No saved credentials', 'Please log in with your email and password first.');
      return;
    }

    const {success} = await rnb.simplePrompt({
      promptMessage: 'Authenticate to access VillaCRM',
    });

    if (success) {
      setEmail(credentials.username);
      setPassword(credentials.password);
      login.mutate();
    }
  };

  return (
    <SafeAreaView className="flex-1 bg-brand-600">
      <KeyboardAvoidingView
        behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
        className="flex-1 justify-center px-6">
        
        {/* Decorative Background Elements */}
        <View className="absolute -top-32 -left-32 w-96 h-96 bg-brand-500 rounded-full opacity-50 blur-3xl" />
        <View className="absolute -bottom-32 -right-32 w-96 h-96 bg-brand-700 rounded-full opacity-50 blur-3xl" />

        <View className="mb-10 mt-8">
          <Text className="text-white text-5xl font-extrabold tracking-tight">VillaCRM</Text>
          <Text className="text-brand-100 text-lg mt-2 font-medium opacity-90">Agent Field App</Text>
        </View>

        <View className="bg-white rounded-3xl p-6 shadow-2xl shadow-brand-900/50 gap-5">
          <View>
            <Text className="text-slate-700 text-sm font-bold mb-2 ml-1">Email Address</Text>
            <TextInput
              className="bg-slate-50 text-slate-900 rounded-2xl px-5 py-4 text-base border border-slate-200 focus:border-brand-500 focus:bg-white"
              placeholder="agent@villacrm.com"
              placeholderTextColor="#94a3b8"
              keyboardType="email-address"
              autoCapitalize="none"
              value={email}
              onChangeText={setEmail}
            />
          </View>

          <View>
            <Text className="text-slate-700 text-sm font-bold mb-2 ml-1">Password</Text>
            <TextInput
              className="bg-slate-50 text-slate-900 rounded-2xl px-5 py-4 text-base border border-slate-200 focus:border-brand-500 focus:bg-white"
              placeholder="••••••••"
              placeholderTextColor="#94a3b8"
              secureTextEntry
              value={password}
              onChangeText={setPassword}
            />
          </View>

          <Pressable
            className="bg-brand-600 rounded-2xl py-4 items-center mt-4 shadow-lg shadow-brand-500/30 active:bg-brand-700"
            onPress={() => login.mutate()}
            disabled={login.isPending}>
            {login.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text className="text-white font-bold text-lg tracking-wide">Sign In</Text>
            )}
          </Pressable>

          <View className="flex-row items-center my-2">
            <View className="flex-1 h-px bg-slate-200" />
            <Text className="text-slate-400 px-4 text-sm font-bold">OR</Text>
            <View className="flex-1 h-px bg-slate-200" />
          </View>

          <Pressable
            className="rounded-2xl py-4 items-center bg-white border border-slate-200 active:bg-slate-50"
            onPress={handleBiometricLogin}>
            <Text className="text-brand-600 font-bold text-base">Use Face ID / Fingerprint</Text>
          </Pressable>
        </View>
      </KeyboardAvoidingView>
    </SafeAreaView>
  );
}
