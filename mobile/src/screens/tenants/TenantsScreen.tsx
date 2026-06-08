import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {tenantsApi, TenantListItem} from '../../api/tenants';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTranslation} from '../../i18n';

type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

const STATUS_COLORS: Record<string, string> = {
  active:      'bg-green-600',
  prospect:    'bg-blue-600',
  vacating:    'bg-amber-600',
  vacated:     'bg-slate-600',
  blacklisted: 'bg-red-700',
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
      className="flex-row items-center px-4 py-3 border-b border-slate-800"
      onPress={onPress}>
      <View className="w-11 h-11 rounded-full bg-brand-700 items-center justify-center mr-3">
        <Text className="text-white font-semibold text-sm">{initials}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-white font-medium">{tenant.full_name ?? t('tenants.noTenant')}</Text>
        <Text className="text-slate-400 text-xs mt-0.5" numberOfLines={1}>
          {tenant.property ?? t('tenants.noProperty')}
        </Text>
        {tenant.monthly_rent != null && (
          <Text className="text-slate-500 text-xs mt-0.5">
            R{tenant.monthly_rent.toLocaleString()}/mo
          </Text>
        )}
      </View>
      <View className="items-end gap-1">
        <View className={`px-2 py-0.5 rounded-full ${STATUS_COLORS[tenant.status] ?? 'bg-slate-600'}`}>
          <Text className="text-white text-xs capitalize">{tenant.status}</Text>
        </View>
        {tenant.fica_count === 0 && (
          <Text className="text-amber-500 text-xs">{t('tenants.ficaPending')}</Text>
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
    <View className="flex-1 bg-surface-base">
      {/* Header */}
      <View className="pt-14 pb-4 px-4 bg-surface-card border-b border-slate-800">
        <Text className="text-2xl font-bold text-white mb-3">{t('tenants.title')}</Text>
        <TextInput
          value={search}
          onChangeText={setSearch}
          placeholder={t('tenants.searchPlaceholder')}
          placeholderTextColor="#64748b"
          className="bg-slate-800 text-white rounded-xl px-4 py-2.5 text-sm"
        />
        {/* Filter pills */}
        <View className="flex-row gap-2 mt-3">
          {filters.map(f => (
            <Pressable
              key={f.value}
              onPress={() => setStatus(f.value)}
              className={`px-3 py-1.5 rounded-full ${status === f.value ? 'bg-brand-600' : 'bg-slate-700'}`}>
              <Text className={`text-xs font-medium ${status === f.value ? 'text-white' : 'text-slate-400'}`}>
                {f.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <FlatList
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
            <View className="flex-1 items-center justify-center py-16">
              <Text className="text-slate-500 text-sm">{t('tenants.noTenants')}</Text>
            </View>
          }
        />
      )}
    </View>
  );
}
