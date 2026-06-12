import React, {useState} from 'react';
import {
  Alert,
  Pressable,
  ScrollView,
  Text,
  View,
  SafeAreaView,
} from 'react-native';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useAuthStore} from '../../store/authStore';
import {authApi} from '../../api/auth';
import {benchmarkApi} from '../../api/benchmark';
import {cacheService} from '../../services/cacheService';
import {useTranslation} from '../../i18n';

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
  const {t} = useTranslation();
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
    onError: () => Alert.alert(t('common.error'), t('profile.languageError')),
  });

  const handleLogout = () => {
    Alert.alert(t('profile.signOutTitle'), t('profile.signOutMessage'), [
      {text: t('common.cancel'), style: 'cancel'},
      {text: t('profile.signOut'), style: 'destructive', onPress: () => logout.mutate()},
    ]);
  };

  const fullName = user ? `${user.first_name} ${user.last_name}` : '';
  const avatarInitials = user
    ? user.first_name.charAt(0) + user.last_name.charAt(0)
    : '??';

  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      <ScrollView className="flex-1" contentContainerClassName="pb-10">
        {/* Header */}
        <View className="bg-white shadow-sm border-b border-slate-100 pb-10 pt-8 px-6 mb-6">
          <Text className="text-slate-900 text-3xl font-extrabold tracking-tight mb-8">Profile</Text>
          <View className="flex-row items-center gap-5">
            <View className="w-20 h-20 rounded-full bg-brand-50 border-2 border-brand-100 items-center justify-center shadow-sm">
              <Text className="text-brand-600 text-3xl font-extrabold">{avatarInitials}</Text>
            </View>
            <View className="flex-1">
              <Text className="text-slate-900 text-2xl font-extrabold tracking-tight">{fullName}</Text>
              <Text className="text-slate-500 font-bold mt-0.5">{user?.email}</Text>
              {user?.job_title && (
                <View className="bg-brand-50 self-start px-2 py-0.5 rounded-md mt-2 border border-brand-100">
                  <Text className="text-brand-700 text-xs font-bold uppercase tracking-wide">{user.job_title}</Text>
                </View>
              )}
            </View>
          </View>
        </View>

        {/* Settings sections */}
        <View className="px-5 mt-2">

          {/* Transcription language */}
          <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest mb-3 ml-2">
            {t('profile.transcriptionLanguage')}
          </Text>
          <Pressable
            className="bg-white shadow-sm border border-slate-100 rounded-2xl px-5 py-4 flex-row items-center justify-between mb-6"
            onPress={() => setShowLangPicker(v => !v)}>
            <Text className="text-slate-900 font-bold text-base">
              {LANGUAGES.find(l => l.code === selectedLang)?.label ?? 'English'}
            </Text>
            <View className="bg-slate-50 w-8 h-8 rounded-full items-center justify-center">
              <Text className="text-slate-500 text-xs font-bold">{showLangPicker ? '▲' : '▼'}</Text>
            </View>
          </Pressable>

          {showLangPicker && (
            <View className="bg-white shadow-md border border-slate-100 rounded-2xl mb-6 overflow-hidden -mt-4">
              {LANGUAGES.map(lang => (
                <Pressable
                  key={lang.code}
                  className={`flex-row items-center px-5 py-4 border-b border-slate-50 ${
                    selectedLang === lang.code ? 'bg-brand-50' : ''
                  }`}
                  onPress={() => setLanguage.mutate(lang.code)}>
                  <Text className={`flex-1 text-base font-bold ${selectedLang === lang.code ? 'text-brand-600' : 'text-slate-700'}`}>
                    {lang.label}
                  </Text>
                  {selectedLang === lang.code && (
                    <Text className="text-brand-600 font-bold text-lg">✓</Text>
                  )}
                </Pressable>
              ))}
            </View>
          )}

          {/* App info */}
          <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest mb-3 ml-2 mt-4">
            {t('profile.about')}
          </Text>
          <View className="bg-white shadow-sm border border-slate-100 rounded-2xl mb-8">
            {[
              {label: t('profile.appVersion'), value: '1.0.0'},
              {label: t('profile.agencyId'),   value: String(user?.agency_id ?? '—')},
            ].map((row, idx) => (
              <View
                key={row.label}
                className={`flex-row items-center justify-between px-5 py-4 ${idx === 0 ? 'border-b border-slate-50' : ''}`}>
                <Text className="text-slate-600 font-bold text-base">{row.label}</Text>
                <Text className="text-slate-400 font-bold">{row.value}</Text>
              </View>
            ))}
          </View>

          {/* Sign out */}
          <Pressable
            className="bg-white border border-red-200 shadow-sm rounded-2xl py-4 items-center mt-2 active:bg-red-50"
            onPress={handleLogout}>
            <Text className="text-red-600 font-extrabold text-lg">{t('profile.signOut')}</Text>
          </Pressable>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
