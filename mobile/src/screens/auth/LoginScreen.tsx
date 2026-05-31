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
        device_name: 'ProposMobile',
        platform: Platform.OS as 'ios' | 'android',
      }),
    onSuccess: async ({data}) => {
      // Store credentials for biometric re-auth
      await Keychain.setGenericPassword(email, password, {
        service: 'propos_credentials',
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

    const credentials = await Keychain.getGenericPassword({service: 'propos_credentials'});
    if (!credentials) {
      Alert.alert('No saved credentials', 'Please log in with your email and password first.');
      return;
    }

    const {success} = await rnb.simplePrompt({
      promptMessage: 'Authenticate to access PropOS',
    });

    if (success) {
      setEmail(credentials.username);
      setPassword(credentials.password);
      login.mutate();
    }
  };

  return (
    <KeyboardAvoidingView
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      className="flex-1 bg-surface justify-center px-6">

      <View className="mb-10">
        <Text className="text-white text-3xl font-bold">PropOS</Text>
        <Text className="text-slate-400 text-base mt-1">Agent Field App</Text>
      </View>

      <View className="gap-4">
        <TextInput
          className="bg-surface-input text-white rounded-xl px-4 py-3.5 text-base"
          placeholder="Email"
          placeholderTextColor="#64748b"
          keyboardType="email-address"
          autoCapitalize="none"
          value={email}
          onChangeText={setEmail}
        />

        <TextInput
          className="bg-surface-input text-white rounded-xl px-4 py-3.5 text-base"
          placeholder="Password"
          placeholderTextColor="#64748b"
          secureTextEntry
          value={password}
          onChangeText={setPassword}
        />

        <Pressable
          className="bg-brand-600 rounded-xl py-4 items-center mt-2"
          onPress={() => login.mutate()}
          disabled={login.isPending}>
          {login.isPending ? (
            <ActivityIndicator color="#fff" />
          ) : (
            <Text className="text-white font-semibold text-base">Sign in</Text>
          )}
        </Pressable>

        <Pressable
          className="rounded-xl py-4 items-center border border-slate-700"
          onPress={handleBiometricLogin}>
          <Text className="text-slate-300 text-base">Use Face ID / Fingerprint</Text>
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
}
