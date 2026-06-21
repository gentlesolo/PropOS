import React, {useState} from 'react';
import {Alert, Modal, Pressable, ScrollView, Text, View} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation, useQueryClient} from '@tanstack/react-query';
import {useAuthStore} from '../../store/authStore';
import {authApi} from '../../api/auth';
import {benchmarkApi} from '../../api/benchmark';
import {cacheService} from '../../services/cacheService';
import {useTranslation} from '../../i18n';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme, ThemePreference} from '../../theme/ThemeProvider';

const LANGUAGES = [
  {code: 'en', label: 'English'},
  {code: 'fr', label: 'Français'},
  {code: 'yo', label: 'Yorùbá'},
  {code: 'ig', label: 'Igbo'},
  {code: 'ha', label: 'Hausa'},
  {code: 'pt', label: 'Português'},
  {code: 'ar', label: 'العربية'},
];

const APPEARANCE_OPTIONS: {value: ThemePreference; label: string; icon: string; description: string}[] = [
  {value: 'light',  label: 'Light',  icon: 'sun',     description: 'Always use light mode'},
  {value: 'dark',   label: 'Dark',   icon: 'moon',    description: 'Always use dark mode'},
  {value: 'system', label: 'System', icon: 'monitor', description: 'Follow device setting'},
];

export function ProfileScreen() {
  const {t} = useTranslation();
  const {tokens, preference, setPreference} = useTheme();
  const {user, clearAuth} = useAuthStore();
  const queryClient = useQueryClient();

  const [selectedLang, setSelectedLang] = useState('en');
  const [showLangPicker, setShowLangPicker] = useState(false);
  const [appearanceSheetVisible, setAppearanceSheetVisible] = useState(false);

  const logout = useMutation({
    mutationFn: () => authApi.logout(),
    onSuccess: () => { cacheService.clearAll(); queryClient.clear(); clearAuth(); },
    onError:   () => { cacheService.clearAll(); queryClient.clear(); clearAuth(); },
  });

  const setLanguage = useMutation({
    mutationFn: (lang: string) => benchmarkApi.setLanguage(lang),
    onSuccess: (_, lang) => { setSelectedLang(lang); setShowLangPicker(false); },
    onError:   () => Alert.alert(t('common.error'), t('profile.languageError')),
  });

  const handleLogout = () => {
    Alert.alert(t('profile.signOutTitle'), t('profile.signOutMessage'), [
      {text: t('common.cancel'), style: 'cancel'},
      {text: t('profile.signOut'), style: 'destructive', onPress: () => logout.mutate()},
    ]);
  };

  const handleAppearanceSelect = (value: ThemePreference) => {
    setPreference(value);
    setTimeout(() => setAppearanceSheetVisible(false), 150);
  };

  const fullName = user ? `${user.first_name} ${user.last_name}` : '';
  const avatarInitials = user ? user.first_name.charAt(0) + user.last_name.charAt(0) : '??';

  const currentAppearanceLabel = APPEARANCE_OPTIONS.find((o) => o.value === preference)?.label ?? 'Dark';

  const sectionLabelStyle = {color: tokens.textTertiary, fontSize: 11, fontWeight: '900' as const, textTransform: 'uppercase' as const, letterSpacing: 2, marginBottom: 12, marginLeft: 4};
  const cardStyle = {backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 16};

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      <ScrollView style={{flex: 1}} contentContainerStyle={{paddingBottom: 40}}>
        {/* Profile header */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderBottomWidth: 1,
            borderBottomColor: tokens.borderDefault,
            paddingBottom: 40,
            paddingTop: 32,
            paddingHorizontal: 24,
            marginBottom: 24,
          }}
        >
          <Text style={{color: tokens.textPrimary, fontSize: 30, fontWeight: '800', letterSpacing: -0.5, marginBottom: 32}}>Profile</Text>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 20}}>
            <View
              style={{
                width: 80,
                height: 80,
                borderRadius: 40,
                backgroundColor: `${tokens.brandPrimary}1A`,
                borderWidth: 2,
                borderColor: `${tokens.brandPrimary}33`,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontSize: 30, fontWeight: '800'}}>{avatarInitials}</Text>
            </View>
            <View style={{flex: 1}}>
              <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '800', letterSpacing: -0.5}}>{fullName}</Text>
              <Text style={{color: tokens.textSecondary, fontWeight: '600', marginTop: 2}}>{user?.email}</Text>
              {user?.job_title && (
                <View
                  style={{
                    backgroundColor: `${tokens.brandPrimary}1A`,
                    alignSelf: 'flex-start',
                    paddingHorizontal: 8,
                    paddingVertical: 2,
                    borderRadius: 6,
                    marginTop: 8,
                    borderWidth: 1,
                    borderColor: `${tokens.brandPrimary}26`,
                  }}
                >
                  <Text style={{color: tokens.brandPrimary, fontSize: 11, fontWeight: '700', textTransform: 'uppercase', letterSpacing: 0.5}}>
                    {user.job_title}
                  </Text>
                </View>
              )}
            </View>
          </View>
        </View>

        <View style={{paddingHorizontal: 20}}>
          {/* Transcription language */}
          <Text style={sectionLabelStyle}>{t('profile.transcriptionLanguage')}</Text>
          <Pressable
            style={[cardStyle, {paddingHorizontal: 20, paddingVertical: 16, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 6}]}
            onPress={() => setShowLangPicker((v) => !v)}
          >
            <Text style={{color: tokens.textPrimary, fontWeight: '700', fontSize: 16}}>
              {LANGUAGES.find((l) => l.code === selectedLang)?.label ?? 'English'}
            </Text>
            <View style={{backgroundColor: tokens.surfaceRaised, width: 32, height: 32, borderRadius: 16, alignItems: 'center', justifyContent: 'center'}}>
              <Icon name={showLangPicker ? 'chevron-up' : 'chevron-down'} size={16} color={tokens.textSecondary} />
            </View>
          </Pressable>

          {showLangPicker && (
            <View style={[cardStyle, {marginBottom: 24, overflow: 'hidden', marginTop: 2}]}>
              {LANGUAGES.map((lang, idx) => {
                const isSelected = selectedLang === lang.code;
                return (
                  <Pressable
                    key={lang.code}
                    style={[
                      {flexDirection: 'row', alignItems: 'center', paddingHorizontal: 20, paddingVertical: 16},
                      isSelected && {backgroundColor: `${tokens.brandPrimary}0D`},
                      idx < LANGUAGES.length - 1 && {borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle},
                    ]}
                    onPress={() => setLanguage.mutate(lang.code)}
                  >
                    <Text style={{flex: 1, fontSize: 16, fontWeight: '700', color: isSelected ? tokens.brandPrimary : tokens.textPrimary}}>
                      {lang.label}
                    </Text>
                    {isSelected && <Icon name="check" size={18} color={tokens.brandPrimary} />}
                  </Pressable>
                );
              })}
            </View>
          )}

          {/* Appearance */}
          <Text style={[sectionLabelStyle, {marginTop: 8}]}>Appearance</Text>
          <Pressable
            style={[cardStyle, {paddingHorizontal: 20, paddingVertical: 16, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 24}]}
            onPress={() => setAppearanceSheetVisible(true)}
          >
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
              <Icon
                name={APPEARANCE_OPTIONS.find((o) => o.value === preference)?.icon ?? 'moon'}
                size={18}
                color={tokens.brandPrimary}
              />
              <Text style={{color: tokens.textPrimary, fontWeight: '700', fontSize: 16}}>{currentAppearanceLabel}</Text>
            </View>
            <Icon name="chevron-right" size={16} color={tokens.borderStrong} />
          </Pressable>

          {/* App info */}
          <Text style={[sectionLabelStyle, {marginTop: 4}]}>{t('profile.about')}</Text>
          <View style={[cardStyle, {marginBottom: 32}]}>
            {[
              {label: t('profile.appVersion'), value: '1.0.0'},
              {label: t('profile.agencyId'),   value: String(user?.agency_id ?? '—')},
            ].map((row, idx) => (
              <View
                key={row.label}
                style={[
                  {flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, paddingVertical: 16},
                  idx === 0 && {borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle},
                ]}
              >
                <Text style={{color: tokens.textSecondary, fontWeight: '700', fontSize: 16}}>{row.label}</Text>
                <Text style={{color: tokens.textTertiary, fontWeight: '700'}}>{row.value}</Text>
              </View>
            ))}
          </View>

          {/* Sign out */}
          <Pressable
            style={({pressed}) => ({
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: '#F43F5E33',
              borderRadius: 16,
              paddingVertical: 16,
              alignItems: 'center',
              marginTop: 8,
              opacity: pressed ? 0.8 : 1,
            })}
            onPress={handleLogout}
          >
            <Text style={{color: '#F43F5E', fontWeight: '800', fontSize: 18}}>{t('profile.signOut')}</Text>
          </Pressable>
        </View>
      </ScrollView>

      {/* Appearance bottom sheet */}
      <Modal
        visible={appearanceSheetVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setAppearanceSheetVisible(false)}
      >
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setAppearanceSheetVisible(false)} />
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              borderTopWidth: 1,
              borderTopColor: tokens.borderDefault,
              padding: 20,
              paddingBottom: 36,
            }}
          >
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 20}} />
            <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '900', marginBottom: 4}}>Appearance</Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, marginBottom: 20}}>Choose how VillaCRM looks on this device.</Text>

            <View style={{gap: 10}}>
              {APPEARANCE_OPTIONS.map((opt) => {
                const isSelected = preference === opt.value;
                return (
                  <Pressable
                    key={opt.value}
                    onPress={() => handleAppearanceSelect(opt.value)}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      paddingHorizontal: 16,
                      paddingVertical: 16,
                      borderRadius: 14,
                      borderWidth: 1.5,
                      backgroundColor: isSelected ? `${tokens.brandPrimary}0D` : tokens.surfaceRaised,
                      borderColor: isSelected ? tokens.brandPrimary : tokens.borderDefault,
                    }}
                  >
                    <View
                      style={{
                        width: 40,
                        height: 40,
                        borderRadius: 20,
                        backgroundColor: isSelected ? `${tokens.brandPrimary}1A` : tokens.surfacePage,
                        borderWidth: 1,
                        borderColor: isSelected ? `${tokens.brandPrimary}33` : tokens.borderDefault,
                        alignItems: 'center',
                        justifyContent: 'center',
                        marginRight: 14,
                      }}
                    >
                      <Icon name={opt.icon} size={18} color={isSelected ? tokens.brandPrimary : tokens.textTertiary} />
                    </View>

                    <View style={{flex: 1}}>
                      <Text style={{color: isSelected ? tokens.brandPrimary : tokens.textPrimary, fontWeight: '800', fontSize: 16}}>
                        {opt.label}
                      </Text>
                      <Text style={{color: tokens.textSecondary, fontSize: 12, marginTop: 2}}>{opt.description}</Text>
                    </View>

                    {isSelected && (
                      <View
                        style={{
                          width: 24,
                          height: 24,
                          borderRadius: 12,
                          backgroundColor: tokens.brandPrimary,
                          alignItems: 'center',
                          justifyContent: 'center',
                        }}
                      >
                        <Icon name="check" size={13} color="#ffffff" />
                      </View>
                    )}
                  </Pressable>
                );
              })}
            </View>
          </View>
        </View>
      </Modal>
    </SafeAreaView>
  );
}
