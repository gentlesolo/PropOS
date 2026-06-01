import React, {useCallback, useState} from 'react';
import {
  FlatList,
  RefreshControl,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {useQuery} from '@tanstack/react-query';
import {invoicesApi, InvoiceListItem} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';

type Nav = NativeStackNavigationProp<FinanceStackParamList, 'InvoicesList'>;

const STATUS_TABS = [
  {label: 'All',         value: ''},
  {label: 'Outstanding', value: 'sent'},
  {label: 'Overdue',     value: 'overdue'},
  {label: 'Paid',        value: 'paid'},
];

const STATUS_COLOR: Record<string, string> = {
  paid:          '#22c55e',
  overdue:       '#ef4444',
  partially_paid:'#f59e0b',
  sent:          '#3b82f6',
  draft:         '#94a3b8',
  void:          '#94a3b8',
};

function StatusBadge({status}: {status: string}) {
  const color = STATUS_COLOR[status] ?? '#94a3b8';
  const label = status.replace('_', ' ');
  return (
    <View style={[styles.badge, {borderColor: color}]}>
      <Text style={[styles.badgeText, {color}]}>{label}</Text>
    </View>
  );
}

function InvoiceRow({item, onPress}: {item: InvoiceListItem; onPress: () => void}) {
  return (
    <TouchableOpacity style={styles.row} onPress={onPress} activeOpacity={0.7}>
      <View style={styles.rowLeft}>
        <Text style={styles.reference}>{item.reference}</Text>
        <Text style={styles.property} numberOfLines={1}>{item.property ?? 'Unknown property'}</Text>
        <Text style={styles.period}>{String(item.period_month).padStart(2, '0')}/{item.period_year} · Due {new Date(item.due_date).toLocaleDateString('en-ZA')}</Text>
      </View>
      <View style={styles.rowRight}>
        <Text style={styles.amount}>R {item.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}</Text>
        <StatusBadge status={item.status} />
        {item.balance > 0 && item.status !== 'paid' && (
          <Text style={styles.balance}>Balance: R {item.balance.toLocaleString('en-ZA', {minimumFractionDigits: 2})}</Text>
        )}
      </View>
    </TouchableOpacity>
  );
}

export function InvoicesScreen() {
  const navigation = useNavigation<Nav>();
  const [activeTab, setActiveTab] = useState('');

  const {data, isLoading, refetch} = useQuery({
    queryKey: ['invoices', activeTab],
    queryFn: () => invoicesApi.list({status: activeTab || undefined}),
  });

  const invoices = data?.data.data ?? [];

  const renderItem = useCallback(({item}: {item: InvoiceListItem}) => (
    <InvoiceRow item={item} onPress={() => navigation.navigate('InvoiceDetail', {invoiceId: item.id})} />
  ), [navigation]);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <Text style={styles.title}>Invoices</Text>
      </View>

      <View style={styles.tabs}>
        {STATUS_TABS.map(tab => (
          <TouchableOpacity
            key={tab.value}
            style={[styles.tab, activeTab === tab.value && styles.tabActive]}
            onPress={() => setActiveTab(tab.value)}>
            <Text style={[styles.tabText, activeTab === tab.value && styles.tabTextActive]}>
              {tab.label}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      <FlatList
        data={invoices}
        keyExtractor={item => String(item.id)}
        renderItem={renderItem}
        refreshControl={<RefreshControl refreshing={isLoading} onRefresh={refetch} tintColor="#3b82f6" />}
        contentContainerStyle={invoices.length === 0 ? styles.emptyContainer : styles.list}
        ListEmptyComponent={
          !isLoading ? (
            <Text style={styles.emptyText}>No invoices found.</Text>
          ) : null
        }
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container:      {flex: 1, backgroundColor: '#0f172a'},
  header:         {paddingHorizontal: 16, paddingTop: 56, paddingBottom: 12},
  title:          {fontSize: 24, fontWeight: '700', color: '#f1f5f9'},
  tabs:           {flexDirection: 'row', paddingHorizontal: 16, gap: 8, marginBottom: 12},
  tab:            {paddingHorizontal: 12, paddingVertical: 6, borderRadius: 20, backgroundColor: '#1e293b'},
  tabActive:      {backgroundColor: '#3b82f6'},
  tabText:        {fontSize: 12, fontWeight: '600', color: '#64748b'},
  tabTextActive:  {color: '#fff'},
  list:           {paddingHorizontal: 16, paddingBottom: 24},
  emptyContainer: {flex: 1, alignItems: 'center', justifyContent: 'center', padding: 40},
  emptyText:      {color: '#64748b', fontSize: 14},
  row:            {backgroundColor: '#1e293b', borderRadius: 12, padding: 14, marginBottom: 10, flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'},
  rowLeft:        {flex: 1, marginRight: 12},
  reference:      {fontSize: 13, fontWeight: '700', color: '#f1f5f9', fontFamily: 'monospace'},
  property:       {fontSize: 12, color: '#94a3b8', marginTop: 2},
  period:         {fontSize: 11, color: '#64748b', marginTop: 2},
  rowRight:       {alignItems: 'flex-end', gap: 4},
  amount:         {fontSize: 15, fontWeight: '700', color: '#f1f5f9'},
  balance:        {fontSize: 10, color: '#f59e0b'},
  badge:          {borderWidth: 1, borderRadius: 10, paddingHorizontal: 7, paddingVertical: 2},
  badgeText:      {fontSize: 10, fontWeight: '600', textTransform: 'capitalize'},
});
