import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  Pressable,
  ScrollView,
  Text,
  View,
} from 'react-native';
import {RouteProp, useRoute} from '@react-navigation/native';
import {useQuery, useMutation} from '@tanstack/react-query';
import {invoicesApi} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';
import {useTranslation} from '../../i18n';

type RouteProps = RouteProp<FinanceStackParamList, 'InvoiceDetail'>;

const STATUS_BORDER: Record<string, string> = {
  paid:           'border-green-500 text-green-400',
  overdue:        'border-red-500 text-red-400',
  partially_paid: 'border-amber-500 text-amber-400',
  sent:           'border-blue-500 text-blue-400',
  draft:          'border-slate-500 text-slate-400',
  void:           'border-slate-500 text-slate-400',
};

function Section({title, children}: {title: string; children: React.ReactNode}) {
  return (
    <View className="bg-surface-card rounded-xl p-3.5 mb-3">
      <Text className="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2.5">{title}</Text>
      {children}
    </View>
  );
}

function Row({label, value, valueColor}: {label: string; value: string; valueColor?: string}) {
  return (
    <View className="flex-row justify-between py-1.5 border-b border-slate-700">
      <Text className="text-slate-400 text-sm">{label}</Text>
      <Text className={`text-sm font-semibold text-right max-w-[60%] ${valueColor ?? 'text-white'}`}>{value}</Text>
    </View>
  );
}

export function InvoiceDetailScreen() {
  const {t} = useTranslation();
  const route = useRoute<RouteProps>();
  const {invoiceId} = route.params;

  const {data, isLoading} = useQuery({
    queryKey: ['invoice', invoiceId],
    queryFn: () => invoicesApi.show(invoiceId),
  });

  const payNowMutation = useMutation({
    mutationFn: () => invoicesApi.payNow(invoiceId),
    onSuccess: result => {
      const url = result.data.url;
      Alert.alert(
        t('finance.paymentGateway'),
        t('finance.redirectMessage'),
        [
          {text: t('common.cancel'), style: 'cancel'},
          {text: t('finance.payNow'), onPress: () => Linking.openURL(url)},
        ],
      );
    },
    onError: () => Alert.alert(t('common.error'), t('finance.paymentError')),
  });

  if (isLoading) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" size="large" />
      </View>
    );
  }

  const invoice = data?.data.data;
  if (!invoice) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <Text className="text-slate-400">{t('finance.notFound')}</Text>
      </View>
    );
  }

  const statusColors = STATUS_BORDER[invoice.status] ?? 'border-slate-500 text-slate-400';
  const canPay = !['paid', 'void'].includes(invoice.status);

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="p-4 pb-10">
      {/* Header */}
      <View className="bg-surface-card rounded-2xl p-5 items-center mb-4">
        <Text className="text-slate-400 font-bold font-mono text-sm mb-2">{invoice.reference}</Text>
        <View className={`border rounded-xl px-2.5 py-0.5 mb-3 ${statusColors.split(' ')[0]}`}>
          <Text className={`text-xs font-bold capitalize ${statusColors.split(' ')[1]}`}>
            {invoice.status.replace('_', ' ')}
          </Text>
        </View>
        <Text className="text-white text-4xl font-extrabold">
          R {invoice.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
        </Text>
        <Text className="text-slate-500 text-sm mt-1">
          {t('tenants.dueDate')} {new Date(invoice.due_date).toLocaleDateString(undefined, {day: 'numeric', month: 'long', year: 'numeric'})}
        </Text>
      </View>

      {/* Details */}
      <Section title={t('finance.details')}>
        <Row label={t('finance.property')} value={invoice.property ?? '—'} />
        <Row label={t('finance.period')}   value={`${String(invoice.period_month).padStart(2,'0')}/${invoice.period_year}`} />
        <Row label={t('finance.type')}     value={invoice.type} />
        {invoice.tenant && (
          <Row label={t('finance.tenant')} value={`${invoice.tenant.first_name} ${invoice.tenant.last_name}`} />
        )}
      </Section>

      {/* Line Items */}
      <Section title={t('finance.lineItems')}>
        {invoice.line_items.map((item, idx) => (
          <View key={idx} className="flex-row justify-between items-center py-2 border-b border-slate-700">
            <View className="flex-1 mr-2">
              <Text className="text-white text-sm">{item.description}</Text>
              <Text className="text-slate-500 text-xs capitalize mt-0.5">{item.category}</Text>
            </View>
            <Text className="text-white font-bold text-sm">
              R {item.amount.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
            </Text>
          </View>
        ))}
      </Section>

      {/* Totals */}
      <Section title={t('finance.summary')}>
        <Row label={t('finance.subtotal')} value={`R ${invoice.subtotal.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        {invoice.tax_amount > 0 && (
          <Row label={t('finance.tax')} value={`R ${invoice.tax_amount.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        )}
        <Row label={t('finance.total')}     value={`R ${invoice.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        <Row label={t('finance.amountPaid')} value={`R ${invoice.amount_paid.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} valueColor="text-green-400" />
        {invoice.balance > 0 && (
          <Row label={t('finance.balance')} value={`R ${invoice.balance.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} valueColor="text-red-400" />
        )}
      </Section>

      {/* Pay Now Button */}
      {canPay && (
        <Pressable
          className={`rounded-xl py-4 items-center mt-2 ${payNowMutation.isPending ? 'bg-brand-800 opacity-60' : 'bg-brand-600'}`}
          onPress={() => payNowMutation.mutate()}
          disabled={payNowMutation.isPending}>
          {payNowMutation.isPending
            ? <ActivityIndicator color="#fff" />
            : <Text className="text-white font-bold text-base">{t('finance.payNow')}</Text>
          }
        </Pressable>
      )}
    </ScrollView>
  );
}
