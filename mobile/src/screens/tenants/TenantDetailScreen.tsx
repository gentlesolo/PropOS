import React, {useState, useRef, useEffect} from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation} from '@react-navigation/native';
import type {RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {tenantsApi, leasesApi, TenantDetail, PaymentItem} from '../../api/tenants';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTheme} from '../../theme/ThemeProvider';
import {useTranslation} from '../../i18n';

type Route = RouteProp<TenantsStackParamList, 'TenantDetail'>;
type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

// Pulsing dot for overdue indicators
function PulsingDot() {
  const opacity = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {toValue: 0.3, duration: 800, useNativeDriver: true}),
        Animated.timing(opacity, {toValue: 1, duration: 800, useNativeDriver: true}),
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

export function TenantDetailScreen() {
  const {t} = useTranslation();
  const {tokens} = useTheme();
  const route = useRoute<Route>();
  const navigation = useNavigation<NavProp>();
  const {tenantId} = route.params;
  const queryClient = useQueryClient();

  // Screen UI state
  const [activeTab, setActiveTab] = useState<'docs' | 'notes'>('docs');
  const [showPayModal, setShowPayModal] = useState(false);
  const [showRenewalBanner, setShowRenewalBanner] = useState(true);

  // Form states for Record Payment Bottom Sheet
  const [amountPaid, setAmountPaid] = useState('');
  const [paidDate, setPaidDate] = useState('2026-06-13');
  const [paymentMethod, setPaymentMethod] = useState<'Bank Transfer' | 'Cash' | 'Card'>('Bank Transfer');

  // AI Reminder Modal states
  const [showReminderModal, setShowReminderModal] = useState(false);
  const [reminderDraftText, setReminderDraftText] = useState('');
  const [isSendingReminder, setIsSendingReminder] = useState(false);

  // Query tenant details
  const {data, isLoading} = useQuery({
    queryKey: ['tenant', tenantId],
    queryFn: () => tenantsApi.show(tenantId),
  });

  const tenant = data?.data;
  const currencySymbol = 'R'; // Configured currency

  // recordPayment mutation
  const payMutation = useMutation({
    mutationFn: (body: {amount: number; date: string; method: string}) =>
      leasesApi.recordPayment(tenant!.active_lease!.id, {
        amount_paid: body.amount,
        paid_date: body.date,
        payment_method: body.method.toLowerCase() as any,
      }),
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tenant', tenantId]});
      setShowPayModal(false);
      Vibration.vibrate(20);
      Alert.alert('Payment Recorded', 'The payment has been marked as Paid successfully.');
    },
    onError: () => {
      Alert.alert('Error', 'Failed to record payment. Please try again.');
    },
  });

  if (isLoading || !tenant) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, alignItems: 'center', justifyContent: 'center'}}>
        <ActivityIndicator color={tokens.brandPrimary} />
      </View>
    );
  }

  const lease = tenant.active_lease;

  // Header rent badge styles
  const renderRentBadgeLarge = () => {
    let badgeText = 'Paid';
    let badgeColor = tokens.successText;
    let badgeBg = `${tokens.successText}15`;
    let isOverdue = false;

    if (tenant.rent_status === 'due') {
      badgeText = `Due in ${tenant.rent_due_days} days`;
      badgeColor = '#F59E0B'; // Amber
      badgeBg = '#FEF3C7';
    } else if (tenant.rent_status === 'overdue') {
      badgeText = `Overdue — ${tenant.rent_due_days} days`;
      badgeColor = '#EF4444'; // Red
      badgeBg = '#FEE2E2';
      isOverdue = true;
    }

    return (
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          backgroundColor: badgeBg,
          paddingHorizontal: 12,
          paddingVertical: 6,
          borderRadius: 20,
          marginTop: 10,
        }}
      >
        {isOverdue && <PulsingDot />}
        <Text style={{color: badgeColor, fontSize: 13, fontWeight: '800'}}>
          {badgeText}
        </Text>
      </View>
    );
  };

  // Open Property Sheet
  const openPropertySheet = () => {
    Alert.alert('Property Info', `${tenant.property}\nManager: Sarah Jenkins\nFICA Status: Compliant`);
  };

  // Send AI Reminder draft action
  const openReminderDraft = () => {
    const text = `Hi ${tenant.full_name},\n\nYour rent payment of ${currencySymbol}${tenant.monthly_rent?.toLocaleString()} for ${tenant.property} is currently overdue by ${tenant.rent_due_days} days. Please settle this outstanding balance as soon as possible.\n\nWarm regards,\nVillaCRM Lettings`;
    setReminderDraftText(text);
    setShowReminderModal(true);
  };

  const executeSendReminder = () => {
    setIsSendingReminder(true);
    setTimeout(() => {
      setIsSendingReminder(false);
      setShowReminderModal(false);
      Alert.alert('Reminder Sent', 'Rent reminder sent successfully.');
    }, 800);
  };

  // Pre-fill payment input on trigger
  const triggerRecordPayment = () => {
    setAmountPaid(String(tenant.monthly_rent ?? ''));
    setShowPayModal(true);
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* Navigation Header */}
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
        <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700'}}>
          Tenant Profile
        </Text>
        <Pressable onPress={() => Alert.alert('Menu', 'Settings options')} style={{padding: 4}}>
          <Icon name="more-horizontal" size={20} color={tokens.textPrimary} />
        </Pressable>
      </View>

      <ScrollView contentContainerStyle={{paddingBottom: 40}} showsVerticalScrollIndicator={false}>
        {/* HEADER BLOCK */}
        <View
          style={{
            alignItems: 'center',
            paddingVertical: 24,
            paddingHorizontal: 20,
            backgroundColor: tokens.surfaceCard,
            borderBottomWidth: 1,
            borderBottomColor: tokens.borderDefault,
            ...tokens.shadowSm,
          }}
        >
          {/* Avatar (80px) */}
          <View
            style={{
              width: 80,
              height: 80,
              borderRadius: 40,
              backgroundColor: `${tokens.brandPrimary}1E`,
              alignItems: 'center',
              justifyContent: 'center',
              marginBottom: 12,
              borderWidth: 1.5,
              borderColor: tokens.brandPrimary,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 24}}>
              {(tenant.full_name ?? 'T').split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase()}
            </Text>
          </View>

          {/* Name & Property Subtitle */}
          <Text style={{color: tokens.textPrimary, fontSize: 20, fontWeight: '800', textAlign: 'center'}}>
            {tenant.full_name}
          </Text>

          <Pressable onPress={openPropertySheet} style={{marginTop: 4}}>
            <Text
              style={{
                color: tokens.brandPrimary,
                fontSize: 13,
                fontWeight: '600',
                textDecorationLine: 'underline',
                textAlign: 'center',
              }}
            >
              {tenant.property}
            </Text>
          </Pressable>

          {/* Rent Status Badge */}
          {renderRentBadgeLarge()}
        </View>

        {/* QUICK ACTIONS ROW */}
        <View
          style={{
            flexDirection: 'row',
            justifyContent: 'space-between',
            paddingHorizontal: 20,
            paddingVertical: 16,
            backgroundColor: tokens.surfaceCard,
            borderBottomWidth: 1,
            borderBottomColor: tokens.borderDefault,
          }}
        >
          {/* Action 1: Call */}
          <Pressable
            onPress={() => Alert.alert('Call', `Dialing ${tenant.full_name}...`)}
            style={{alignItems: 'center', flex: 1}}
          >
            <View
              style={{
                width: 42,
                height: 42,
                borderRadius: 21,
                backgroundColor: tokens.surfaceRaised,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 6,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            >
              <Icon name="phone" size={16} color={tokens.textPrimary} />
            </View>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>Call</Text>
          </Pressable>

          {/* Action 2: WhatsApp */}
          <Pressable
            onPress={() => Alert.alert('WhatsApp', 'Opening chat...')}
            style={{alignItems: 'center', flex: 1}}
          >
            <View
              style={{
                width: 42,
                height: 42,
                borderRadius: 21,
                backgroundColor: tokens.surfaceRaised,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 6,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            >
              <Icon name="message-circle" size={16} color={tokens.textPrimary} />
            </View>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>WhatsApp</Text>
          </Pressable>

          {/* Action 3: SMS */}
          <Pressable
            onPress={() => Alert.alert('SMS', 'Opening messaging compose...')}
            style={{alignItems: 'center', flex: 1}}
          >
            <View
              style={{
                width: 42,
                height: 42,
                borderRadius: 21,
                backgroundColor: tokens.surfaceRaised,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 6,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            >
              <Icon name="message-square" size={16} color={tokens.textPrimary} />
            </View>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>SMS</Text>
          </Pressable>

          {/* Action 4: Send Reminder (Financial Action) */}
          <Pressable onPress={openReminderDraft} style={{alignItems: 'center', flex: 1}}>
            <View
              style={{
                width: 42,
                height: 42,
                borderRadius: 21,
                backgroundColor: '#FEF3C7', // Amber tint
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 6,
                borderWidth: 1,
                borderColor: '#FCD34D',
              }}
            >
              <Icon name="bell" size={16} color="#D97706" />
            </View>
            <Text style={{color: '#D97706', fontSize: 11, fontWeight: '700'}}>Reminder</Text>
          </Pressable>
        </View>

        {/* EXPIRED / EXPIRING LEASE BANNER */}
        {showRenewalBanner && lease && lease.days_until_expiry <= 60 && (
          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              justifyContent: 'space-between',
              backgroundColor: '#FEF3C7', // Amber
              borderWidth: 1,
              borderColor: '#FCD34D',
              borderRadius: 12,
              padding: 12,
              marginHorizontal: 20,
              marginTop: 16,
            }}
          >
            <View style={{flex: 1, marginRight: 10}}>
              <Text style={{color: '#B45309', fontSize: 13, fontWeight: '800'}}>
                Lease ends in {lease.days_until_expiry} days
              </Text>
              <Text style={{color: '#D97706', fontSize: 11, fontWeight: '600', marginTop: 2}}>
                Would you like to initiate the renewal workflow?
              </Text>
            </View>
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
              <Pressable
                onPress={() => Alert.alert('Renewal', 'Starting lease renewal wizard...')}
                style={{
                  backgroundColor: '#D97706',
                  borderRadius: 6,
                  paddingHorizontal: 10,
                  paddingVertical: 6,
                }}
              >
                <Text style={{color: '#ffffff', fontSize: 11, fontWeight: '800'}}>Renew</Text>
              </Pressable>
              <Pressable onPress={() => setShowRenewalBanner(false)}>
                <Icon name="x" size={16} color="#B45309" />
              </Pressable>
            </View>
          </View>
        )}

        {/* LEASE SUMMARY CARD */}
        {lease && (
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 16,
              padding: 16,
              marginHorizontal: 20,
              marginTop: 16,
              ...tokens.shadowSm,
            }}
          >
            <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5, marginBottom: 12}}>
              Lease Terms
            </Text>

            <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 14}}>
              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Lease Start</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700', marginTop: 3}}>
                  {lease.start_date}
                </Text>
              </View>

              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Lease End</Text>
                <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 3}}>
                  <Text style={{color: lease.days_until_expiry <= 60 ? '#F59E0B' : tokens.textPrimary, fontSize: 12, fontWeight: '700'}}>
                    {lease.end_date}
                  </Text>
                  {lease.days_until_expiry <= 60 && (
                    <View style={{backgroundColor: '#FEF3C7', borderRadius: 4, paddingHorizontal: 4, paddingVertical: 1, marginLeft: 4}}>
                      <Text style={{color: '#D97706', fontSize: 8, fontWeight: '800'}}>Renewal Due</Text>
                    </View>
                  )}
                </View>
              </View>

              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Monthly Rent</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800', fontFamily: 'monospace', marginTop: 3}}>
                  {currencySymbol}{lease.monthly_rent?.toLocaleString()}
                </Text>
              </View>

              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Deposit Held</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800', fontFamily: 'monospace', marginTop: 3}}>
                  {currencySymbol}{lease.deposit_amount?.toLocaleString()}
                </Text>
              </View>

              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Payment Day</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700', marginTop: 3}}>
                  Day {lease.payment_day} of month
                </Text>
              </View>

              <View style={{width: '45%'}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>Status</Text>
                <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '800', textTransform: 'capitalize', marginTop: 3}}>
                  {lease.status}
                </Text>
              </View>
            </View>
          </View>
        )}

        {/* AI SUMMARY CARD */}
        <View
          style={{
            backgroundColor: tokens.surfaceCard,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            borderRadius: 16,
            padding: 16,
            marginHorizontal: 20,
            marginTop: 16,
            position: 'relative',
            overflow: 'hidden',
            ...tokens.shadowSm,
          }}
        >
          <View style={{position: 'absolute', left: 0, top: 0, bottom: 0, width: 4, backgroundColor: '#10B981'}} />
          <View style={{paddingLeft: 6}}>
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 6}}>
              <Text style={{fontSize: 13, color: '#10B981'}}>✦</Text>
              <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                AI Insight
              </Text>
            </View>
            <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 17}}>
              {tenant.rent_status === 'overdue'
                ? `${tenant.full_name} has paid on time for 11 of the last 12 months. This payment is currently ${tenant.rent_due_days} days overdue — her first late payment since moving in. No reminder sent yet this cycle.`
                : `${tenant.full_name} has a perfect payment track record, consistently transferring rent 2 days before the due date. Recommending regular automated receipts.`}
            </Text>
          </View>
        </View>

        {/* PAYMENT HISTORY */}
        <View style={{marginHorizontal: 20, marginTop: 20}}>
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 12}}>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5}}>
              Payment History
            </Text>
            <Pressable onPress={() => Alert.alert('Payments', 'Show full invoices ledger')}>
              <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '700'}}>
                View all
              </Text>
            </Pressable>
          </View>

          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 16,
              padding: 4,
              ...tokens.shadowSm,
            }}
          >
            {tenant.recent_payments.map((pmt: PaymentItem, idx: number) => {
              const isOverdue = pmt.status === 'overdue';
              const isDue = pmt.status === 'pending';
              const isPaid = pmt.status === 'paid';

              let statusText = 'Pending';
              let statusColor = tokens.textTertiary;
              if (isPaid) {
                statusText = 'Paid';
                statusColor = tokens.successText;
              } else if (isDue) {
                statusText = 'Due';
                statusColor = '#F59E0B';
              } else if (isOverdue) {
                statusText = 'Overdue';
                statusColor = '#EF4444';
              }

              // Extract Period Label (e.g. INV-2026-1-06 -> June 2026 representation)
              const getPeriodLabel = (ref: string) => {
                if (ref.includes('-06')) return 'June 2026';
                if (ref.includes('-05')) return 'May 2026';
                if (ref.includes('-04')) return 'April 2026';
                if (ref.includes('-03')) return 'March 2026';
                return 'Previous Cycle';
              };

              return (
                <Pressable
                  key={pmt.id}
                  onPress={() => Alert.alert('Invoice Detail', `Opening invoice ref: ${pmt.reference}`)}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    padding: 12,
                    borderBottomWidth: idx === tenant.recent_payments.length - 1 ? 0 : 1,
                    borderBottomColor: tokens.borderSubtle,
                  }}
                >
                  <View style={{flex: 1}}>
                    <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                      {getPeriodLabel(pmt.reference)}
                    </Text>
                    {isPaid && pmt.paid_date && (
                      <Text style={{color: tokens.textTertiary, fontSize: 11, marginTop: 2}}>
                        Paid {pmt.paid_date.split('-')[2]} Jun
                      </Text>
                    )}
                    {isOverdue && (
                      <Text style={{color: '#EF4444', fontSize: 11, fontWeight: '600', marginTop: 2}}>
                        12 days overdue
                      </Text>
                    )}
                  </View>

                  <View style={{alignItems: 'flex-end', gap: 6}}>
                    <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800', fontFamily: 'monospace'}}>
                      {currencySymbol}{pmt.amount_due?.toLocaleString()}
                    </Text>
                    <View style={{flexDirection: 'row', alignItems: 'center'}}>
                      <Text style={{color: statusColor, fontSize: 11, fontWeight: '800'}}>
                        {statusText}
                      </Text>
                      {isPaid && (
                        <Icon name="check" size={12} color={tokens.successText} style={{marginLeft: 4}} />
                      )}
                    </View>
                  </View>

                  {/* Record Payment shortcut button inline if overdue */}
                  {isOverdue && (
                    <Pressable
                      onPress={(e) => {
                        e.stopPropagation();
                        triggerRecordPayment();
                      }}
                      style={{
                        backgroundColor: '#10B981',
                        borderRadius: 8,
                        paddingHorizontal: 8,
                        paddingVertical: 6,
                        marginLeft: 10,
                      }}
                    >
                      <Text style={{color: '#ffffff', fontSize: 10, fontWeight: '800'}}>Record</Text>
                    </Pressable>
                  )}
                </Pressable>
              );
            })}
          </View>
        </View>

        {/* TABS SELECTOR (Documents / Notes) */}
        <View style={{flexDirection: 'row', borderBottomWidth: 1, borderBottomColor: tokens.borderDefault, marginHorizontal: 20, marginTop: 24}}>
          <Pressable
            onPress={() => setActiveTab('docs')}
            style={{
              paddingVertical: 10,
              borderBottomWidth: 2,
              borderBottomColor: activeTab === 'docs' ? tokens.brandPrimary : 'transparent',
              marginRight: 24,
            }}
          >
            <Text style={{color: activeTab === 'docs' ? tokens.textPrimary : tokens.textTertiary, fontSize: 13, fontWeight: '800'}}>
              Documents
            </Text>
          </Pressable>

          <Pressable
            onPress={() => setActiveTab('notes')}
            style={{
              paddingVertical: 10,
              borderBottomWidth: 2,
              borderBottomColor: activeTab === 'notes' ? tokens.brandPrimary : 'transparent',
            }}
          >
            <Text style={{color: activeTab === 'notes' ? tokens.textPrimary : tokens.textTertiary, fontSize: 13, fontWeight: '800'}}>
              Notes
            </Text>
          </Pressable>
        </View>

        {/* TAB CONTENTS */}
        <View style={{marginHorizontal: 20, marginTop: 12}}>
          {activeTab === 'docs' ? (
            /* Documents List */
            <View style={{gap: 8}}>
              {[
                {name: 'Lease_Agreement_Marina_Heights.pdf', type: 'file-text', size: '2.4 MB'},
                {name: 'Move_In_Inspection_Report.pdf', type: 'image', size: '4.8 MB'},
                {name: 'Tenant_FICA_Identity_Verification.pdf', type: 'lock', size: '840 KB'},
              ].map((doc, idx) => (
                <Pressable
                  key={idx}
                  onPress={() => Alert.alert('Download', `Downloading ${doc.name}...`)}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 12,
                    padding: 12,
                  }}
                >
                  <Icon name={doc.type} size={18} color={tokens.brandPrimary} style={{marginRight: 10}} />
                  <View style={{flex: 1}}>
                    <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700'}} numberOfLines={1}>
                      {doc.name}
                    </Text>
                    <Text style={{color: tokens.textTertiary, fontSize: 10, marginTop: 2}}>
                      {doc.size}
                    </Text>
                  </View>
                  <Icon name="download" size={14} color={tokens.textTertiary} />
                </Pressable>
              ))}
            </View>
          ) : (
            /* Notes Section (voice-note + text input pattern) */
            <View>
              {/* Text Input Row */}
              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 12,
                  flexDirection: 'row',
                  alignItems: 'center',
                  marginBottom: 16,
                }}
              >
                <TextInput
                  placeholder="Tap to type note..."
                  placeholderTextColor={tokens.textTertiary}
                  style={{flex: 1, color: tokens.textPrimary, fontSize: 13, padding: 0}}
                />
                <Pressable
                  onPress={() => Alert.alert('Voice Note', 'Recording audio memo...')}
                  style={{
                    width: 32,
                    height: 32,
                    borderRadius: 16,
                    backgroundColor: `${tokens.brandPrimary}1E`,
                    alignItems: 'center',
                    justifyContent: 'center',
                    marginLeft: 10,
                  }}
                >
                  <Icon name="mic" size={14} color={tokens.brandPrimary} />
                </Pressable>
              </View>

              {/* Notes list */}
              <View style={{gap: 10}}>
                {[
                  {date: '10 May 2026', author: 'Sarah Jenkins', note: 'Spoke with Adaeze. She confirmed EFT payment might be 2 days late due to bank holiday processing times.'},
                  {date: '15 Aug 2025', author: 'Sarah Jenkins', note: 'Lease signed and deposit check cleared.'},
                ].map((noteItem, idx) => (
                  <View
                    key={idx}
                    style={{
                      backgroundColor: tokens.surfaceCard,
                      borderWidth: 1,
                      borderColor: tokens.borderDefault,
                      borderRadius: 12,
                      padding: 12,
                    }}
                  >
                    <View style={{flexDirection: 'row', justifyContent: 'space-between', marginBottom: 4}}>
                      <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '800'}}>
                        {noteItem.author}
                      </Text>
                      <Text style={{color: tokens.textTertiary, fontSize: 10}}>
                        {noteItem.date}
                      </Text>
                    </View>
                    <Text style={{color: tokens.textPrimary, fontSize: 12, lineHeight: 16}}>
                      {noteItem.note}
                    </Text>
                  </View>
                ))}
              </View>
            </View>
          )}
        </View>
      </ScrollView>

      {/* RECORD PAYMENT BOTTOM SHEET / MODAL */}
      <Modal
        visible={showPayModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowPayModal(false)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'flex-end',
          }}
          onPress={() => setShowPayModal(false)}
        >
          <Pressable
            style={{
              backgroundColor: tokens.surfaceCard,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              borderWidth: 1,
              borderColor: tokens.borderStrong,
              padding: 24,
              paddingBottom: 40,
            }}
            onPress={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20}}>
              <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '800'}}>
                Record Rent Payment
              </Text>
              <Pressable
                onPress={() => setShowPayModal(false)}
                style={{
                  width: 32,
                  height: 32,
                  borderRadius: 16,
                  backgroundColor: tokens.surfaceRaised,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            {/* Amount input */}
            <View style={{marginBottom: 16}}>
              <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                Amount Paid ({currencySymbol})
              </Text>
              <TextInput
                value={amountPaid}
                onChangeText={setAmountPaid}
                keyboardType="decimal-pad"
                style={{
                  backgroundColor: tokens.surfaceSunken,
                  color: tokens.textPrimary,
                  fontSize: 16,
                  fontWeight: '800',
                  paddingVertical: 12,
                  paddingHorizontal: 16,
                  borderRadius: 12,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                }}
              />
            </View>

            {/* Date input */}
            <View style={{marginBottom: 16}}>
              <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                Date Paid
              </Text>
              <TextInput
                value={paidDate}
                onChangeText={setPaidDate}
                placeholder="YYYY-MM-DD"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceSunken,
                  color: tokens.textPrimary,
                  fontSize: 13,
                  paddingVertical: 12,
                  paddingHorizontal: 16,
                  borderRadius: 12,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                }}
              />
            </View>

            {/* Payment Method selector */}
            <View style={{marginBottom: 24}}>
              <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                Payment Method
              </Text>
              <View style={{flexDirection: 'row', gap: 8}}>
                {['Bank Transfer', 'Cash', 'Card'].map((meth) => {
                  const active = paymentMethod === meth;
                  return (
                    <Pressable
                      key={meth}
                      onPress={() => setPaymentMethod(meth as any)}
                      style={{
                        flex: 1,
                        paddingVertical: 10,
                        borderRadius: 8,
                        backgroundColor: active ? tokens.brandPrimary : tokens.surfaceRaised,
                        borderWidth: 1,
                        borderColor: active ? tokens.brandPrimary : tokens.borderDefault,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{color: active ? '#ffffff' : tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                        {meth}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>

            {/* CTA Mark Paid */}
            <Pressable
              onPress={() => payMutation.mutate({amount: parseFloat(amountPaid), date: paidDate, method: paymentMethod})}
              disabled={!amountPaid || payMutation.isPending}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 12,
                paddingVertical: 14,
                alignItems: 'center',
              }}
            >
              {payMutation.isPending ? (
                <ActivityIndicator color="#ffffff" size="small" />
              ) : (
                <Text style={{color: '#ffffff', fontSize: 14, fontWeight: '800'}}>
                  Mark as Paid
                </Text>
              )}
            </Pressable>
          </Pressable>
        </Pressable>
      </Modal>

      {/* AI REMINDER MODAL */}
      <Modal
        visible={showReminderModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowReminderModal(false)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'center',
            alignItems: 'center',
            padding: 20,
          }}
          onPress={() => setShowReminderModal(false)}
        >
          <Pressable
            style={{
              width: '100%',
              backgroundColor: tokens.surfaceCard,
              borderRadius: 24,
              borderWidth: 1,
              borderColor: tokens.borderStrong,
              padding: 20,
              ...tokens.shadowMd,
            }}
            onPress={(e) => e.stopPropagation()}
          >
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14}}>
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
                <Text style={{color: '#F59E0B', fontSize: 14}}>✦</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 15, fontWeight: '800'}}>
                  AI Rent Reminder
                </Text>
              </View>
              <Pressable
                onPress={() => setShowReminderModal(false)}
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

            <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700', marginBottom: 8}}>
              DRAFT MESSAGE FOR {tenant.full_name?.toUpperCase()}
            </Text>

            <View
              style={{
                backgroundColor: tokens.surfaceSunken,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                padding: 12,
                marginBottom: 20,
                minHeight: 120,
              }}
            >
              <TextInput
                multiline
                value={reminderDraftText}
                onChangeText={setReminderDraftText}
                style={{
                  color: tokens.textPrimary,
                  fontSize: 13,
                  lineHeight: 18,
                  textAlignVertical: 'top',
                  padding: 0,
                }}
              />
            </View>

            <View style={{flexDirection: 'row', gap: 10}}>
              <Pressable
                onPress={() => setShowReminderModal(false)}
                style={{
                  flex: 1,
                  paddingVertical: 12,
                  borderRadius: 12,
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  alignItems: 'center',
                }}
              >
                <Text style={{color: tokens.textSecondary, fontSize: 13, fontWeight: '800'}}>
                  Cancel
                </Text>
              </Pressable>

              <Pressable
                onPress={executeSendReminder}
                disabled={isSendingReminder}
                style={{
                  flex: 2,
                  paddingVertical: 12,
                  borderRadius: 12,
                  backgroundColor: tokens.brandPrimary,
                  alignItems: 'center',
                  flexDirection: 'row',
                  justifyContent: 'center',
                  gap: 6,
                }}
              >
                {isSendingReminder ? (
                  <ActivityIndicator color="#ffffff" size="small" />
                ) : (
                  <>
                    <Icon name="send" size={14} color="#ffffff" />
                    <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>
                      Send Reminder
                    </Text>
                  </>
                )}
              </Pressable>
            </View>
          </Pressable>
        </Pressable>
      </Modal>
    </SafeAreaView>
  );
}

// Inline SafeAreaView fallback wrapper
function SafeAreaView({children, style, edges}: {children: any; style: any; edges?: string[]}) {
  const insets = require('react-native-safe-area-context').useSafeAreaInsets();
  const paddingTop = edges?.includes('top') ? insets.top : 0;
  return <View style={[{paddingTop}, style]}>{children}</View>;
}
