import React, {useCallback, useState} from 'react';
import {
  FlatList,
  Pressable,
  RefreshControl,
  Text,
  View,
  SafeAreaView,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useQuery} from '@tanstack/react-query';
import {invoicesApi, InvoiceListItem} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';
import {useTranslation} from '../../i18n';

type Nav = NativeStackNavigationProp<FinanceStackParamList, 'InvoicesList'>;

const STATUS_COLOR: Record<string, string> = {
  paid:           'bg-green-50 border-green-200 text-green-700',
  overdue:        'bg-red-50 border-red-200 text-red-700',
  partially_paid: 'bg-amber-50 border-amber-200 text-amber-700',
  sent:           'bg-blue-50 border-blue-200 text-blue-700',
  draft:          'bg-slate-50 border-slate-200 text-slate-600',
  void:           'bg-slate-100 border-slate-200 text-slate-500',
};

function StatusBadge({status}: {status: string}) {
  const colors = STATUS_COLOR[status] ?? 'bg-slate-50 border-slate-200 text-slate-600';
  const label  = status.replace('_', ' ');
  return (
    <View className={`border rounded-md px-2 py-0.5 ${colors.split(' ')[0]} ${colors.split(' ')[1]}`}>
      <Text className={`text-[10px] font-bold uppercase tracking-wider ${colors.split(' ')[2]}`}>{label}</Text>
    </View>
  );
}

function InvoiceRow({item, onPress}: {item: InvoiceListItem; onPress: () => void}) {
  const {t} = useTranslation();
  return (
    <Pressable
      className="bg-white shadow-sm border border-slate-100 rounded-3xl p-5 mb-4 mx-5 flex-row justify-between items-start"
      onPress={onPress}>
      <View className="flex-1 mr-3">
        <Text className="text-slate-900 font-extrabold text-lg tracking-tight">{item.reference}</Text>
        <Text className="text-slate-500 text-sm font-medium mt-1" numberOfLines={1}>
          {item.property ?? t('finance.unknownProperty')}
        </Text>
        <Text className="text-slate-400 text-xs font-bold mt-2 uppercase tracking-wide">
          {String(item.period_month).padStart(2, '0')}/{item.period_year} · {t('tenants.dueDate')} {new Date(item.due_date).toLocaleDateString()}
        </Text>
      </View>
      <View className="items-end gap-2">
        <Text className="text-slate-900 font-extrabold text-xl">
          R {item.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
        </Text>
        <StatusBadge status={item.status} />
        {item.balance > 0 && item.status !== 'paid' && (
          <Text className="text-red-500 font-bold text-xs mt-1">
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
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">{t('finance.title')}</Text>
          <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
            <Text className="text-brand-600 font-bold text-lg">{invoices.length || 0}</Text>
          </View>
        </View>

        <View className="flex-row gap-2">
          {statusTabs.map(tab => (
            <Pressable
              key={tab.value}
              className={`px-4 py-2 rounded-full border ${activeTab === tab.value ? 'bg-brand-600 border-brand-600 shadow-sm' : 'bg-slate-50 border-slate-200'}`}
              onPress={() => setActiveTab(tab.value)}>
              <Text className={`text-xs font-bold tracking-wide ${activeTab === tab.value ? 'text-white' : 'text-slate-600'}`}>
                {tab.label}
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      <FlatList
        className="flex-1 pt-4"
        data={invoices}
        keyExtractor={item => String(item.id)}
        renderItem={renderItem}
        contentContainerStyle={invoices.length === 0 ? {flex: 1, alignItems: 'center', justifyContent: 'center', padding: 40} : {paddingBottom: 40}}
        refreshControl={<RefreshControl refreshing={isLoading} onRefresh={refetch} tintColor="#10b981" />}
        ListEmptyComponent={
          !isLoading ? (
            <View className="items-center">
              <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
                <Text className="text-4xl">📄</Text>
              </View>
              <Text className="text-slate-800 text-xl font-bold mb-2 text-center">{t('finance.noInvoices')}</Text>
              <Text className="text-slate-500 text-center font-medium">All your generated invoices will be listed here.</Text>
            </View>
          ) : null
        }
      />
    </SafeAreaView>
  );
}
