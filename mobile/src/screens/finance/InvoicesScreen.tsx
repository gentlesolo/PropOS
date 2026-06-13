import React, {useCallback, useState, useMemo, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Animated,
  Alert,
  FlatList,
  Modal,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
  RefreshControl,
  StyleSheet,
  Platform,
} from 'react-native';
import {SafeAreaView, useSafeAreaInsets} from 'react-native-safe-area-context';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {invoicesApi, InvoiceListItem, InvoiceDetail} from '../../api/invoices';
import {contactsApi} from '../../api/contacts';
import {FinanceStackParamList} from '../../navigation/stacks/FinanceStack';
import {useTheme} from '../../theme/ThemeProvider';
import {useTranslation} from '../../i18n';
import {createMMKV} from 'react-native-mmkv';

type Nav = NativeStackNavigationProp<FinanceStackParamList, 'InvoicesList'>;

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
  const symbol = localStore.getString('currency_symbol') || '₦';
  return `${symbol}${amount.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
};

// Helper to format currency abbreviated (e.g. ₦4.2M)
const formatCurrencyAbbreviated = (amount: number) => {
  const symbol = localStore.getString('currency_symbol') || '₦';
  if (amount >= 1_000_000) {
    return `${symbol}${(amount / 1_000_000).toFixed(1)}M`;
  }
  if (amount >= 1_000) {
    return `${symbol}${(amount / 1_000).toFixed(1)}K`;
  }
  return `${symbol}${amount.toLocaleString()}`;
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
        paddingHorizontal: 8,
        paddingVertical: 2.5,
        borderRadius: 6,
        backgroundColor: bgColor,
        borderWidth: 1,
        borderColor: borderColor,
      }}
    >
      <Text
        style={{
          fontSize: 9,
          fontWeight: '900',
          textTransform: 'uppercase',
          color: textColor,
          letterSpacing: 0.5,
        }}
      >
        {status.replace('_', ' ')}
      </Text>
    </View>
  );
}

export function InvoicesScreen() {
  const {t} = useTranslation();
  const {tokens} = useTheme();
  const navigation = useNavigation<Nav>();
  const insets = useSafeAreaInsets();
  const queryClient = useQueryClient();

  // Core filter states
  const [search, setSearch] = useState('');
  const [filterTab, setFilterTab] = useState<'all' | 'outstanding' | 'overdue' | 'paid' | 'draft' | 'due_this_week' | 'paid_this_month'>('all');
  const [groupMode, setGroupMode] = useState<'status' | 'recipient'>('status');

  // Interactive UI states
  const [isPaidExpanded, setIsPaidExpanded] = useState(false);
  const [expandedRecipients, setExpandedRecipients] = useState<Record<string, boolean>>({});
  const [isSelectionMode, setIsSelectionMode] = useState(false);
  const [selectedInvoiceIds, setSelectedInvoiceIds] = useState<Record<number, boolean>>({});

  // Sheet / Modal visibility
  const [createSheetVisible, setCreateSheetVisible] = useState(false);
  const [reminderModalVisible, setReminderModalVisible] = useState(false);
  const [mockSettingsVisible, setMockSettingsVisible] = useState(false);

  // Mock control toggles for demonstrating empty states
  const [isBillingEnabled, setIsBillingEnabled] = useState(true);
  const [simulateEmptyList, setSimulateEmptyList] = useState(false);

  // Form states for creating one-off invoice
  const [formRecipient, setFormRecipient] = useState('');
  const [formRecipientSearch, setFormRecipientSearch] = useState('');
  const [formType, setFormType] = useState<'commission' | 'maintenance' | 'utility' | 'other'>('commission');
  const [formProperty, setFormProperty] = useState('');
  const [formReference, setFormReference] = useState('');
  const [formDueDate, setFormDueDate] = useState('');
  const [formLineItems, setFormLineItems] = useState<{description: string; amount: number}[]>([]);
  const [tempItemDesc, setTempItemDesc] = useState('');
  const [tempItemAmount, setTempItemAmount] = useState('');

  // Bulk reminder message text store
  const [reminderDrafts, setReminderDrafts] = useState<Record<number, string>>({});
  const [isSendingReminders, setIsSendingReminders] = useState(false);

  // Load local persisted invoices
  const [localInvoices, setLocalInvoices] = useState<InvoiceListItem[]>([]);
  useEffect(() => {
    try {
      const stored = localStore.getString('invoices');
      if (stored) {
        setLocalInvoices(JSON.parse(stored));
      }
    } catch (e) {
      console.error('Failed to load local invoices', e);
    }
  }, []);

  // Sync / cached timestamp
  const [syncTimeText] = useState('as of 12:10 PM');

  // Fetch Invoices Query
  const {data, isLoading, refetch, isRefetching} = useQuery({
    queryKey: ['invoices'],
    queryFn: () => invoicesApi.list().then((r) => r.data),
  });

  // Fetch Contacts for recipient selector
  const {data: contactsData} = useQuery({
    queryKey: ['contacts-simple'],
    queryFn: () => contactsApi.list({page: 1}).then((r) => r.data),
  });

  const apiInvoices = data?.data ?? [];

  // Merged invoices list (API + Local Storage)
  const allInvoices = useMemo(() => {
    if (simulateEmptyList) return [];
    const invoiceMap = new Map<number, InvoiceListItem>();
    apiInvoices.forEach((inv) => invoiceMap.set(inv.id, inv));
    localInvoices.forEach((inv) => invoiceMap.set(inv.id, inv));
    return Array.from(invoiceMap.values());
  }, [apiInvoices, localInvoices, simulateEmptyList]);

  // Generate Reference when opening the create modal
  const openCreateSheet = () => {
    Vibration.vibrate(10);
    const randRef = 'INV-' + Math.floor(Math.random() * 900000 + 100000);
    const inAWeek = new Date();
    inAWeek.setDate(inAWeek.getDate() + 7);
    const dateStr = inAWeek.toISOString().split('T')[0];

    setFormReference(randRef);
    setFormDueDate(dateStr);
    setFormRecipient('');
    setFormRecipientSearch('');
    setFormProperty('');
    setFormLineItems([]);
    setTempItemDesc('');
    setTempItemAmount('');
    setFormType('commission');
    setCreateSheetVisible(true);
  };

  // Save invoice locally
  const handleSaveInvoice = () => {
    if (!formRecipient.trim()) {
      Alert.alert('Validation Error', 'Please specify a recipient name.');
      return;
    }
    if (formLineItems.length === 0) {
      Alert.alert('Validation Error', 'Please add at least one line item.');
      return;
    }

    const itemTotal = formLineItems.reduce((sum, item) => sum + item.amount, 0);

    const newInvoice: InvoiceListItem & {recipient_name?: string} = {
      id: Math.floor(Math.random() * 90000) + 10000,
      reference: formReference.trim(),
      type: formType,
      status: 'sent', // defaulted to sent/unpaid
      subtotal: itemTotal,
      tax_amount: 0,
      total: itemTotal,
      amount_paid: 0,
      balance: itemTotal,
      due_date: formDueDate || new Date().toISOString().split('T')[0],
      period_month: new Date().getMonth() + 1,
      period_year: new Date().getFullYear(),
      property: formProperty.trim() || null,
      issued_at: new Date().toISOString(),
      paid_at: null,
      recipient_name: formRecipient.trim(),
    };

    const updated = [newInvoice, ...localInvoices];
    setLocalInvoices(updated);
    try {
      localStore.set('invoices', JSON.stringify(updated));
    } catch (e) {
      console.error(e);
    }

    setCreateSheetVisible(false);
    Alert.alert('Success', 'Invoice created successfully!');
  };

  // Add temp line item to list
  const handleAddLineItem = () => {
    const desc = tempItemDesc.trim();
    const amt = parseFloat(tempItemAmount);

    if (!desc) {
      Alert.alert('Validation Error', 'Please enter a description.');
      return;
    }
    if (isNaN(amt) || amt <= 0) {
      Alert.alert('Validation Error', 'Please enter a valid positive amount.');
      return;
    }

    setFormLineItems([...formLineItems, {description: desc, amount: amt}]);
    setTempItemDesc('');
    setTempItemAmount('');
  };

  // Remove line item
  const handleRemoveLineItem = (index: number) => {
    setFormLineItems(formLineItems.filter((_, i) => i !== index));
  };

  // Helper to extract recipient name safely
  const getRecipientName = (item: any) => {
    if (item.recipient_name) return item.recipient_name;
    if (item.tenant?.first_name || item.tenant?.last_name) {
      return `${item.tenant.first_name ?? ''} ${item.tenant.last_name ?? ''}`.trim();
    }
    if (item.lease?.tenant?.contact) {
      const contact = item.lease.tenant.contact;
      return `${contact.first_name ?? ''} ${contact.last_name ?? ''}`.trim();
    }
    return 'Unknown Recipient';
  };

  // Calculate dynamic stats from allInvoices
  const now = new Date();
  const startOfToday = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const next7Days = new Date(startOfToday.getTime() + 7 * 24 * 60 * 60 * 1000);

  const stats = useMemo(() => {
    let outstanding = 0;
    let dueThisWeek = 0;
    let paidThisMonth = 0;
    let overdue = 0;

    allInvoices.forEach((item) => {
      const dueDate = new Date(item.due_date);
      const isPaid = item.status === 'paid';
      const isVoid = item.status === 'void';
      const bal = item.balance ?? (item.total - item.amount_paid);

      if (!isPaid && !isVoid) {
        outstanding += bal;

        // Overdue status check
        const isOverdue = item.status === 'overdue' || dueDate < startOfToday;
        if (isOverdue) {
          overdue++;
        }

        // Due this week
        if (dueDate >= startOfToday && dueDate <= next7Days) {
          dueThisWeek += bal;
        }
      }

      if (isPaid) {
        const paidDate = item.paid_at ? new Date(item.paid_at) : dueDate;
        if (paidDate.getMonth() === now.getMonth() && paidDate.getFullYear() === now.getFullYear()) {
          paidThisMonth += item.total;
        }
      }
    });

    return {outstanding, dueThisWeek, paidThisMonth, overdue};
  }, [allInvoices]);

  // Apply filters and searches to list items
  const filteredInvoices = useMemo(() => {
    return allInvoices.filter((item) => {
      const recipient = getRecipientName(item);
      const matchSearch =
        recipient.toLowerCase().includes(search.toLowerCase()) ||
        item.reference.toLowerCase().includes(search.toLowerCase()) ||
        (item.property || '').toLowerCase().includes(search.toLowerCase());

      if (!matchSearch) return false;

      const dueDate = new Date(item.due_date);
      const isPaid = item.status === 'paid';
      const isVoid = item.status === 'void';

      if (filterTab === 'outstanding') {
        return !isPaid && !isVoid;
      }
      if (filterTab === 'overdue') {
        return (!isPaid && !isVoid) && (item.status === 'overdue' || dueDate < startOfToday);
      }
      if (filterTab === 'paid') {
        return isPaid;
      }
      if (filterTab === 'draft') {
        return item.status === 'draft';
      }
      if (filterTab === 'due_this_week') {
        return (!isPaid && !isVoid) && (dueDate >= startOfToday && dueDate <= next7Days);
      }
      if (filterTab === 'paid_this_month') {
        const paidDate = item.paid_at ? new Date(item.paid_at) : dueDate;
        return isPaid && (paidDate.getMonth() === now.getMonth() && paidDate.getFullYear() === now.getFullYear());
      }

      return true;
    });
  }, [allInvoices, search, filterTab]);

  // Grouped items logic
  const groupedData = useMemo(() => {
    if (groupMode === 'recipient') {
      const groups: Record<string, InvoiceListItem[]> = {};
      filteredInvoices.forEach((inv) => {
        const rName = getRecipientName(inv);
        if (!groups[rName]) groups[rName] = [];
        groups[rName].push(inv);
      });

      return Object.keys(groups).map((rName) => {
        const items = groups[rName];
        const outstandingBal = items.reduce((sum, item) => {
          if (item.status !== 'paid' && item.status !== 'void') {
            return sum + (item.balance ?? (item.total - item.amount_paid));
          }
          return sum;
        }, 0);

        return {
          title: rName,
          type: 'recipient' as const,
          outstandingBalance: outstandingBal,
          data: items.sort((a, b) => b.due_date.localeCompare(a.due_date)),
        };
      });
    }

    // Default status grouping
    const overdueList: InvoiceListItem[] = [];
    const dueThisWeekList: InvoiceListItem[] = [];
    const dueLaterList: InvoiceListItem[] = [];
    const paidList: InvoiceListItem[] = [];
    const draftList: InvoiceListItem[] = [];

    filteredInvoices.forEach((inv) => {
      const isPaid = inv.status === 'paid';
      const isVoid = inv.status === 'void';
      const isDraft = inv.status === 'draft';
      const dueDate = new Date(inv.due_date);

      if (isDraft) {
        draftList.push(inv);
      } else if (isPaid) {
        paidList.push(inv);
      } else if (isVoid) {
        // skip or categorize under paid/draft, but usually skip void in primary status groups
      } else {
        const isOverdue = inv.status === 'overdue' || dueDate < startOfToday;
        if (isOverdue) {
          overdueList.push(inv);
        } else if (dueDate >= startOfToday && dueDate <= next7Days) {
          dueThisWeekList.push(inv);
        } else {
          dueLaterList.push(inv);
        }
      }
    });

    const sections = [];
    if (overdueList.length > 0) {
      sections.push({title: 'Overdue', type: 'overdue' as const, data: overdueList});
    }
    if (dueThisWeekList.length > 0) {
      sections.push({title: 'Due This Week', type: 'due_week' as const, data: dueThisWeekList});
    }
    if (dueLaterList.length > 0) {
      sections.push({title: 'Due Later', type: 'due_later' as const, data: dueLaterList});
    }
    if (paidList.length > 0) {
      sections.push({title: 'Paid', type: 'paid' as const, data: paidList});
    }
    if (draftList.length > 0) {
      sections.push({title: 'Draft', type: 'draft' as const, data: draftList});
    }

    return sections;
  }, [filteredInvoices, groupMode]);

  // Overdue count for badge
  const overdueCountTotal = useMemo(() => {
    return allInvoices.filter((item) => {
      const isPaid = item.status === 'paid';
      const isVoid = item.status === 'void';
      const dueDate = new Date(item.due_date);
      return (!isPaid && !isVoid) && (item.status === 'overdue' || dueDate < startOfToday);
    }).length;
  }, [allInvoices]);

  // Bulk actions handling
  const handleLongPressInvoice = (invoice: InvoiceListItem) => {
    // Only allow selecting unpaid/outstanding invoices
    if (invoice.status === 'paid' || invoice.status === 'void') return;

    Vibration.vibrate(20);
    setIsSelectionMode(true);
    setSelectedInvoiceIds((prev) => ({
      ...prev,
      [invoice.id]: true,
    }));
  };

  const handlePressInvoice = (invoice: InvoiceListItem) => {
    if (isSelectionMode) {
      if (invoice.status === 'paid' || invoice.status === 'void') return;
      Vibration.vibrate(5);
      setSelectedInvoiceIds((prev) => {
        const next = {...prev};
        if (next[invoice.id]) {
          delete next[invoice.id];
        } else {
          next[invoice.id] = true;
        }
        return next;
      });
    } else {
      navigation.navigate('InvoiceDetail', {invoiceId: invoice.id});
    }
  };

  const selectedCount = Object.values(selectedInvoiceIds).filter(Boolean).length;

  const handleOpenRemindersModal = () => {
    if (selectedCount === 0) return;

    // Generate AI reminder drafts
    const drafts: Record<number, string> = {};
    allInvoices.forEach((inv) => {
      if (selectedInvoiceIds[inv.id]) {
        const recipient = getRecipientName(inv);
        const outstanding = inv.balance ?? (inv.total - inv.amount_paid);
        drafts[inv.id] = `Hi ${recipient},\n\nThis is a friendly reminder from VillaCRM that the invoice ${inv.reference} of ${formatCurrency(outstanding)} is overdue. Please settle it at your earliest convenience.\n\nBest regards,\nVillaCRM Accounts`;
      }
    });

    setReminderDrafts(drafts);
    setReminderModalVisible(true);
  };

  const handleSendReminders = () => {
    setIsSendingReminders(true);
    setTimeout(() => {
      setIsSendingReminders(false);
      setReminderModalVisible(false);
      setIsSelectionMode(false);
      setSelectedInvoiceIds({});
      Alert.alert('Success', `Reminders sent successfully for ${selectedCount} invoices!`);
    }, 1200);
  };

  // Format type icon circles
  const renderInvoiceTypeIcon = (type: string) => {
    let iconName = 'file-text';
    let bgColor = '#71717A1A';
    let iconColor = '#71717A';

    if (type === 'rent') {
      iconName = 'home';
      bgColor = `${tokens.brandPrimary}1E`;
      iconColor = tokens.brandPrimary;
    } else if (type === 'commission') {
      iconName = 'percent';
      bgColor = `${tokens.brandAccent}1E`;
      iconColor = tokens.brandAccent;
    } else if (type === 'maintenance') {
      iconName = 'tool';
      bgColor = '#EF44441A';
      iconColor = '#EF4444';
    } else if (type === 'utility') {
      iconName = 'zap';
      bgColor = '#3B82F61A';
      iconColor = '#3B82F6';
    }

    return (
      <View
        style={{
          width: 38,
          height: 38,
          borderRadius: 19,
          backgroundColor: bgColor,
          alignItems: 'center',
          justifyContent: 'center',
          marginRight: 12,
        }}
      >
        <Icon name={iconName} size={16} color={iconColor} />
      </View>
    );
  };

  // Render Row Item
  const renderInvoiceRow = (item: InvoiceListItem) => {
    const isSelected = selectedInvoiceIds[item.id] || false;
    const recipient = getRecipientName(item);
    const isOverdue = item.status === 'overdue' || (new Date(item.due_date) < startOfToday && item.status !== 'paid' && item.status !== 'void');
    const isDraft = item.status === 'draft';

    return (
      <Pressable
        key={item.id}
        onLongPress={() => handleLongPressInvoice(item)}
        onPress={() => handlePressInvoice(item)}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          padding: 16,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          opacity: isDraft ? 0.6 : 1,
        }}
      >
        {/* Selection Checkbox */}
        {isSelectionMode && (item.status !== 'paid' && item.status !== 'void') && (
          <View
            style={{
              width: 20,
              height: 20,
              borderRadius: 6,
              borderWidth: 2,
              borderColor: isSelected ? tokens.brandPrimary : tokens.borderStrong,
              backgroundColor: isSelected ? tokens.brandPrimary : 'transparent',
              alignItems: 'center',
              justifyContent: 'center',
              marginRight: 12,
            }}
          >
            {isSelected && <Icon name="check" size={12} color="#ffffff" />}
          </View>
        )}

        {/* Leading icon */}
        {renderInvoiceTypeIcon(item.type)}

        {/* Text descriptions */}
        <View style={{flex: 1, marginRight: 8}}>
          <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700'}} numberOfLines={1}>
            {recipient}
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '600', marginTop: 3}}>
            {item.type === 'rent' ? `Rent — ${item.reference}` : `${item.type.toUpperCase()} — ${item.reference}`}
          </Text>
          {item.property && (
            <Text style={{color: tokens.textTertiary, fontSize: 10, marginTop: 2}} numberOfLines={1}>
              {item.property}
            </Text>
          )}
        </View>

        {/* Price and status */}
        <View style={{alignItems: 'flex-end', justifyContent: 'center'}}>
          <View style={{flexDirection: 'row', alignItems: 'center'}}>
            {isOverdue && <PulsingDot />}
            <Text
              style={{
                color: isOverdue ? '#EF4444' : tokens.textPrimary,
                fontSize: 14,
                fontWeight: '700',
                fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
              }}
            >
              {formatCurrency(item.total)}
            </Text>
          </View>
          <View style={{marginTop: 5, flexDirection: 'row', gap: 6, alignItems: 'center'}}>
            <StatusBadge status={item.status} tokens={tokens} />
          </View>
          <Text style={{color: tokens.textTertiary, fontSize: 9, marginTop: 4, fontWeight: '600'}}>
            {item.status === 'paid' ? `Paid ${item.paid_at ? item.paid_at.split(' ')[0] : item.due_date}` : `Due ${item.due_date}`}
          </Text>
        </View>
      </Pressable>
    );
  };

  // Toggle recipient header expanded state
  const toggleRecipientExpanded = (name: string) => {
    setExpandedRecipients((prev) => ({
      ...prev,
      [name]: !prev[name],
    }));
  };

  // Main list renderer
  const renderListSections = () => {
    if (groupedData.length === 0) {
      return renderEmptyState();
    }

    return (
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 120}}
        showsVerticalScrollIndicator={false}
        refreshControl={<RefreshControl refreshing={isRefetching} onRefresh={refetch} tintColor={tokens.brandPrimary} />}
      >
        {groupedData.map((section) => {
          if (section.type === 'recipient') {
            const isExpanded = expandedRecipients[section.title] ?? true;
            return (
              <View key={section.title} style={{marginBottom: 8, borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle}}>
                {/* Recipient Header Row */}
                <Pressable
                  onPress={() => toggleRecipientExpanded(section.title)}
                  style={{
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    paddingHorizontal: 20,
                    paddingVertical: 12,
                    backgroundColor: tokens.surfaceSunken,
                  }}
                >
                  <View style={{flexDirection: 'row', alignItems: 'center', flex: 1}}>
                    <Icon name="user" size={14} color={tokens.textSecondary} style={{marginRight: 6}} />
                    <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800'}}>
                      {section.title}
                    </Text>
                  </View>

                  <View style={{flexDirection: 'row', alignItems: 'center'}}>
                    <Text
                      style={{
                        color: section.outstandingBalance > 0 ? tokens.dangerText : tokens.successText,
                        fontSize: 11,
                        fontWeight: '700',
                        marginRight: 8,
                        fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace',
                      }}
                    >
                      {section.outstandingBalance > 0
                        ? `${formatCurrency(section.outstandingBalance)} due`
                        : 'Settled'}
                    </Text>
                    <Icon name={isExpanded ? 'chevron-up' : 'chevron-down'} size={14} color={tokens.textTertiary} />
                  </View>
                </Pressable>

                {/* Recipient rows */}
                {isExpanded && (
                  <View style={{backgroundColor: tokens.surfaceCard}}>
                    {section.data.map((inv) => renderInvoiceRow(inv))}
                  </View>
                )}
              </View>
            );
          }

          // Status group headers
          const isCollapsed = section.type === 'paid' && !isPaidExpanded;
          let headerColor = tokens.textTertiary;
          let dotColor = tokens.textTertiary;

          if (section.type === 'overdue') { headerColor = '#EF4444'; dotColor = '#EF4444'; }
          else if (section.type === 'due_week') { headerColor = '#F59E0B'; dotColor = '#F59E0B'; }
          else if (section.type === 'paid') { headerColor = '#10B981'; dotColor = '#10B981'; }

          return (
            <View key={section.title} style={{marginBottom: 16}}>
              <Pressable
                disabled={section.type !== 'paid'}
                onPress={() => setIsPaidExpanded(!isPaidExpanded)}
                style={{
                  paddingHorizontal: 20,
                  paddingVertical: 8,
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  marginBottom: 6,
                }}
              >
                <View style={{flexDirection: 'row', alignItems: 'center'}}>
                  <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: dotColor, marginRight: 6}} />
                  <Text style={{color: headerColor, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.8}}>
                    {section.title} ({section.data.length})
                  </Text>
                </View>
                {section.type === 'paid' && (
                  <Icon name={isPaidExpanded ? 'chevron-up' : 'chevron-down'} size={14} color={tokens.textTertiary} />
                )}
              </Pressable>

              {!isCollapsed && (
                <View style={{borderRadius: 16, overflow: 'hidden', marginHorizontal: 16, borderWidth: 1, borderColor: tokens.borderDefault}}>
                  {section.data.map((inv) => renderInvoiceRow(inv))}
                </View>
              )}
            </View>
          );
        })}
      </ScrollView>
    );
  };

  // Explanatory empty states
  const renderEmptyState = () => {
    if (!isBillingEnabled) {
      return (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 80, paddingHorizontal: 32}}>
          <View style={{width: 72, height: 72, borderRadius: 36, backgroundColor: `${tokens.brandPrimary}1E`, alignItems: 'center', justifyContent: 'center', marginBottom: 16}}>
            <Icon name="alert-circle" size={32} color={tokens.brandPrimary} />
          </View>
          <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 6}}>
            Billing & Invoicing Not Enabled
          </Text>
          <Text style={{color: tokens.textTertiary, fontSize: 12, textAlign: 'center', lineHeight: 18, marginBottom: 24, maxWidth: 280}}>
            Enable VillaCRM billing to generate automated rental invoices, charge maintenance commissions, track client payments, and dispatch smart AI tenant reminders.
          </Text>
          <Pressable
            onPress={() => {
              Vibration.vibrate(10);
              setIsBillingEnabled(true);
              Alert.alert('Billing Enabled', 'VillaCRM billing has been successfully enabled.');
            }}
            style={{
              backgroundColor: tokens.brandPrimary,
              borderRadius: 12,
              paddingVertical: 12,
              paddingHorizontal: 24,
              ...tokens.shadowSm,
            }}
          >
            <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>
              Enable Billing & Lettings
            </Text>
          </Pressable>
        </View>
      );
    }

    return (
      <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 80, paddingHorizontal: 32}}>
        <View style={{width: 72, height: 72, borderRadius: 36, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center', marginBottom: 16}}>
          <Icon name="file-text" size={32} color={tokens.textTertiary} />
        </View>
        <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 6}}>
          No Invoices Generated
        </Text>
        <Text style={{color: tokens.textTertiary, fontSize: 12, textAlign: 'center', lineHeight: 17, marginBottom: 20}}>
          Your active invoices will appear here. Tap the "+" button above to generate a new one-off commission or fee invoice manually.
        </Text>
      </View>
    );
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* HEADER SECTION */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          paddingTop: 12,
          paddingBottom: 8,
          ...tokens.shadowSm,
        }}
      >
        {/* Top title bar */}
        <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, marginBottom: 12}}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
            <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '800', letterSpacing: -0.5}}>
              {t('finance.title')}
            </Text>
            <View style={{backgroundColor: `${tokens.brandPrimary}22`, borderRadius: 8, paddingHorizontal: 6, paddingVertical: 2}}>
              <Text style={{color: tokens.brandPrimary, fontSize: 11, fontWeight: '800'}}>
                {allInvoices.length}
              </Text>
            </View>
          </View>

          {/* Header Action Buttons */}
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 10}}>
            {/* Demo / Mock Settings wrench */}
            <Pressable
              onPress={() => { Vibration.vibrate(5); setMockSettingsVisible(true); }}
              style={{
                width: 36,
                height: 36,
                borderRadius: 18,
                backgroundColor: tokens.surfaceRaised,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                alignItems: 'center',
                justifyContent: 'center',
              }}
            >
              <Icon name="sliders" size={16} color={tokens.textSecondary} />
            </Pressable>

            {/* Toggle grouping mode */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setGroupMode(groupMode === 'status' ? 'recipient' : 'status');
              }}
              style={{
                paddingVertical: 8,
                paddingHorizontal: 12,
                borderRadius: 8,
                backgroundColor: tokens.surfaceRaised,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
              }}
            >
              <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '800'}}>
                {groupMode === 'status' ? 'Group: Status' : 'Group: Tenant'}
              </Text>
            </Pressable>

            {/* "+" button to create invoice */}
            <Pressable
              onPress={openCreateSheet}
              style={{
                width: 36,
                height: 36,
                borderRadius: 18,
                backgroundColor: tokens.brandPrimary,
                alignItems: 'center',
                justifyContent: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Icon name="plus" size={18} color="#ffffff" />
            </Pressable>
          </View>
        </View>

        {/* Search Input bar */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: tokens.surfaceRaised,
            borderRadius: 12,
            paddingHorizontal: 12,
            paddingVertical: Platform.OS === 'ios' ? 10 : 6,
            marginHorizontal: 20,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            marginBottom: 12,
          }}
        >
          <Icon name="search" size={14} color={tokens.textTertiary} style={{marginRight: 8}} />
          <TextInput
            value={search}
            onChangeText={setSearch}
            placeholder="Search recipient, property, or reference..."
            placeholderTextColor={tokens.textTertiary}
            style={{
              flex: 1,
              color: tokens.textPrimary,
              fontSize: 13,
              fontWeight: '600',
              padding: 0,
            }}
            clearButtonMode="while-editing"
          />
        </View>

        {/* SUMMARY STAT STRIP (horizontal scroll) */}
        {isBillingEnabled && (
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{paddingHorizontal: 20, gap: 10, paddingBottom: 10}}
          >
            {/* Stat Chip: Outstanding */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setFilterTab(filterTab === 'outstanding' ? 'all' : 'outstanding');
              }}
              style={{
                paddingHorizontal: 14,
                paddingVertical: 10,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: filterTab === 'outstanding' ? '#EF4444' : tokens.borderDefault,
                backgroundColor: filterTab === 'outstanding' ? '#EF444415' : tokens.surfaceRaised,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <View style={{marginRight: 6}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>OUTSTANDING</Text>
                <Text style={{color: stats.outstanding > 0 ? '#EF4444' : tokens.textPrimary, fontSize: 13, fontWeight: '800', marginTop: 2, fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace'}}>
                  {formatCurrencyAbbreviated(stats.outstanding)}
                </Text>
              </View>
            </Pressable>

            {/* Stat Chip: Due this week */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setFilterTab(filterTab === 'due_this_week' ? 'all' : 'due_this_week');
              }}
              style={{
                paddingHorizontal: 14,
                paddingVertical: 10,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: filterTab === 'due_this_week' ? '#F59E0B' : tokens.borderDefault,
                backgroundColor: filterTab === 'due_this_week' ? '#F59E0B15' : tokens.surfaceRaised,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <View style={{marginRight: 6}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>DUE THIS WEEK</Text>
                <Text style={{color: stats.dueThisWeek > 0 ? '#F59E0B' : tokens.textPrimary, fontSize: 13, fontWeight: '800', marginTop: 2, fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace'}}>
                  {formatCurrencyAbbreviated(stats.dueThisWeek)}
                </Text>
              </View>
            </Pressable>

            {/* Stat Chip: Paid this month */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setFilterTab(filterTab === 'paid_this_month' ? 'all' : 'paid_this_month');
              }}
              style={{
                paddingHorizontal: 14,
                paddingVertical: 10,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: filterTab === 'paid_this_month' ? '#10B981' : tokens.borderDefault,
                backgroundColor: filterTab === 'paid_this_month' ? '#10B98115' : tokens.surfaceRaised,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <View style={{marginRight: 6}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>PAID THIS MONTH</Text>
                <Text style={{color: '#10B981', fontSize: 13, fontWeight: '800', marginTop: 2, fontFamily: Platform.OS === 'ios' ? 'Courier' : 'monospace'}}>
                  {formatCurrencyAbbreviated(stats.paidThisMonth)}
                </Text>
              </View>
            </Pressable>

            {/* Stat Chip: Overdue Invoices Count */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setFilterTab(filterTab === 'overdue' ? 'all' : 'overdue');
              }}
              style={{
                paddingHorizontal: 14,
                paddingVertical: 10,
                borderRadius: 12,
                borderWidth: 1,
                borderColor: filterTab === 'overdue' ? '#EF4444' : tokens.borderDefault,
                backgroundColor: filterTab === 'overdue' ? '#EF444415' : tokens.surfaceRaised,
                flexDirection: 'row',
                alignItems: 'center',
              }}
            >
              <View style={{marginRight: 6}}>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>OVERDUE</Text>
                <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 2}}>
                  {stats.overdue > 0 && <PulsingDot />}
                  <Text style={{color: stats.overdue > 0 ? '#EF4444' : tokens.textPrimary, fontSize: 13, fontWeight: '800'}}>
                    {stats.overdue} Invoice{stats.overdue !== 1 ? 's' : ''}
                  </Text>
                </View>
              </View>
            </Pressable>
          </ScrollView>
        )}

        {/* FILTER CHIPS ROW */}
        {isBillingEnabled && (
          <ScrollView
            horizontal
            showsHorizontalScrollIndicator={false}
            contentContainerStyle={{paddingHorizontal: 20, gap: 8, marginTop: 4}}
          >
            {[
              {label: 'All', value: 'all' as const},
              {label: 'Outstanding', value: 'outstanding' as const},
              {
                label: overdueCountTotal > 0 ? `Overdue (${overdueCountTotal})` : 'Overdue',
                value: 'overdue' as const,
                isDanger: overdueCountTotal > 0,
              },
              {label: 'Paid', value: 'paid' as const},
              {label: 'Draft', value: 'draft' as const},
            ].map((chip) => {
              const isActive = filterTab === chip.value;
              let bgColor = tokens.surfaceRaised;
              let textColor = tokens.textSecondary;
              let borderColor = tokens.borderDefault;

              if (isActive) {
                bgColor = tokens.brandPrimary;
                textColor = '#FFFFFF';
                borderColor = tokens.brandPrimary;
              } else if (chip.isDanger) {
                bgColor = '#FFF1F2';
                textColor = '#EF4444';
                borderColor = '#FECDD3';
              }

              return (
                <Pressable
                  key={chip.value}
                  onPress={() => {
                    Vibration.vibrate(5);
                    setFilterTab(chip.value);
                  }}
                  style={{
                    paddingHorizontal: 12,
                    paddingVertical: 6,
                    borderRadius: 999,
                    backgroundColor: bgColor,
                    borderWidth: 1,
                    borderColor: borderColor,
                  }}
                >
                  <Text style={{fontSize: 11, fontWeight: '700', color: textColor}}>
                    {chip.label}
                  </Text>
                </Pressable>
              );
            })}
          </ScrollView>
        )}
      </View>

      {/* CORE LIST VIEW BODY */}
      <View style={{flex: 1, paddingTop: 10}}>
        {isLoading ? (
          <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
            <ActivityIndicator color={tokens.brandPrimary} />
          </View>
        ) : isBillingEnabled ? (
          renderListSections()
        ) : (
          renderEmptyState()
        )}
      </View>

      {/* OFFLINE / CACHE BANNER */}
      {isBillingEnabled && (
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'center',
            paddingVertical: 5,
            backgroundColor: tokens.surfaceSunken,
            borderTopWidth: 1,
            borderTopColor: tokens.borderDefault,
            position: 'absolute',
            bottom: isSelectionMode ? 80 : 0,
            left: 0,
            right: 0,
          }}
        >
          <Icon name="cloud-lightning" size={10} color={tokens.textTertiary} style={{marginRight: 4}} />
          <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '600'}}>
            Billing Ledger cached {syncTimeText}
          </Text>
        </View>
      )}

      {/* BULK ACTIONS STICKY BOTTOM BAR */}
      {isSelectionMode && (
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
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'space-between',
            ...tokens.shadowMd,
          }}
        >
          <View style={{flexDirection: 'row', alignItems: 'center'}}>
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setIsSelectionMode(false);
                setSelectedInvoiceIds({});
              }}
              style={{marginRight: 16, padding: 4}}
            >
              <Icon name="x" size={20} color={tokens.textSecondary} />
            </Pressable>
            <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800'}}>
              {selectedCount} Selected
            </Text>
          </View>

          <View style={{flexDirection: 'row', gap: 10}}>
            <Pressable
              onPress={handleOpenRemindersModal}
              disabled={selectedCount === 0}
              style={{
                backgroundColor: selectedCount === 0 ? tokens.borderStrong : tokens.brandPrimary,
                borderRadius: 10,
                paddingVertical: 10,
                paddingHorizontal: 16,
                flexDirection: 'row',
                alignItems: 'center',
                opacity: selectedCount === 0 ? 0.6 : 1,
              }}
            >
              <Icon name="send" size={12} color="#ffffff" style={{marginRight: 6}} />
              <Text style={{color: '#ffffff', fontSize: 12, fontWeight: '800'}}>
                Send Reminders
              </Text>
            </Pressable>
          </View>
        </View>
      )}

      {/* MODAL 1: CREATE ONE-OFF INVOICE SHEET */}
      <Modal
        visible={createSheetVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setCreateSheetVisible(false)}
      >
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: tokens.surfaceOverlay}}>
          <Pressable style={{flex: 1}} onPress={() => setCreateSheetVisible(false)} />
          <View
            style={{
              maxHeight: '85%',
              backgroundColor: tokens.surfaceCard,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              borderTopWidth: 1,
              borderTopColor: tokens.borderStrong,
              padding: 20,
              paddingBottom: insets.bottom > 0 ? insets.bottom + 20 : 30,
            }}
          >
            {/* Header */}
            <View style={{width: 40, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '900'}}>
                Create One-Off Invoice
              </Text>
              <Pressable
                onPress={() => setCreateSheetVisible(false)}
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

            <ScrollView showsVerticalScrollIndicator={false} style={{marginBottom: 20}}>
              {/* Type selector */}
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 8}}>
                Invoice Type
              </Text>
              <View style={{flexDirection: 'row', gap: 6, marginBottom: 16}}>
                {(['commission', 'maintenance', 'utility', 'other'] as const).map((tType) => {
                  const isSel = formType === tType;
                  return (
                    <Pressable
                      key={tType}
                      onPress={() => setFormType(tType)}
                      style={{
                        flex: 1,
                        paddingVertical: 8,
                        borderRadius: 8,
                        borderWidth: 1,
                        borderColor: isSel ? tokens.brandPrimary : tokens.borderDefault,
                        backgroundColor: isSel ? `${tokens.brandPrimary}1E` : tokens.surfaceRaised,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{color: isSel ? tokens.brandPrimary : tokens.textSecondary, fontSize: 10, fontWeight: '800', textTransform: 'capitalize'}}>
                        {tType}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>

              {/* Searchable Recipient Picker */}
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 8}}>
                Recipient
              </Text>
              <View style={{position: 'relative', marginBottom: 16}}>
                <TextInput
                  value={formRecipientSearch}
                  onChangeText={(val) => {
                    setFormRecipientSearch(val);
                    setFormRecipient(val); // fallback to text as typed
                  }}
                  placeholder="Search contacts or enter recipient name..."
                  placeholderTextColor={tokens.textTertiary}
                  style={{
                    backgroundColor: tokens.surfaceInput,
                    color: tokens.textPrimary,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 12,
                    paddingHorizontal: 14,
                    paddingVertical: 12,
                    fontSize: 13,
                    fontWeight: '600',
                  }}
                />

                {/* Contacts drop suggestion list */}
                {contactsData?.data && formRecipientSearch.trim().length > 0 && formRecipient !== formRecipientSearch && (
                  <View
                    style={{
                      maxHeight: 140,
                      borderWidth: 1,
                      borderColor: tokens.borderStrong,
                      borderRadius: 12,
                      backgroundColor: tokens.surfaceRaised,
                      marginTop: 4,
                      zIndex: 99,
                      overflow: 'hidden',
                    }}
                  >
                    <ScrollView nestedScrollEnabled keyboardShouldPersistTaps="handled">
                      {contactsData.data
                        .filter((c) =>
                          `${c.first_name} ${c.last_name}`
                            .toLowerCase()
                            .includes(formRecipientSearch.toLowerCase())
                        )
                        .map((c) => {
                          const name = `${c.first_name} ${c.last_name}`;
                          return (
                            <Pressable
                              key={c.id}
                              onPress={() => {
                                setFormRecipient(name);
                                setFormRecipientSearch(name);
                              }}
                              style={{
                                padding: 12,
                                borderBottomWidth: 1,
                                borderBottomColor: tokens.borderSubtle,
                                backgroundColor: tokens.surfaceCard,
                              }}
                            >
                              <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700'}}>{name}</Text>
                              <Text style={{color: tokens.textTertiary, fontSize: 10, marginTop: 2}}>{c.email || c.phone || 'No contact details'}</Text>
                            </Pressable>
                          );
                        })}
                    </ScrollView>
                  </View>
                )}
              </View>

              {/* Reference */}
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 8}}>
                Reference Number
              </Text>
              <TextInput
                value={formReference}
                onChangeText={setFormReference}
                placeholder="INV-XXXXXX"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceInput,
                  color: tokens.textPrimary,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 12,
                  paddingHorizontal: 14,
                  paddingVertical: 12,
                  fontSize: 13,
                  fontWeight: '600',
                  marginBottom: 16,
                }}
              />

              {/* Property Address */}
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 8}}>
                Property (Optional)
              </Text>
              <TextInput
                value={formProperty}
                onChangeText={setFormProperty}
                placeholder="e.g. 14A Bourdillon Road, Ikoyi"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceInput,
                  color: tokens.textPrimary,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 12,
                  paddingHorizontal: 14,
                  paddingVertical: 12,
                  fontSize: 13,
                  fontWeight: '600',
                  marginBottom: 16,
                }}
              />

              {/* Due Date */}
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', marginBottom: 8}}>
                Due Date
              </Text>
              <TextInput
                value={formDueDate}
                onChangeText={setFormDueDate}
                placeholder="YYYY-MM-DD"
                placeholderTextColor={tokens.textTertiary}
                style={{
                  backgroundColor: tokens.surfaceInput,
                  color: tokens.textPrimary,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 12,
                  paddingHorizontal: 14,
                  paddingVertical: 12,
                  fontSize: 13,
                  fontWeight: '600',
                  marginBottom: 16,
                }}
              />

              {/* Line Items Adder Section */}
              <View style={{borderTopWidth: 1, borderTopColor: tokens.borderDefault, paddingTop: 16, marginBottom: 16}}>
                <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800', marginBottom: 12}}>
                  Invoice Line Items
                </Text>

                {/* Current line items list */}
                {formLineItems.length === 0 ? (
                  <Text style={{color: tokens.textTertiary, fontSize: 12, fontStyle: 'italic', marginBottom: 12}}>
                    No line items added yet.
                  </Text>
                ) : (
                  <View style={{marginBottom: 12, gap: 8}}>
                    {formLineItems.map((item, index) => (
                      <View
                        key={index}
                        style={{
                          flexDirection: 'row',
                          alignItems: 'center',
                          justifyContent: 'space-between',
                          padding: 10,
                          borderRadius: 8,
                          backgroundColor: tokens.surfaceSunken,
                          borderWidth: 1,
                          borderColor: tokens.borderSubtle,
                        }}
                      >
                        <View style={{flex: 1, marginRight: 8}}>
                          <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700'}}>{item.description}</Text>
                        </View>
                        <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '800', marginRight: 12}}>
                          {formatCurrency(item.amount)}
                        </Text>
                        <Pressable onPress={() => handleRemoveLineItem(index)} style={{padding: 4}}>
                          <Icon name="trash-2" size={14} color="#EF4444" />
                        </Pressable>
                      </View>
                    ))}
                  </View>
                )}

                {/* Add new item fields */}
                <View style={{flexDirection: 'row', gap: 8}}>
                  <TextInput
                    value={tempItemDesc}
                    onChangeText={setTempItemDesc}
                    placeholder="Item description"
                    placeholderTextColor={tokens.textTertiary}
                    style={{
                      flex: 2,
                      backgroundColor: tokens.surfaceInput,
                      color: tokens.textPrimary,
                      borderWidth: 1,
                      borderColor: tokens.borderDefault,
                      borderRadius: 8,
                      paddingHorizontal: 10,
                      paddingVertical: 8,
                      fontSize: 12,
                      fontWeight: '600',
                    }}
                  />
                  <TextInput
                    value={tempItemAmount}
                    onChangeText={setTempItemAmount}
                    keyboardType="numeric"
                    placeholder="Amount (₦)"
                    placeholderTextColor={tokens.textTertiary}
                    style={{
                      flex: 1,
                      backgroundColor: tokens.surfaceInput,
                      color: tokens.textPrimary,
                      borderWidth: 1,
                      borderColor: tokens.borderDefault,
                      borderRadius: 8,
                      paddingHorizontal: 10,
                      paddingVertical: 8,
                      fontSize: 12,
                      fontWeight: '600',
                    }}
                  />
                  <Pressable
                    onPress={handleAddLineItem}
                    style={{
                      backgroundColor: tokens.brandPrimary,
                      borderRadius: 8,
                      paddingHorizontal: 12,
                      alignItems: 'center',
                      justifyContent: 'center',
                    }}
                  >
                    <Icon name="plus" size={16} color="#ffffff" />
                  </Pressable>
                </View>
              </View>
            </ScrollView>

            {/* Total display & submit buttons */}
            <View
              style={{
                borderTopWidth: 1,
                borderTopColor: tokens.borderDefault,
                paddingTop: 16,
                flexDirection: 'row',
                justifyContent: 'space-between',
                alignItems: 'center',
              }}
            >
              <View>
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>TOTAL INVOICE AMOUNT</Text>
                <Text style={{color: tokens.brandPrimary, fontSize: 18, fontWeight: '900', marginTop: 2}}>
                  {formatCurrency(formLineItems.reduce((sum, item) => sum + item.amount, 0))}
                </Text>
              </View>

              <View style={{flexDirection: 'row', gap: 10}}>
                <Pressable
                  onPress={() => setCreateSheetVisible(false)}
                  style={{
                    backgroundColor: tokens.surfaceRaised,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 12,
                    paddingVertical: 12,
                    paddingHorizontal: 16,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 13, fontWeight: '700'}}>Cancel</Text>
                </Pressable>
                <Pressable
                  onPress={handleSaveInvoice}
                  style={{
                    backgroundColor: tokens.brandPrimary,
                    borderRadius: 12,
                    paddingVertical: 12,
                    paddingHorizontal: 20,
                    ...tokens.shadowSm,
                  }}
                >
                  <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>Create Invoice</Text>
                </Pressable>
              </View>
            </View>
          </View>
        </View>
      </Modal>

      {/* MODAL 2: BULK AI REMINDERS PREVIEW */}
      <Modal
        visible={reminderModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setReminderModalVisible(false)}
      >
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: tokens.surfaceOverlay}}>
          <Pressable style={{flex: 1}} onPress={() => setReminderModalVisible(false)} />
          <View
            style={{
              maxHeight: '80%',
              backgroundColor: tokens.surfaceCard,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              borderTopWidth: 1,
              borderTopColor: tokens.borderStrong,
              padding: 20,
              paddingBottom: insets.bottom > 0 ? insets.bottom + 20 : 30,
            }}
          >
            {/* Header */}
            <View style={{width: 40, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 16}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
                <Icon name="zap" size={16} color={tokens.brandAccent} />
                <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '900'}}>
                  Review AI Reminders ({selectedCount})
                </Text>
              </View>
              <Pressable
                onPress={() => setReminderModalVisible(false)}
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

            <ScrollView showsVerticalScrollIndicator={false} style={{marginBottom: 20}}>
              <Text style={{color: tokens.textTertiary, fontSize: 12, marginBottom: 16, lineHeight: 18}}>
                The following AI-generated rent reminders have been prepared for the selected overdue invoices. Review and edit the drafts below before dispatching.
              </Text>

              {allInvoices
                .filter((inv) => selectedInvoiceIds[inv.id])
                .map((inv) => {
                  const recipient = getRecipientName(inv);
                  return (
                    <View
                      key={inv.id}
                      style={{
                        marginBottom: 16,
                        borderWidth: 1,
                        borderColor: tokens.borderDefault,
                        borderRadius: 16,
                        padding: 14,
                        backgroundColor: tokens.surfaceSunken,
                      }}
                    >
                      <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8}}>
                        <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '800'}}>
                          {recipient}
                        </Text>
                        <Text style={{color: tokens.dangerText, fontSize: 10, fontWeight: '700', fontFamily: 'monospace'}}>
                          {inv.reference}
                        </Text>
                      </View>

                      <TextInput
                        multiline
                        value={reminderDrafts[inv.id]}
                        onChangeText={(text) =>
                          setReminderDrafts((prev) => ({...prev, [inv.id]: text}))
                        }
                        style={{
                          backgroundColor: tokens.surfaceCard,
                          color: tokens.textPrimary,
                          borderWidth: 1,
                          borderColor: tokens.borderDefault,
                          borderRadius: 8,
                          padding: 10,
                          fontSize: 12,
                          lineHeight: 18,
                          minHeight: 100,
                          textAlignVertical: 'top',
                        }}
                      />
                    </View>
                  );
                })}
            </ScrollView>

            {/* Confirm send row */}
            <View
              style={{
                borderTopWidth: 1,
                borderTopColor: tokens.borderDefault,
                paddingTop: 16,
                flexDirection: 'row',
                justifyContent: 'flex-end',
                gap: 12,
              }}
            >
              <Pressable
                onPress={() => setReminderModalVisible(false)}
                style={{
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 12,
                  paddingVertical: 12,
                  paddingHorizontal: 20,
                }}
              >
                <Text style={{color: tokens.textSecondary, fontSize: 13, fontWeight: '700'}}>Cancel</Text>
              </Pressable>
              <Pressable
                onPress={handleSendReminders}
                disabled={isSendingReminders}
                style={{
                  backgroundColor: tokens.brandPrimary,
                  borderRadius: 12,
                  paddingVertical: 12,
                  paddingHorizontal: 24,
                  flexDirection: 'row',
                  alignItems: 'center',
                  ...tokens.shadowSm,
                }}
              >
                {isSendingReminders ? (
                  <ActivityIndicator color="#ffffff" size="small" />
                ) : (
                  <>
                    <Icon name="send" size={13} color="#ffffff" style={{marginRight: 6}} />
                    <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>Confirm & Send All</Text>
                  </>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>

      {/* MODAL 3: MOCK / DEMO STATE SETTINGS */}
      <Modal
        visible={mockSettingsVisible}
        transparent
        animationType="fade"
        onRequestClose={() => setMockSettingsVisible(false)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'center',
            alignItems: 'center',
            padding: 20,
          }}
          onPress={() => setMockSettingsVisible(false)}
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
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14}}>
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
                <Icon name="sliders" size={16} color={tokens.brandPrimary} />
                <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '800'}}>
                  Demo States Controller
                </Text>
              </View>
              <Pressable
                onPress={() => setMockSettingsVisible(false)}
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

            <Text style={{color: tokens.textSecondary, fontSize: 12, marginBottom: 16}}>
              Use these options to simulate different screen states for reviews, testing, or demonstrations.
            </Text>

            <View style={{gap: 12, marginBottom: 20}}>
              {/* Toggle Billing Enabled */}
              <Pressable
                onPress={() => {
                  Vibration.vibrate(5);
                  setIsBillingEnabled(!isBillingEnabled);
                }}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: 12,
                  borderRadius: 12,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  backgroundColor: tokens.surfaceRaised,
                }}
              >
                <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                  Billing & Lettings Module
                </Text>
                <View
                  style={{
                    backgroundColor: isBillingEnabled ? tokens.successBg : tokens.dangerBg,
                    paddingHorizontal: 8,
                    paddingVertical: 4,
                    borderRadius: 6,
                  }}
                >
                  <Text style={{color: isBillingEnabled ? tokens.successText : tokens.dangerText, fontSize: 10, fontWeight: '800'}}>
                    {isBillingEnabled ? 'ENABLED' : 'DISABLED'}
                  </Text>
                </View>
              </Pressable>

              {/* Toggle Simulate Empty List */}
              <Pressable
                onPress={() => {
                  Vibration.vibrate(5);
                  setSimulateEmptyList(!simulateEmptyList);
                }}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  padding: 12,
                  borderRadius: 12,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  backgroundColor: tokens.surfaceRaised,
                }}
              >
                <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                  Simulate Empty Invoices List
                </Text>
                <View
                  style={{
                    backgroundColor: simulateEmptyList ? tokens.brandAccent + '1E' : tokens.surfaceSunken,
                    paddingHorizontal: 8,
                    paddingVertical: 4,
                    borderRadius: 6,
                  }}
                >
                  <Text style={{color: simulateEmptyList ? tokens.brandAccent : tokens.textTertiary, fontSize: 10, fontWeight: '800'}}>
                    {simulateEmptyList ? 'EMPTY ACTIVE' : 'NORMAL DATA'}
                  </Text>
                </View>
              </Pressable>

              {/* Reset Local Created Invoices */}
              <Pressable
                onPress={() => {
                  Vibration.vibrate(10);
                  Alert.alert('Reset Invoices', 'Are you sure you want to delete all manually created invoices from the local storage cache?', [
                    {text: 'Cancel', style: 'cancel'},
                    {
                      text: 'Reset',
                      style: 'destructive',
                      onPress: () => {
                        setLocalInvoices([]);
                        localStore.delete('invoices');
                        setMockSettingsVisible(false);
                        Alert.alert('Reset Successful', 'Manually created invoices cleared.');
                      },
                    },
                  ]);
                }}
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'center',
                  padding: 12,
                  borderRadius: 12,
                  backgroundColor: '#EF444415',
                  borderWidth: 1,
                  borderColor: '#EF444433',
                }}
              >
                <Icon name="trash-2" size={14} color="#EF4444" style={{marginRight: 6}} />
                <Text style={{color: '#EF4444', fontSize: 13, fontWeight: '700'}}>
                  Reset Manually Created Invoices
                </Text>
              </Pressable>
            </View>

            <Pressable
              onPress={() => setMockSettingsVisible(false)}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 12,
                paddingVertical: 12,
                alignItems: 'center',
              }}
            >
              <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>Close</Text>
            </Pressable>
          </Pressable>
        </Pressable>
      </Modal>
    </SafeAreaView>
  );
}
