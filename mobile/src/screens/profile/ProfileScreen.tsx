import React, {useState} from 'react';
import {
  Alert,
  Pressable,
  ScrollView,
  Switch,
  Text,
  View,
} from 'react-native';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useAuthStore} from '../../store/authStore';
import {authApi} from '../../api/auth';
import {benchmarkApi} from '../../api/benchmark';
import {cacheService} from '../../services/cacheService';

const LANGUAGES = [
  {code: 'en', label: 'English'},
  {code: 'fr', label: 'Français'},
  {code: 'yo', label: 'Yorùbá'},
  {code: 'ig', label: 'Igbo'},
  {code: 'ha', label: 'Hausa'},
  {code: 'pt', label: 'Português'},
  {code: 'ar', label: 'العربية'},
];

export function ProfileScreen() {
  const {user, clearAuth} = useAuthStore();
  const queryClient = useQueryClient();

  const [selectedLang, setSelectedLang] = useState('en');
  const [showLangPicker, setShowLangPicker] = useState(false);

  const logout = useMutation({
    mutationFn: () => authApi.logout(),
    onSuccess: () => {
      cacheService.clearAll();
      queryClient.clear();
      clearAuth();
    },
    onError: () => {
      // Even if the server call fails, clear local state
      cacheService.clearAll();
      queryClient.clear();
      clearAuth();
    },
  });

  const setLanguage = useMutation({
    mutationFn: (lang: string) => benchmarkApi.setLanguage(lang),
    onSuccess: (_, lang) => {
      setSelectedLang(lang);
      setShowLangPicker(false);
    },
    onError: () => Alert.alert('Error', 'Could not update language preference.'),
  });

  const handleLogout = () => {
    Alert.alert('Sign out', 'Are you sure you want to sign out?', [
      {text: 'Cancel', style: 'cancel'},
      {text: 'Sign out', style: 'destructive', onPress: () => logout.mutate()},
    ]);
  };

  const fullName = user ? `${user.first_name} ${user.last_name}` : '';
  const avatarInitials = user
    ? user.first_name.charAt(0) + user.last_name.charAt(0)
    : '??';

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="pb-10">
      {/* Header */}
      <View className="pt-14 px-4 pb-6 border-b border-slate-800">
        <View className="flex-row items-center gap-4">
          <View className="w-16 h-16 rounded-full bg-brand-700 items-center justify-center">
            <Text className="text-white text-2xl font-bold">{avatarInitials}</Text>
          </View>
          <View>
            <Text className="text-white text-xl font-bold">{fullName}</Text>
            <Text className="text-slate-400 text-sm mt-0.5">{user?.email}</Text>
            {user?.job_title && (
              <Text className="text-slate-500 text-xs mt-0.5">{user.job_title}</Text>
            )}
          </View>
        </View>
      </View>

      {/* Settings sections */}
      <View className="px-4 mt-6">

        {/* Transcription language */}
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
          Call transcription language
        </Text>
        <Pressable
          className="bg-surface-card rounded-xl px-4 py-3.5 flex-row items-center justify-between mb-5"
          onPress={() => setShowLangPicker(v => !v)}>
          <Text className="text-white text-sm">
            {LANGUAGES.find(l => l.code === selectedLang)?.label ?? 'English'}
          </Text>
          <Text className="text-slate-500 text-xs">{showLangPicker ? '▲' : '▼'}</Text>
        </Pressable>

        {showLangPicker && (
          <View className="bg-surface-card rounded-xl mb-5 overflow-hidden">
            {LANGUAGES.map(lang => (
              <Pressable
                key={lang.code}
                className={`flex-row items-center px-4 py-3 border-b border-slate-800 ${
                  selectedLang === lang.code ? 'bg-brand-900/40' : ''
                }`}
                onPress={() => setLanguage.mutate(lang.code)}>
                <Text className={`flex-1 text-sm ${selectedLang === lang.code ? 'text-brand-400 font-semibold' : 'text-white'}`}>
                  {lang.label}
                </Text>
                {selectedLang === lang.code && (
                  <Text className="text-brand-500">✓</Text>
                )}
              </Pressable>
            ))}
          </View>
        )}

        {/* App info */}
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
          About
        </Text>
        <View className="bg-surface-card rounded-xl mb-5">
          {[
            {label: 'App version', value: '1.0.0'},
            {label: 'Agency ID', value: String(user?.agency_id ?? '—')},
          ].map(row => (
            <View
              key={row.label}
              className="flex-row items-center justify-between px-4 py-3 border-b border-slate-800 last:border-0">
              <Text className="text-slate-400 text-sm">{row.label}</Text>
              <Text className="text-slate-300 text-sm">{row.value}</Text>
            </View>
          ))}
        </View>

        {/* Sign out */}
        <Pressable
          className="bg-red-950 border border-red-800 rounded-xl py-4 items-center"
          onPress={handleLogout}>
          <Text className="text-red-400 font-semibold">Sign out</Text>
        </Pressable>
      </View>
    </ScrollView>
  );
}
