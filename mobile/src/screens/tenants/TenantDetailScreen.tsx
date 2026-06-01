import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute} from '@react-navigation/native';
import type {RouteProp} from '@react-navigation/native';
import {tenantsApi, leasesApi, PaymentItem} from '../../api/tenants';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {format} from 'date-fns';

type Route = RouteProp<TenantsStackParamList, 'TenantDetail'>;

const STATUS_COLORS: Record<string, string> = {
  paid:    'text-green-400',
  partial: 'text-amber-400',
  overdue: 'text-red-400',
  pending: 'text-slate-400',
  waived:  'text-slate-500',
};

function PaymentRow({payment}: {payment: PaymentItem}) {
  return (
    <View className="flex-row items-center py-2.5 border-b border-slate-800">
      <View className="flex-1">
        <Text className="text-white text-sm font-mono">{payment.reference}</Text>
        <Text className="text-slate-400 text-xs mt-0.5">
          Due {format(new Date(payment.due_date), 'dd MMM yyyy')}
        </Text>
      </View>
      <View className="items-end">
        <Text className="text-white text-sm font-bold">R{payment.amount_due.toLocaleString()}</Text>
        <Text className={`text-xs mt-0.5 capitalize ${STATUS_COLORS[payment.status] ?? 'text-slate-400'}`}>
          {payment.status}
        </Text>
      </View>
    </View>
  );
}

type PaymentMethod = 'eft' | 'cash' | 'card' | 'cheque';

const PAYMENT_METHODS: {label: string; value: PaymentMethod}[] = [
  {label: 'EFT', value: 'eft'},
  {label: 'Cash', value: 'cash'},
  {label: 'Card', value: 'card'},
  {label: 'Cheque', value: 'cheque'},
];

export function TenantDetailScreen() {
  const route = useRoute<Route>();
  const {tenantId} = route.params;
  const queryClient = useQueryClient();

  const [showPayModal, setShowPayModal] = useState(false);
  const [amountPaid, setAmountPaid]     = useState('');
  const [paidDate, setPaidDate]         = useState(format(new Date(), 'yyyy-MM-dd'));
  const [method, setMethod]             = useState<PaymentMethod>('eft');

  const {data, isLoading} = useQuery({
    queryKey: ['tenant', tenantId],
    queryFn:  () => tenantsApi.show(tenantId),
  });

  const tenant = data?.data;

  const payMutation = useMutation({
    mutationFn: () =>
      leasesApi.recordPayment(tenant!.active_lease!.id, {
        amount_paid:    parseFloat(amountPaid),
        paid_date:      paidDate,
        payment_method: method,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tenant', tenantId]});
      setShowPayModal(false);
      setAmountPaid('');
      Alert.alert('Payment Recorded', 'The payment has been recorded and a receipt sent.');
    },
    onError: () => Alert.alert('Error', 'Could not record payment. Please try again.'),
  });

  if (isLoading || !tenant) {
    return (
      <View className="flex-1 bg-surface-base items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  const lease = tenant.active_lease;

  return (
    <View className="flex-1 bg-surface-base">
      <ScrollView>
        {/* Header */}
        <View className="pt-14 pb-5 px-4 bg-surface-card border-b border-slate-800">
          <View className="flex-row items-start justify-between">
            <View className="flex-1">
              <Text className="text-2xl font-bold text-white">{tenant.full_name ?? 'Tenant'}</Text>
              <Text className="text-slate-400 text-sm mt-1" numberOfLines={1}>
                {tenant.property ?? 'No property'}
              </Text>
            </View>
            <View className={`px-3 py-1 rounded-full ${
              tenant.status === 'active' ? 'bg-green-900' : 'bg-slate-700'
            }`}>
              <Text className="text-white text-xs capitalize font-medium">{tenant.status}</Text>
            </View>
          </View>
        </View>

        {/* Contact Info */}
        {tenant.contact && (
          <View className="mx-4 mt-4 p-4 bg-surface-card rounded-2xl">
            <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">Contact</Text>
            <View className="space-y-2">
              {tenant.contact.phone && (
                <Text className="text-white text-sm">📞 {tenant.contact.phone}</Text>
              )}
              {tenant.contact.email && (
                <Text className="text-white text-sm">✉️ {tenant.contact.email}</Text>
              )}
              {tenant.contact.id_number && (
                <Text className="text-white text-sm">🪪 {tenant.contact.id_number}</Text>
              )}
            </View>
          </View>
        )}

        {/* Active Lease */}
        {lease ? (
          <View className="mx-4 mt-4 p-4 bg-surface-card rounded-2xl">
            <View className="flex-row items-center justify-between mb-3">
              <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider">Active Lease</Text>
              <Text className="text-slate-500 font-mono text-xs">{lease.reference}</Text>
            </View>
            <View className="flex-row flex-wrap gap-3">
              <View className="flex-1 min-w-[120px] p-3 bg-slate-800 rounded-xl">
                <Text className="text-slate-400 text-xs mb-1">Monthly Rent</Text>
                <Text className="text-white font-bold text-lg">R{lease.monthly_rent.toLocaleString()}</Text>
              </View>
              <View className="flex-1 min-w-[120px] p-3 bg-slate-800 rounded-xl">
                <Text className="text-slate-400 text-xs mb-1">Expires</Text>
                <Text className={`font-bold text-sm ${lease.days_until_expiry <= 60 ? 'text-amber-400' : 'text-white'}`}>
                  {format(new Date(lease.end_date), 'dd MMM yyyy')}
                </Text>
                <Text className="text-slate-500 text-xs mt-0.5">{lease.days_until_expiry}d left</Text>
              </View>
              <View className="flex-1 min-w-[120px] p-3 bg-slate-800 rounded-xl">
                <Text className="text-slate-400 text-xs mb-1">Outstanding</Text>
                <Text className={`font-bold text-sm ${lease.outstanding_balance > 0 ? 'text-red-400' : 'text-green-400'}`}>
                  R{lease.outstanding_balance.toLocaleString()}
                </Text>
              </View>
            </View>

            <Pressable
              onPress={() => setShowPayModal(true)}
              className="mt-4 py-3 bg-green-700 rounded-xl items-center">
              <Text className="text-white font-semibold text-sm">💳 Record Payment</Text>
            </Pressable>
          </View>
        ) : (
          <View className="mx-4 mt-4 p-4 bg-surface-card rounded-2xl">
            <Text className="text-slate-500 text-sm text-center">No active lease</Text>
          </View>
        )}

        {/* Recent Payments */}
        {tenant.recent_payments.length > 0 && (
          <View className="mx-4 mt-4 p-4 bg-surface-card rounded-2xl">
            <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-3">
              Recent Payments
            </Text>
            {tenant.recent_payments.map(p => (
              <PaymentRow key={p.id} payment={p} />
            ))}
          </View>
        )}

        <View className="h-8" />
      </ScrollView>

      {/* Record Payment Modal */}
      <Modal visible={showPayModal} animationType="slide" presentationStyle="pageSheet">
        <View className="flex-1 bg-surface-base p-5">
          <View className="flex-row items-center justify-between mb-6">
            <Text className="text-xl font-bold text-white">Record Payment</Text>
            <Pressable onPress={() => setShowPayModal(false)}>
              <Text className="text-slate-400 text-lg">✕</Text>
            </Pressable>
          </View>

          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Amount Paid</Text>
          <TextInput
            value={amountPaid}
            onChangeText={setAmountPaid}
            keyboardType="decimal-pad"
            placeholder="0.00"
            placeholderTextColor="#64748b"
            className="bg-slate-800 text-white rounded-xl px-4 py-3 text-lg font-bold mb-4"
          />

          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Date Paid</Text>
          <TextInput
            value={paidDate}
            onChangeText={setPaidDate}
            placeholder="YYYY-MM-DD"
            placeholderTextColor="#64748b"
            className="bg-slate-800 text-white rounded-xl px-4 py-3 text-sm mb-4"
          />

          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wider mb-2">Payment Method</Text>
          <View className="flex-row gap-2 mb-6">
            {PAYMENT_METHODS.map(m => (
              <Pressable
                key={m.value}
                onPress={() => setMethod(m.value)}
                className={`flex-1 py-2.5 rounded-xl items-center ${method === m.value ? 'bg-brand-600' : 'bg-slate-700'}`}>
                <Text className={`text-xs font-medium ${method === m.value ? 'text-white' : 'text-slate-400'}`}>
                  {m.label}
                </Text>
              </Pressable>
            ))}
          </View>

          <Pressable
            onPress={() => payMutation.mutate()}
            disabled={!amountPaid || payMutation.isPending}
            className={`py-4 rounded-xl items-center ${!amountPaid || payMutation.isPending ? 'bg-slate-700' : 'bg-green-700'}`}>
            {payMutation.isPending ? (
              <ActivityIndicator color="#fff" />
            ) : (
              <Text className="text-white font-bold text-base">Confirm Payment</Text>
            )}
          </Pressable>
        </View>
      </Modal>
    </View>
  );
}
