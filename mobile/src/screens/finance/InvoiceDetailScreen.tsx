import React, {useState, useMemo, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Linking,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  Share,
  StyleSheet,
  Text,
  TextInput,
  View,
  Vibration,
} from 'react-native';
import {RouteProp, useRoute, useNavigation} from '@react-navigation/native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {SafeAreaView, useSafeAreaInsets} from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/Feather';
import {invoicesApi, InvoiceDetail, InvoiceListItem} from '../../api/invoices';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';
import {useTranslation} from '../../i18n';
import {useTheme} from '../../theme/ThemeProvider';
import {createMMKV} from 'react-native-mmkv';
import {RecordPaymentModal} from '../../components/RecordPaymentModal';

type RouteProps = RouteProp<FinanceStackParamList, 'InvoiceDetail'>;

// Instantiate local storage safely
let localStore: any;
try {
  localStore = createMMKV({id: 'invoices-local-store-v1'});
} catch (e) {
  const store: Record<string, string> = {};
  localStore = {
    getString: (key: string) => store[key] || null,
    set: (key: string, val: string) => { store[key] = val; },
    delete: (key: string) => { delete store[key]; },
  };
}

// Helper to format currency
const formatCurrency = (amount: number) => {
  const currencySymbol = localStore.getString('currency_symbol') || '₦';
  return `${currencySymbol}${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
};

// Pulsing dot for overdue items (1.2s loop, opacity 1.0 -> 0.4)
function PulsingDot() {
  const opacity = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {toValue: 0.4, duration: 600, useNativeDriver: true}),
        Animated.timing(opacity, {toValue: 1, duration: 600, useNativeDriver: true}),
      ])
    ).start();
  }, []);

  return (
    <Animated.View
      style={{
        width: 8,
        height: 8,
        borderRadius: 4,
        backgroundColor: '#EF4444',
        opacity,
        marginRight: 6,
      }}
    />
  );
}

// Status badge component
function StatusBadge({status, tokens}: {status: string; tokens: any}) {
  let bgColor = tokens.surfaceSunken;
  let textColor = tokens.textSecondary;
  let borderColor = tokens.borderDefault;

  if (status === 'paid') {
    bgColor = tokens.successBg;
    textColor = tokens.successText;
    borderColor = tokens.successBorder;
  } else if (status === 'overdue') {
    bgColor = tokens.dangerBg;
    textColor = tokens.dangerText;
    borderColor = tokens.dangerBorder;
  } else if (status === 'partially_paid') {
    bgColor = tokens.warningBg;
    textColor = tokens.warningText;
    borderColor = tokens.warningBorder;
  } else if (status === 'sent') {
    bgColor = tokens.infoBg;
    textColor = tokens.infoText;
    borderColor = tokens.infoBorder;
  } else if (status === 'draft') {
    bgColor = tokens.surfaceSunken;
    textColor = tokens.textTertiary;
    borderColor = tokens.borderDefault;
  }

  return (
    <View
      style={{
        paddingHorizontal: 10,
        paddingVertical: 4,
        borderRadius: 6,
        backgroundColor: bgColor,
        borderWidth: 1,
        borderColor: borderColor,
        flexDirection: 'row',
        alignItems: 'center',
      }}
    >
      <Text
        style={{
          fontSize: 10,
          fontWeight: '900',
          textTransform: 'uppercase',
          color: textColor,
          letterSpacing: 0.6,
        }}
      >
        {status.replace('_', ' ')}
      </Text>
    </View>
  );
}

export function InvoiceDetailScreen() {
  const {t} = useTranslation();
  const route = useRoute<RouteProps>();
  const navigation = useNavigation();
  const {invoiceId} = route.params;
  const {tokens} = useTheme();
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  // Local state for modals & forms
  const [showPayModal, setShowPayModal] = useState(false);
  const [showEditItemModal, setShowEditItemModal] = useState(false);

  const [isRecordingPayment, setIsRecordingPayment] = useState(false);

  // Line item editing states (for Draft mode)
  const [editingItemIndex, setEditingItemIndex] = useState<number | null>(null);
  const [editItemDesc, setEditItemDesc] = useState('');
  const [editItemAmount, setEditItemAmount] = useState('');

  // Simulated PDF download progress
  const [isDownloading, setIsDownloading] = useState(false);

  // Load local persisted invoices
  const [localInvoices, setLocalInvoices] = useState<InvoiceListItem[]>([]);
  useEffect(() => {
    try {
      const stored = localStore.getString('invoices');
      if (stored) {
        setLocalInvoices(JSON.parse(stored));
      }
    } catch (e) {
      console.error(e);
    }
  }, []);

  // Check if we have this invoice stored in local cache overrides
  const localInvoice = useMemo(() => {
    return localInvoices.find((item) => item.id === invoiceId);
  }, [localInvoices, invoiceId]);

  // Fetch Remote Query if not in local cache
  const {data: apiData, isLoading: isQueryLoading} = useQuery({
    queryKey: ['invoice', invoiceId],
    queryFn: () => invoicesApi.show(invoiceId).then((r) => r.data),
    enabled: !localInvoice,
  });

  // Master invoice structure (merging local & remote fields)
  const invoice = useMemo(() => {
    if (localInvoice) {
      // Map local InvoiceListItem to full InvoiceDetail type
      return {
        ...localInvoice,
        tenant: (localInvoice as any).tenant || ((localInvoice as any).recipient_name ? {
          id: 0,
          first_name: (localInvoice as any).recipient_name.split(' ')[0] || '',
          last_name: (localInvoice as any).recipient_name.split(' ').slice(1).join(' ') || '',
          phone: null,
          email: null,
        } : null),
        property_detail: (localInvoice as any).property_detail || (localInvoice.property ? {
          id: 0,
          address_line_1: localInvoice.property,
          city: '',
        } : null),
        line_items: (localInvoice as any).line_items || [
          { description: 'Invoice Balance', category: 'general', quantity: 1, unit_price: localInvoice.total, amount: localInvoice.total }
        ],
      } as InvoiceDetail;
    }
    return apiData?.data;
  }, [localInvoice, apiData]);

  // Helper to extract recipient name
  const getRecipientName = (inv: any) => {
    if (inv?.recipient_name) return inv.recipient_name;
    if (inv?.tenant?.first_name || inv?.tenant?.last_name) {
      return `${inv.tenant.first_name ?? ''} ${inv.tenant.last_name ?? ''}`.trim();
    }
    if (inv?.lease?.tenant?.contact) {
      const contact = inv.lease.tenant.contact;
      return `${contact.first_name ?? ''} ${contact.last_name ?? ''}`.trim();
    }
    return 'Unknown Recipient';
  };

  const now = new Date();
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());

  // Overdue check
  const isOverdue = useMemo(() => {
    if (!invoice) return false;
    if (invoice.status === 'paid' || invoice.status === 'void' || invoice.status === 'draft') return false;
    if (invoice.status === 'overdue') return true;
    return new Date(invoice.due_date) < startOfToday;
  }, [invoice, startOfToday]);

  // Overdue days calc
  const overdueDays = useMemo(() => {
    if (!invoice || !isOverdue) return 0;
    const dueDate = new Date(invoice.due_date);
    const diffTime = startOfToday.getTime() - dueDate.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays > 0 ? diffDays : 0;
  }, [invoice, isOverdue, startOfToday]);

  // Context line: Adaeze Obi · Flat 3B, Marina Heights · Rent — June 2026
  const contextLine = useMemo(() => {
    if (!invoice) return '';
    const recipient = getRecipientName(invoice);
    const property = invoice.property || 'No Property Linked';
    const invoiceType = invoice.type.charAt(0).toUpperCase() + invoice.type.slice(1);
    const period = `${String(invoice.period_month).padStart(2, '0')}/${invoice.period_year}`;
    return `${recipient} · ${property} · ${invoiceType} — ${period}`;
  }, [invoice]);

  // AI Insight Card Content
  const aiInsight = useMemo(() => {
    if (!invoice) return '';
    const recipient = getRecipientName(invoice);
    const firstName = recipient.split(' ')[0] || 'Tenant';
    if (isOverdue) {
      return `This is ${firstName}'s first late payment in 12 months. A reminder was sent ${overdueDays > 1 ? '2 days ago' : 'yesterday'} via WhatsApp — no response yet.`;
    }
    if (invoice.status === 'paid') {
      return `${firstName} has a perfect payment record (12/12 periods settled on time). Automated receipts and ledger updates have been successfully sent.`;
    }
    if (invoice.status === 'draft') {
      return `This draft invoice for ${firstName} can be reviewed and sent. Sending will activate payment tracking and automated billing reminder channels.`;
    }
    return `${firstName}'s invoice is outstanding but not yet overdue. Payment links are active.`;
  }, [invoice, isOverdue, overdueDays]);

  // Save changes to local storage
  const saveInvoiceToStore = (updatedInvoice: any) => {
    const updatedList = [
      updatedInvoice,
      ...localInvoices.filter((item) => item.id !== invoiceId),
    ];
    setLocalInvoices(updatedList);
    try {
      localStore.set('invoices', JSON.stringify(updatedList));
      // Force trigger parent queries refresh
      queryClient.invalidateQueries({queryKey: ['invoices']});
    } catch (e) {
      console.error(e);
    }
  };

  // Pay Now Payment Gateway simulation
  const payNowMutation = useMutation({
    mutationFn: () => invoicesApi.payNow(invoiceId),
    onSuccess: (result) => {
      const url = result.data.url;
      Alert.alert(
        t('finance.paymentGateway'),
        t('finance.redirectMessage'),
        [
          {text: t('common.cancel'), style: 'cancel'},
          {text: t('finance.payNow'), onPress: () => Linking.openURL(url)},
        ]
      );
    },
    onError: () => Alert.alert(t('common.error'), t('finance.paymentError')),
  });

  // Record Manual Payment (Bottom Sheet)
  const openRecordPayment = () => {
    setShowPayModal(true);
  };

  const handleRecordPaymentSubmit = (amt: number, date: string, method: 'Bank Transfer' | 'Cash' | 'Card') => {
    if (!invoice) return;

    setIsRecordingPayment(true);

    setTimeout(() => {
      const newPaid = invoice.amount_paid + amt;
      const newBalance = Math.max(0, invoice.total - newPaid);
      const newStatus = newBalance === 0 ? 'paid' : 'partially_paid';

      const updated: any = {
        ...invoice,
        amount_paid: newPaid,
        balance: newBalance,
        status: newStatus,
        paid_at: newStatus === 'paid' ? date + ' 00:00:00' : invoice.paid_at,
        // Save manual payment transaction info inside property detail or meta
        recipient_name: getRecipientName(invoice),
      };

      saveInvoiceToStore(updated);
      setIsRecordingPayment(false);
      setShowPayModal(false);
      Alert.alert('Payment Recorded', `Recorded payment of ${formatCurrency(amt)} via ${method}.`);
    }, 1000);
  };

  // Send Invoice (Transition Draft to Sent)
  const handleSendInvoice = () => {
    if (!invoice) return;
    Vibration.vibrate(15);
    const updated: any = {
      ...invoice,
      status: 'sent',
      issued_at: new Date().toISOString(),
      recipient_name: getRecipientName(invoice),
    };
    saveInvoiceToStore(updated);
    Alert.alert('Invoice Sent', `Invoice ${invoice.reference} has been sent. Status updated to Pending.`);
  };

  // Download PDF simulation
  const handleDownloadPDF = () => {
    if (!invoice) return;
    Vibration.vibrate(10);
    setIsDownloading(true);
    setTimeout(() => {
      setIsDownloading(false);
      Alert.alert('Download Complete', `PDF of invoice ${invoice.reference} saved to device.`, [
        {text: 'Open PDF', onPress: () => Alert.alert('PDF Viewer', `Opening invoice_${invoice.reference}.pdf`)},
        {text: 'Close', style: 'cancel'},
      ]);
    }, 1500);
  };

  // Native Share Sheet
  const handleShare = async () => {
    if (!invoice) return;
    Vibration.vibrate(5);
    try {
      const recipient = getRecipientName(invoice);
      await Share.share({
        message: `VillaCRM Invoice ${invoice.reference} for ${recipient}:\nTotal Amount: ${formatCurrency(invoice.total)}\nDue Date: ${invoice.due_date}\nSecure PDF: https://villacrm.com/invoices/inv-${invoice.id}.pdf`,
      });
    } catch (e) {
      console.error(e);
    }
  };

  // Line item editing (Draft mode)
  const openEditLineItem = (index: number) => {
    if (invoice?.status !== 'draft') return;
    const item = invoice.line_items[index];
    if (!item) return;

    Vibration.vibrate(5);
    setEditingItemIndex(index);
    setEditItemDesc(item.description);
    setEditItemAmount(String(item.amount));
    setShowEditItemModal(true);
  };

  const openAddLineItem = () => {
    if (invoice?.status !== 'draft') return;

    Vibration.vibrate(5);
    setEditingItemIndex(null);
    setEditItemDesc('');
    setEditItemAmount('');
    setShowEditItemModal(true);
  };

  const handleSaveLineItem = () => {
    if (!invoice) return;
    const desc = editItemDesc.trim();
    const amt = parseFloat(editItemAmount);

    if (!desc) {
      Alert.alert('Validation Error', 'Item description is required.');
      return;
    }
    if (isNaN(amt) || amt <= 0) {
      Alert.alert('Validation Error', 'Item amount must be a positive number.');
      return;
    }

    let updatedLines = [...invoice.line_items];
    const newItem = {
      description: desc,
      category: 'general',
      quantity: 1,
      unit_price: amt,
      amount: amt,
    };

    if (editingItemIndex !== null) {
      updatedLines[editingItemIndex] = newItem;
    } else {
      updatedLines.push(newItem);
    }

    const newTotal = updatedLines.reduce((sum, item) => sum + item.amount, 0);

    const updated: any = {
      ...invoice,
      line_items: updatedLines,
      subtotal: newTotal,
      total: newTotal,
      balance: newTotal - invoice.amount_paid,
      recipient_name: getRecipientName(invoice),
    };

    saveInvoiceToStore(updated);
    setShowEditItemModal(false);
  };

  const handleDeleteLineItem = () => {
    if (!invoice || editingItemIndex === null) return;

    let updatedLines = invoice.line_items.filter((_, idx) => idx !== editingItemIndex);
    const newTotal = updatedLines.reduce((sum, item) => sum + item.amount, 0);

    const updated: any = {
      ...invoice,
      line_items: updatedLines,
      subtotal: newTotal,
      total: newTotal,
      balance: newTotal - invoice.amount_paid,
      recipient_name: getRecipientName(invoice),
    };

    saveInvoiceToStore(updated);
    setShowEditItemModal(false);
  };

  // Loading indicator
  if (isQueryLoading && !localInvoice) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} size="large" />
      </View>
    );
  }

  // Not Found
  if (!invoice) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <Icon name="file-text" size={48} color={tokens.textTertiary} style={{marginBottom: 16}} />
        <Text style={{color: tokens.textSecondary, fontSize: 16, fontWeight: '700'}}>{t('finance.notFound')}</Text>
      </View>
    );
  }

  const isDraft = invoice.status === 'draft';
  const isPaid = invoice.status === 'paid';
  const remainingBalance = invoice.balance ?? (invoice.total - invoice.amount_paid);

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* HEADER NAVIGATION */}
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'space-between',
          paddingHorizontal: 16,
          paddingVertical: 12,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
        }}
      >
        <Pressable onPress={() => navigation.goBack()} style={{padding: 4}}>
          <Icon name="arrow-left" size={20} color={tokens.textPrimary} />
        </Pressable>
        <Text style={{color: tokens.textPrimary, fontSize: 15, fontWeight: '800'}}>
          Invoice Details
        </Text>
        <Pressable onPress={handleShare} style={{padding: 4}}>
          <Icon name="share-2" size={20} color={tokens.textPrimary} />
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={{paddingBottom: 120}} showsVerticalScrollIndicator={false}>
        
        {/* HEADER INFORMATION BLOCK */}
        <View
          style={{
            alignItems: 'center',
            paddingVertical: 24,
            paddingHorizontal: 20,
            backgroundColor: tokens.surfaceCard,
            borderBottomWidth: 1,
            borderBottomColor: tokens.borderDefault,
            marginBottom: 16,
          }}
        >
          {/* Reference */}
          <Text
            style={{
              color: tokens.textSecondary,
              fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
              fontSize: 14,
              fontWeight: '700',
              marginBottom: 10,
            }}
          >
            {invoice.reference}
          </Text>

          {/* Status Badge */}
          <View style={{marginBottom: 16}}>
            <StatusBadge status={invoice.status} tokens={tokens} />
          </View>

          {/* Amount Card (Focal Point) */}
          <Text
            style={{
              color: isOverdue ? '#EF4444' : tokens.textPrimary,
              fontSize: 36,
              fontWeight: '900',
              fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
              marginBottom: 8,
            }}
          >
            {formatCurrency(invoice.total)}
          </Text>

          {/* Date descriptor below amount */}
          <View style={{flexDirection: 'row', alignItems: 'center'}}>
            {isPaid ? (
              <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <Icon name="check-circle" size={14} color="#10B981" style={{marginRight: 6}} />
                <Text style={{color: '#10B981', fontSize: 13, fontWeight: '700'}}>
                  Paid on {invoice.paid_at ? invoice.paid_at.split(' ')[0] : invoice.due_date}
                </Text>
              </View>
            ) : isOverdue ? (
              <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <PulsingDot />
                <Text style={{color: '#EF4444', fontSize: 13, fontWeight: '800'}}>
                  {overdueDays} days overdue (Due {invoice.due_date})
                </Text>
              </View>
            ) : (
              <Text style={{color: tokens.textSecondary, fontSize: 13, fontWeight: '600'}}>
                Due {invoice.due_date}
              </Text>
            )}
          </View>

          {/* Recipient Context Line */}
          <Text
            style={{
              color: tokens.textTertiary,
              fontSize: 11,
              fontWeight: '600',
              textAlign: 'center',
              marginTop: 14,
              lineHeight: 16,
            }}
          >
            {contextLine}
          </Text>
        </View>

        {/* DRAFT EDITABILITY / READ-ONLY BANNER */}
        <View style={{marginHorizontal: 16, marginBottom: 16}}>
          {isDraft ? (
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: `${tokens.brandPrimary}15`,
                borderWidth: 1,
                borderColor: `${tokens.brandPrimary}33`,
                borderRadius: 12,
                padding: 12,
              }}
            >
              <Icon name="edit-2" size={16} color={tokens.brandPrimary} style={{marginRight: 10}} />
              <Text style={{color: tokens.brandPrimary, fontSize: 11, fontWeight: '700', flex: 1, lineHeight: 15}}>
                This invoice is in Draft mode. You can tap on any line item below to edit it, or click "+ Add line item".
              </Text>
            </View>
          ) : (
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: tokens.surfaceSunken,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 12,
                padding: 12,
              }}
            >
              <Icon name="lock" size={16} color={tokens.textTertiary} style={{marginRight: 10}} />
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', flex: 1, lineHeight: 15}}>
                This invoice has been sent and cannot be edited.{' '}
                <Text
                  onPress={() => Alert.alert('Credit Note Adjustment', 'Adjustments or credit notes must be issued from the VillaCRM desktop workspace.')}
                  style={{color: tokens.brandPrimary, textDecorationLine: 'underline'}}
                >
                  Create an adjustment instead.
                </Text>
              </Text>
            </View>
          )}
        </View>

        {/* LINE ITEMS DETAIL BLOCK */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 20,
            marginHorizontal: 16,
            padding: 16,
            marginBottom: 16,
            ...tokens.shadowSm,
          }}
        >
          <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '800', textTransform: 'uppercase', marginBottom: 12, letterSpacing: 0.5}}>
            Details
          </Text>

          {/* Line items list */}
          <View style={{marginBottom: 12}}>
            {invoice.line_items.map((item, idx) => {
              const isFee = item.description.toLowerCase().includes('fee') || item.description.toLowerCase().includes('late');
              return (
                <Pressable
                  key={idx}
                  disabled={!isDraft}
                  onPress={() => openEditLineItem(idx)}
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingVertical: 10,
                    borderBottomWidth: 1,
                    borderBottomColor: tokens.borderSubtle,
                  }}
                >
                  <View style={{flex: 1, marginRight: 8}}>
                    <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                      {item.description}
                    </Text>
                    <Text style={{color: tokens.textTertiary, fontSize: 10, textTransform: 'uppercase', marginTop: 3}}>
                      {item.category} {isDraft && '· Tap to edit'}
                    </Text>
                  </View>
                  <Text
                    style={{
                      color: isFee ? '#F59E0B' : tokens.textPrimary,
                      fontSize: 13,
                      fontWeight: '700',
                      fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
                    }}
                  >
                    {formatCurrency(item.amount)}
                  </Text>
                </Pressable>
              );
            })}
          </View>

          {/* Add item button (Draft mode only) */}
          {isDraft && (
            <Pressable
              onPress={openAddLineItem}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'center',
                paddingVertical: 10,
                borderWidth: 1,
                borderStyle: 'dashed',
                borderColor: tokens.brandPrimary,
                borderRadius: 8,
                marginTop: 8,
              }}
            >
              <Icon name="plus" size={14} color={tokens.brandPrimary} style={{marginRight: 6}} />
              <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '800'}}>
                Add line item
              </Text>
            </Pressable>
          )}

          {/* Totals ledger */}
          <View style={{marginTop: 12, gap: 8}}>
            {/* Subtotal */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
              <Text style={{color: tokens.textSecondary, fontSize: 12}}>{t('finance.subtotal')}</Text>
              <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '600', fontFamily: 'monospace'}}>
                {formatCurrency(invoice.subtotal)}
              </Text>
            </View>

            {/* Tax */}
            {invoice.tax_amount > 0 && (
              <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
                <Text style={{color: tokens.textSecondary, fontSize: 12}}>{t('finance.tax')}</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '600', fontFamily: 'monospace'}}>
                  {formatCurrency(invoice.tax_amount)}
                </Text>
              </View>
            )}

            {/* Separator */}
            <View style={{height: 1, backgroundColor: tokens.borderStrong, marginVertical: 4}} />

            {/* Total */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
              <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800'}}>{t('finance.total')}</Text>
              <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '900', fontFamily: 'monospace'}}>
                {formatCurrency(invoice.total)}
              </Text>
            </View>

            {/* Paid & Remaining if outstanding */}
            {invoice.amount_paid > 0 && (
              <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
                <Text style={{color: '#10B981', fontSize: 12, fontWeight: '700'}}>Amount Paid</Text>
                <Text style={{color: '#10B981', fontSize: 12, fontWeight: '700', fontFamily: 'monospace'}}>
                  {formatCurrency(invoice.amount_paid)}
                </Text>
              </View>
            )}

            {invoice.balance > 0 && (
              <View style={{flexDirection: 'row', justifyContent: 'space-between'}}>
                <Text style={{color: '#EF4444', fontSize: 12, fontWeight: '700'}}>{t('finance.balance')}</Text>
                <Text style={{color: '#EF4444', fontSize: 12, fontWeight: '700', fontFamily: 'monospace'}}>
                  {formatCurrency(invoice.balance)}
                </Text>
              </View>
            )}
          </View>
        </View>

        {/* PAYMENT DETAILS / PROGRESS SECTION */}
        {invoice.status !== 'draft' && (
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 20,
              marginHorizontal: 16,
              padding: 16,
              marginBottom: 16,
              ...tokens.shadowSm,
            }}
          >
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '800', textTransform: 'uppercase', marginBottom: 12, letterSpacing: 0.5}}>
              Payment Ledger
            </Text>

            {isPaid ? (
              <View style={{flexDirection: 'row', alignItems: 'center', padding: 4}}>
                <Icon name="info" size={16} color={tokens.textTertiary} style={{marginRight: 8}} />
                <Text style={{color: tokens.textSecondary, fontSize: 11, lineHeight: 16}}>
                  Paid via Bank Transfer on {invoice.paid_at ? invoice.paid_at.split(' ')[0] : invoice.due_date} (Tx Ref: TXN-{invoice.id * 13})
                </Text>
              </View>
            ) : invoice.amount_paid > 0 ? (
              <View>
                {/* Progress bar info */}
                <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                  Paid: {formatCurrency(invoice.amount_paid)} · Remaining: {formatCurrency(remainingBalance)}
                </Text>
                
                {/* Progress bar */}
                <View style={{width: '100%', height: 6, backgroundColor: tokens.surfaceSunken, borderRadius: 3, overflow: 'hidden'}}>
                  <View
                    style={{
                      width: `${(invoice.amount_paid / invoice.total) * 100}%`,
                      height: '100%',
                      backgroundColor: '#10B981',
                      borderRadius: 3,
                    }}
                  />
                </View>
              </View>
            ) : (
              <View style={{flexDirection: 'row', alignItems: 'center', padding: 4}}>
                <Icon name="clock" size={16} color={tokens.textTertiary} style={{marginRight: 8}} />
                <Text style={{color: tokens.textSecondary, fontSize: 11, lineHeight: 16}}>
                  This invoice is outstanding. Send payment reminders or click "Record Payment" to clear the ledger balance.
                </Text>
              </View>
            )}
          </View>
        )}

        {/* AI CONTEXT CARD */}
        {aiInsight.length > 0 && (
          <View
            style={{
              backgroundColor: '#6366F10F',
              borderWidth: 1,
              borderColor: '#6366F122',
              borderRadius: 20,
              marginHorizontal: 16,
              padding: 16,
              marginBottom: 16,
            }}
          >
            <View style={{flexDirection: 'row', alignItems: 'center', marginBottom: 6}}>
              <Icon name="zap" size={14} color="#6366F1" style={{marginRight: 6}} />
              <Text style={{color: '#6366F1', fontSize: 11, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 0.5}}>
                AI Insight
              </Text>
            </View>
            <Text style={{color: tokens.textPrimary, fontSize: 12, lineHeight: 17}}>
              {aiInsight}
            </Text>
          </View>
        )}

      </ScrollView>

      {/* STICKY BOTTOM ACTION BAR */}
      <View
        style={{
          position: 'absolute',
          bottom: 0,
          left: 0,
          right: 0,
          backgroundColor: tokens.surfaceCard,
          borderTopWidth: 1,
          borderTopColor: tokens.borderStrong,
          paddingHorizontal: 20,
          paddingTop: 12,
          paddingBottom: insets.bottom > 0 ? insets.bottom : 12,
          ...tokens.shadowMd,
        }}
      >
        {isDraft ? (
          // DRAFT STATUS ACTIONS
          <View>
            <Pressable
              onPress={handleSendInvoice}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 12,
                paddingVertical: 14,
                alignItems: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Text style={{color: '#ffffff', fontSize: 14, fontWeight: '800'}}>
                Send Invoice
              </Text>
            </Pressable>
          </View>
        ) : isPaid ? (
          // PAID STATUS ACTIONS
          <View>
            <Pressable
              onPress={handleDownloadPDF}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 12,
                paddingVertical: 14,
                alignItems: 'center',
                flexDirection: 'row',
                justifyContent: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Icon name="download" size={14} color="#ffffff" style={{marginRight: 6}} />
              <Text style={{color: '#ffffff', fontSize: 14, fontWeight: '800'}}>
                Download Receipt
              </Text>
            </Pressable>

            {/* Share secondary link */}
            <Pressable
              onPress={handleShare}
              style={{
                alignItems: 'center',
                paddingVertical: 10,
                marginTop: 6,
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '800'}}>
                Share Receipt
              </Text>
            </Pressable>
          </View>
        ) : (
          // UNPAID / OVERDUE STATUS ACTIONS
          <View>
            {/* Record Payment primary */}
            <Pressable
              onPress={openRecordPayment}
              style={{
                backgroundColor: '#10B981',
                borderRadius: 12,
                paddingVertical: 14,
                alignItems: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Text style={{color: '#ffffff', fontSize: 14, fontWeight: '800'}}>
                Record Payment
              </Text>
            </Pressable>

            {/* Secondary Actions 3-across row */}
            <View
              style={{
                flexDirection: 'row',
                justifyContent: 'space-around',
                borderTopWidth: 1,
                borderTopColor: tokens.borderSubtle,
                marginTop: 10,
                paddingTop: 10,
              }}
            >
              {/* Send Reminder */}
              <Pressable
                onPress={() => {
                  Vibration.vibrate(5);
                  Alert.alert('Send Reminder', `Drafting rent reminder notification for ${getRecipientName(invoice)}.`, [
                    {text: 'Cancel', style: 'cancel'},
                    {text: 'Send via WhatsApp', onPress: () => Alert.alert('Success', 'Reminder sent successfully.')},
                  ]);
                }}
                style={{alignItems: 'center', flex: 1}}
              >
                <Icon name="bell" size={16} color={tokens.textSecondary} style={{marginBottom: 4}} />
                <Text style={{color: tokens.textSecondary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>
                  Reminder
                </Text>
              </Pressable>

              {/* Download PDF */}
              <Pressable
                onPress={handleDownloadPDF}
                style={{alignItems: 'center', flex: 1}}
              >
                <Icon name="download" size={16} color={tokens.textSecondary} style={{marginBottom: 4}} />
                <Text style={{color: tokens.textSecondary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>
                  Download
                </Text>
              </Pressable>

              {/* Share */}
              <Pressable
                onPress={handleShare}
                style={{alignItems: 'center', flex: 1}}
              >
                <Icon name="share-2" size={16} color={tokens.textSecondary} style={{marginBottom: 4}} />
                <Text style={{color: tokens.textSecondary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>
                  Share
                </Text>
              </Pressable>
            </View>
          </View>
        )}
      </View>

      {/* MODAL A: RECORD PAYMENT BOTTOM SHEET (SHARED COMPONENT) */}
      <RecordPaymentModal
        visible={showPayModal}
        onClose={() => setShowPayModal(false)}
        prefilledAmount={invoice ? String(invoice.balance ?? (invoice.total - invoice.amount_paid)) : '0'}
        onConfirm={handleRecordPaymentSubmit}
        isSubmitting={isRecordingPayment}
      />

      {/* MODAL B: EDIT/ADD LINE ITEM (DRAFT MODE) */}
      <Modal
        visible={showEditItemModal}
        transparent
        animationType="fade"
        onRequestClose={() => setShowEditItemModal(false)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'center',
            alignItems: 'center',
            padding: 20,
          }}
          onPress={() => setShowEditItemModal(false)}
        >
          <Pressable
            style={{
              width: '90%',
              backgroundColor: tokens.surfaceCard,
              borderRadius: 24,
              borderWidth: 1,
              borderColor: tokens.borderStrong,
              padding: 20,
              ...tokens.shadowMd,
            }}
            onPress={(e) => e.stopPropagation()}
          >
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <Text style={{color: tokens.textPrimary, fontSize: 15, fontWeight: '800'}}>
                {editingItemIndex !== null ? 'Edit Line Item' : 'Add Line Item'}
              </Text>
              <Pressable
                onPress={() => setShowEditItemModal(false)}
                style={{
                  width: 28,
                  height: 28,
                  borderRadius: 14,
                  backgroundColor: tokens.surfaceRaised,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Icon name="x" size={14} color={tokens.textSecondary} />
              </Pressable>
            </View>

            {/* Description field */}
            <View style={{marginBottom: 16}}>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 6}}>
                Description
              </Text>
              <TextInput
                value={editItemDesc}
                onChangeText={setEditItemDesc}
                placeholder="e.g. Service Charge Adjustment"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceSunken,
                  color: tokens.textPrimary,
                  fontSize: 13,
                  paddingVertical: 10,
                  paddingHorizontal: 12,
                  borderRadius: 8,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                }}
              />
            </View>

            {/* Amount field */}
            <View style={{marginBottom: 20}}>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 6}}>
                Amount (₦)
              </Text>
              <TextInput
                value={editItemAmount}
                onChangeText={setEditItemAmount}
                keyboardType="numeric"
                placeholder="e.g. 25000"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceSunken,
                  color: tokens.textPrimary,
                  fontSize: 13,
                  paddingVertical: 10,
                  paddingHorizontal: 12,
                  borderRadius: 8,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                }}
              />
            </View>

            {/* Submit / delete buttons */}
            <View style={{flexDirection: 'row', justifyContent: 'flex-end', gap: 10}}>
              {editingItemIndex !== null && (
                <Pressable
                  onPress={handleDeleteLineItem}
                  style={{
                    backgroundColor: '#EF444415',
                    borderWidth: 1,
                    borderColor: '#EF444433',
                    borderRadius: 10,
                    paddingVertical: 10,
                    paddingHorizontal: 14,
                    alignItems: 'center',
                    justifyContent: 'center',
                  }}
                >
                  <Text style={{color: '#EF4444', fontSize: 12, fontWeight: '700'}}>Delete</Text>
                </Pressable>
              )}
              <Pressable
                onPress={() => setShowEditItemModal(false)}
                style={{
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 10,
                  paddingVertical: 10,
                  paddingHorizontal: 14,
                }}
              >
                <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700'}}>Cancel</Text>
              </Pressable>
              <Pressable
                onPress={handleSaveLineItem}
                style={{
                  backgroundColor: tokens.brandPrimary,
                  borderRadius: 10,
                  paddingVertical: 10,
                  paddingHorizontal: 16,
                  ...tokens.shadowSm,
                }}
              >
                <Text style={{color: '#ffffff', fontSize: 12, fontWeight: '800'}}>Save</Text>
              </Pressable>
            </View>
          </Pressable>
        </Pressable>
      </Modal>

      {/* SIMULATED PDF DOWNLOAD PROGRESS OVERLAY */}
      {isDownloading && (
        <View
          style={{
            position: 'absolute',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            backgroundColor: 'rgba(0, 0, 0, 0.4)',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 9999,
          }}
        >
          <View
            style={{
              width: 140,
              backgroundColor: tokens.surfaceCard,
              borderRadius: 16,
              padding: 20,
              alignItems: 'center',
              borderWidth: 1,
              borderColor: tokens.borderStrong,
            }}
          >
            <ActivityIndicator color={tokens.brandPrimary} size="large" style={{marginBottom: 12}} />
            <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '800'}}>
              Generating PDF...
            </Text>
          </View>
        </View>
      )}

    </SafeAreaView>
  );
}
