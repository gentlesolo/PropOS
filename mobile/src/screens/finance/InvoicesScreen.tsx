import React, {useCallback, useState} from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  Text,
  View,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useQuery} from '@tanstack/react-query';
import {invoicesApi, InvoiceListItem} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';
import {useTranslation} from '../../i18n';

type Nav = NativeStackNavigationProp<FinanceStackParamList, 'InvoicesList'>;

const STATUS_COLOR: Record<string, string> = {
  paid:           'border-green-500 text-green-400',
  overdue:        'border-red-500 text-red-400',
  partially_paid: 'border-amber-500 text-amber-400',
  sent:           'border-blue-500 text-blue-400',
  draft:          'border-slate-500 text-slate-400',
  void:           'border-slate-500 text-slate-400',
};

function StatusBadge({status}: {status: string}) {
  const colors = STATUS_COLOR[status] ?? 'border-slate-500 text-slate-400';
  const label  = status.replace('_', ' ');
  return (
    <View className={`border rounded-full px-2 py-0.5 ${colors.split(' ')[0]}`}>
      <Text className={`text-xs font-semibold capitalize ${colors.split(' ')[1]}`}>{label}</Text>
    </View>
  );
}

function InvoiceRow({item, onPress}: {item: InvoiceListItem; onPress: () => void}) {
  const {t} = useTranslation();
  return (
    <Pressable
      className="bg-surface-card rounded-xl p-3.5 mb-2.5 flex-row justify-between items-start"
      onPress={onPress}>
      <View className="flex-1 mr-3">
        <Text className="text-white font-bold text-sm font-mono">{item.reference}</Text>
        <Text className="text-slate-400 text-xs mt-0.5" numberOfLines={1}>
          {item.property ?? t('finance.unknownProperty')}
        </Text>
        <Text className="text-slate-500 text-[11px] mt-0.5">
          {String(item.period_month).padStart(2, '0')}/{item.period_year} · {t('tenants.dueDate')} {new Date(item.due_date).toLocaleDateString()}
        </Text>
      </View>
      <View className="items-end gap-1">
        <Text className="text-white font-bold text-base">
          R {item.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
        </Text>
        <StatusBadge status={item.status} />
        {item.balance > 0 && item.status !== 'paid' && (
          <Text className="text-amber-400 text-[10px]">
            {t('finance.balance')}: R {item.balance.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
          </Text>
        )}
      </View>
    </Pressable>
  );
}

export function InvoicesScreen() {
  const {t} = useTranslation();
  const navigation = useNavigation<Nav>();
  const [activeTab, setActiveTab] = useState('');

  const statusTabs = [
    {label: t('finance.filterAll'),         value: ''},
    {label: t('finance.filterOutstanding'), value: 'sent'},
    {label: t('finance.filterOverdue'),     value: 'overdue'},
    {label: t('finance.filterPaid'),        value: 'paid'},
  ];

  const {data, isLoading, refetch} = useQuery({
    queryKey: ['invoices', activeTab],
    queryFn: () => invoicesApi.list({status: activeTab || undefined}),
  });

  const invoices = data?.data.data ?? [];

  const renderItem = useCallback(({item}: {item: InvoiceListItem}) => (
    <InvoiceRow item={item} onPress={() => navigation.navigate('InvoiceDetail', {invoiceId: item.id})} />
  ), [navigation]);

  return (
    <View className="flex-1 bg-surface">
      <View className="px-4 pt-14 pb-3">
        <Text className="text-2xl font-bold text-white">{t('finance.title')}</Text>
      </View>

      <View className="flex-row px-4 gap-2 mb-3">
        {statusTabs.map(tab => (
          <Pressable
            key={tab.value}
            className={`px-3 py-1.5 rounded-full ${activeTab === tab.value ? 'bg-brand-600' : 'bg-surface-card'}`}
            onPress={() => setActiveTab(tab.value)}>
            <Text className={`text-xs font-semibold ${activeTab === tab.value ? 'text-white' : 'text-slate-400'}`}>
              {tab.label}
            </Text>
          </Pressable>
        ))}
      </View>

      <FlatList
        data={invoices}
        keyExtractor={item => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={invoices.length === 0 ? {flex: 1, alignItems: 'center', justifyContent: 'center', padding: 40} : {paddingHorizontal: 16, paddingBottom: 24}}
        refreshControl={<RefreshControl refreshing={isLoading} onRefresh={refetch} tintColor="#3b82f6" />}
        ListEmptyComponent={
          !isLoading ? (
            <Text className="text-slate-500 text-sm">{t('finance.noInvoices')}</Text>
          ) : null
        }
      />
    </View>
  );
}
