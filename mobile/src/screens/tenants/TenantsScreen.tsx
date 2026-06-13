import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {tenantsApi, TenantListItem} from '../../api/tenants';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTranslation} from '../../i18n';

type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

const STATUS_COLORS: Record<string, string> = {
  active:      'bg-green-50 text-green-700 border-green-200',
  prospect:    'bg-blue-50 text-blue-700 border-blue-200',
  vacating:    'bg-amber-50 text-amber-700 border-amber-200',
  vacated:     'bg-slate-100 text-slate-700 border-slate-200',
  blacklisted: 'bg-red-50 text-red-700 border-red-200',
};

function TenantRow({tenant, onPress}: {tenant: TenantListItem; onPress: () => void}) {
  const {t} = useTranslation();
  const initials = (tenant.full_name ?? 'T')
    .split(' ')
    .map(n => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  return (
    <Pressable
      className="bg-white shadow-sm border border-slate-100 rounded-3xl p-5 mb-4 mx-5 flex-row items-center"
      onPress={onPress}>
      <View className="w-14 h-14 rounded-full bg-brand-50 border border-brand-100 items-center justify-center mr-4">
        <Text className="text-brand-600 font-extrabold text-lg">{initials}</Text>
      </View>
      <View className="flex-1 pr-2">
        <Text className="text-slate-900 font-bold text-base mb-0.5">{tenant.full_name ?? t('tenants.noTenant')}</Text>
        <Text className="text-slate-500 font-medium text-sm" numberOfLines={1}>
          {tenant.property ?? t('tenants.noProperty')}
        </Text>
        {tenant.monthly_rent != null && (
          <Text className="text-slate-500 font-bold text-xs mt-1 uppercase tracking-wide">
            R{tenant.monthly_rent.toLocaleString()} {t('tenants.perMonth', '/ mo')}
          </Text>
        )}
      </View>
      <View className="items-end gap-2">
        <View className={`px-2.5 py-1 rounded-md border ${STATUS_COLORS[tenant.status] ?? 'bg-slate-50 text-slate-600 border-slate-200'}`}>
          <Text className={`text-[10px] font-bold uppercase tracking-widest ${STATUS_COLORS[tenant.status]?.split(' ')[1] || 'text-slate-600'}`}>
            {tenant.status}
          </Text>
        </View>
        {tenant.fica_count === 0 && (
          <View className="bg-amber-50 px-2 py-0.5 rounded border border-amber-100">
             <Text className="text-amber-600 font-bold text-[10px] uppercase">{t('tenants.ficaPending')}</Text>
          </View>
        )}
      </View>
    </Pressable>
  );
}

export function TenantsScreen() {
  const {t} = useTranslation();
  const navigation = useNavigation<NavProp>();
  const [search, setSearch]   = useState('');
  const [status, setStatus]   = useState('');

  const filters = [
    {label: t('tenants.filterAll'),      value: ''},
    {label: t('tenants.filterActive'),   value: 'active'},
    {label: t('tenants.filterProspect'), value: 'prospect'},
    {label: t('tenants.filterVacating'), value: 'vacating'},
  ];

  const {data, isLoading, refetch, isRefetching} = useQuery({
    queryKey: ['tenants', status, search],
    queryFn:  () => tenantsApi.list({status: status || undefined, search: search || undefined}),
  });

  const tenants = data?.data?.data ?? [];

  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      {/* Header */}
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">{t('tenants.title')}</Text>
          <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
            <Text className="text-brand-600 font-bold text-lg">{tenants.length || 0}</Text>
          </View>
        </View>
        <View className="flex-row items-center bg-slate-50 rounded-2xl px-4 py-3 border border-slate-200 mb-4">
          <Text className="text-slate-400 mr-2">🔍</Text>
          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder={t('tenants.searchPlaceholder')}
            placeholderTextColor="#94a3b8"
            className="flex-1 text-slate-900 text-base"
            clearButtonMode="while-editing"
          />
        </View>
        {/* Filter pills */}
        <View className="flex-row gap-2">
          {filters.map(f => (
            <Pressable
              key={f.value}
              onPress={() => setStatus(f.value)}
              className={`px-4 py-2 rounded-full border ${status === f.value ? 'bg-brand-600 border-brand-600 shadow-sm' : 'bg-slate-50 border-slate-200'}`}>
              <Text className={`text-xs font-bold tracking-wide ${status === f.value ? 'text-white' : 'text-slate-600'}`}>
                {f.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-4"
          contentContainerStyle={{ paddingBottom: 40 }}
          data={tenants}
          keyExtractor={item => String(item.id)}
          renderItem={({item}) => (
            <TenantRow
              tenant={item}
              onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
            />
          )}
          onRefresh={refetch}
          refreshing={isRefetching}
          ListEmptyComponent={
            <View className="flex-1 items-center justify-center py-20 px-10">
              <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
                <Text className="text-4xl">🔑</Text>
              </View>
              <Text className="text-slate-800 text-xl font-bold mb-2 text-center">{t('tenants.noTenants')}</Text>
              <Text className="text-slate-500 text-center font-medium">Add a tenant or try a different filter.</Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
