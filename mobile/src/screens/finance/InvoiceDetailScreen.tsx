import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Linking,
  ScrollView,
  StyleSheet,
  Text,
  TouchableOpacity,
  View,
} from 'react-native';
import {RouteProp, useRoute} from '@react-navigation/native';
import {useQuery, useMutation} from '@tanstack/react-query';
import {invoicesApi} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';

type RouteProps = RouteProp<FinanceStackParamList, 'InvoiceDetail'>;

const STATUS_COLOR: Record<string, string> = {
  paid:          '#22c55e',
  overdue:       '#ef4444',
  partially_paid:'#f59e0b',
  sent:          '#3b82f6',
  draft:         '#94a3b8',
  void:          '#94a3b8',
};

function Section({title, children}: {title: string; children: React.ReactNode}) {
  return (
    <View style={styles.section}>
      <Text style={styles.sectionTitle}>{title}</Text>
      {children}
    </View>
  );
}

function Row({label, value, valueColor}: {label: string; value: string; valueColor?: string}) {
  return (
    <View style={styles.detailRow}>
      <Text style={styles.label}>{label}</Text>
      <Text style={[styles.value, valueColor ? {color: valueColor} : undefined]}>{value}</Text>
    </View>
  );
}

export function InvoiceDetailScreen() {
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
        'Payment Gateway',
        'You will be redirected to the secure payment page.',
        [
          {text: 'Cancel', style: 'cancel'},
          {text: 'Pay Now', onPress: () => Linking.openURL(url)},
        ],
      );
    },
    onError: () => Alert.alert('Error', 'Could not generate payment link. Please try again.'),
  });

  if (isLoading) {
    return (
      <View style={styles.loading}>
        <ActivityIndicator color="#3b82f6" size="large" />
      </View>
    );
  }

  const invoice = data?.data.data;
  if (!invoice) {
    return (
      <View style={styles.loading}>
        <Text style={styles.errorText}>Invoice not found.</Text>
      </View>
    );
  }

  const statusColor = STATUS_COLOR[invoice.status] ?? '#94a3b8';
  const canPay = !['paid', 'void'].includes(invoice.status);

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      {/* Header */}
      <View style={styles.headerCard}>
        <Text style={styles.reference}>{invoice.reference}</Text>
        <View style={[styles.statusBadge, {borderColor: statusColor}]}>
          <Text style={[styles.statusText, {color: statusColor}]}>
            {invoice.status.replace('_', ' ')}
          </Text>
        </View>
        <Text style={styles.totalAmount}>
          R {invoice.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
        </Text>
        <Text style={styles.dueDate}>
          Due {new Date(invoice.due_date).toLocaleDateString('en-ZA', {day: 'numeric', month: 'long', year: 'numeric'})}
        </Text>
      </View>

      {/* Property & Period */}
      <Section title="Details">
        <Row label="Property"  value={invoice.property ?? '—'} />
        <Row label="Period"    value={`${String(invoice.period_month).padStart(2,'0')}/${invoice.period_year}`} />
        <Row label="Type"      value={invoice.type} />
        {invoice.tenant && (
          <Row label="Tenant" value={`${invoice.tenant.first_name} ${invoice.tenant.last_name}`} />
        )}
      </Section>

      {/* Line Items */}
      <Section title="Line Items">
        {invoice.line_items.map((item, idx) => (
          <View key={idx} style={styles.lineItem}>
            <View style={styles.lineItemLeft}>
              <Text style={styles.lineItemDesc}>{item.description}</Text>
              <Text style={styles.lineItemCat}>{item.category}</Text>
            </View>
            <Text style={styles.lineItemAmount}>
              R {item.amount.toLocaleString('en-ZA', {minimumFractionDigits: 2})}
            </Text>
          </View>
        ))}
      </Section>

      {/* Totals */}
      <Section title="Summary">
        <Row label="Subtotal"   value={`R ${invoice.subtotal.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        {invoice.tax_amount > 0 && (
          <Row label="Tax"      value={`R ${invoice.tax_amount.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        )}
        <Row label="Total"      value={`R ${invoice.total.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} />
        <Row label="Paid"       value={`R ${invoice.amount_paid.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} valueColor="#22c55e" />
        {invoice.balance > 0 && (
          <Row label="Balance"  value={`R ${invoice.balance.toLocaleString('en-ZA', {minimumFractionDigits: 2})}`} valueColor="#ef4444" />
        )}
      </Section>

      {/* Pay Now Button */}
      {canPay && (
        <TouchableOpacity
          style={[styles.payButton, payNowMutation.isPending && styles.payButtonDisabled]}
          onPress={() => payNowMutation.mutate()}
          disabled={payNowMutation.isPending}
          activeOpacity={0.8}>
          {payNowMutation.isPending
            ? <ActivityIndicator color="#fff" />
            : <Text style={styles.payButtonText}>Pay Now</Text>
          }
        </TouchableOpacity>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container:        {flex: 1, backgroundColor: '#0f172a'},
  content:          {padding: 16, paddingBottom: 40},
  loading:          {flex: 1, backgroundColor: '#0f172a', alignItems: 'center', justifyContent: 'center'},
  errorText:        {color: '#94a3b8'},
  headerCard:       {backgroundColor: '#1e293b', borderRadius: 16, padding: 20, alignItems: 'center', marginBottom: 16},
  reference:        {fontSize: 14, fontWeight: '700', color: '#94a3b8', fontFamily: 'monospace', marginBottom: 8},
  statusBadge:      {borderWidth: 1, borderRadius: 12, paddingHorizontal: 10, paddingVertical: 3, marginBottom: 12},
  statusText:       {fontSize: 12, fontWeight: '700', textTransform: 'capitalize'},
  totalAmount:      {fontSize: 32, fontWeight: '800', color: '#f1f5f9'},
  dueDate:          {fontSize: 13, color: '#64748b', marginTop: 4},
  section:          {backgroundColor: '#1e293b', borderRadius: 12, padding: 14, marginBottom: 12},
  sectionTitle:     {fontSize: 12, fontWeight: '700', color: '#64748b', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 10},
  detailRow:        {flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6, borderBottomWidth: 1, borderBottomColor: '#334155'},
  label:            {fontSize: 13, color: '#94a3b8'},
  value:            {fontSize: 13, fontWeight: '600', color: '#f1f5f9', maxWidth: '60%', textAlign: 'right'},
  lineItem:         {flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingVertical: 8, borderBottomWidth: 1, borderBottomColor: '#334155'},
  lineItemLeft:     {flex: 1, marginRight: 8},
  lineItemDesc:     {fontSize: 13, color: '#f1f5f9'},
  lineItemCat:      {fontSize: 11, color: '#64748b', textTransform: 'capitalize', marginTop: 2},
  lineItemAmount:   {fontSize: 13, fontWeight: '700', color: '#f1f5f9'},
  payButton:        {backgroundColor: '#3b82f6', borderRadius: 14, paddingVertical: 16, alignItems: 'center', marginTop: 8},
  payButtonDisabled:{opacity: 0.6},
  payButtonText:    {fontSize: 16, fontWeight: '700', color: '#fff'},
});
