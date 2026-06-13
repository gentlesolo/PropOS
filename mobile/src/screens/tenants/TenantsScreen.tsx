import React, {useState, useRef, useEffect} from 'react';
import {
  ActivityIndicator,
  Animated,
  Alert,
  FlatList,
  Modal,
  PanResponder,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
  Vibration,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {tenantsApi, TenantListItem} from '../../api/tenants';
import type {TenantsStackParamList} from '../../navigation/stacks/TenantsStack';
import {useTheme} from '../../theme/ThemeProvider';
import {useTranslation} from '../../i18n';

type NavProp = NativeStackNavigationProp<TenantsStackParamList>;

// Pulsing dot for overdue payments
function PulsingDot() {
  const opacity = useRef(new Animated.Value(1)).current;

  useEffect(() => {
    Animated.loop(
      Animated.sequence([
        Animated.timing(opacity, {toValue: 0.2, duration: 800, useNativeDriver: true}),
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

// Custom dual-direction swipe row
interface TenantSwipeableRowProps {
  children: React.ReactNode;
  onCall: () => void;
  onSendReminder: () => void;
  tokens: any;
}

function TenantSwipeableRow({children, onCall, onSendReminder, tokens}: TenantSwipeableRowProps) {
  const translateX = useRef(new Animated.Value(0)).current;
  const currentTranslation = useRef(0);
  const isOpenLeft = useRef(false);
  const isOpenRight = useRef(false);

  const snap = (toValue: number) => {
    Animated.spring(translateX, {
      toValue,
      useNativeDriver: true,
      bounciness: 4,
      speed: 12,
    }).start(() => {
      currentTranslation.current = toValue;
      isOpenLeft.current = toValue > 0;
      isOpenRight.current = toValue < 0;
    });
  };

  const panResponder = useRef(
    PanResponder.create({
      onStartShouldSetPanResponder: () => false,
      onMoveShouldSetPanResponder: (_, gestureState) => {
        const {dx, dy} = gestureState;
        return Math.abs(dx) > Math.abs(dy) && Math.abs(dx) > 10;
      },
      onPanResponderMove: (_, gestureState) => {
        let newX = currentTranslation.current + gestureState.dx;
        // Limit swipe range to -70 (reminder) and 70 (call)
        if (newX > 75) newX = 75;
        if (newX < -75) newX = -75;
        translateX.setValue(newX);
      },
      onPanResponderRelease: (_, gestureState) => {
        const dx = gestureState.dx;
        if (dx > 35) {
          // Swipe right reveals left action (Call)
          snap(70);
        } else if (dx < -35) {
          // Swipe left reveals right action (Send Reminder)
          snap(-70);
        } else {
          snap(0);
        }
      },
      onPanResponderTerminate: () => snap(0),
    })
  ).current;

  return (
    <View
      style={{
        position: 'relative',
        overflow: 'hidden',
        marginBottom: 10,
        marginHorizontal: 16,
        borderRadius: 16,
        backgroundColor: tokens.surfaceCard,
        borderWidth: 1,
        borderColor: tokens.borderDefault,
        ...tokens.shadowSm,
      }}
    >
      {/* Left Action (Call) - Revealed when swiping right */}
      <View style={{position: 'absolute', left: 0, top: 0, bottom: 0, width: 70, zIndex: 0}}>
        <Pressable
          onPress={() => {
            snap(0);
            onCall();
          }}
          style={{
            flex: 1,
            backgroundColor: '#10B981', // Emerald
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Icon name="phone" size={18} color="#ffffff" />
        </Pressable>
      </View>

      {/* Right Action (Reminder) - Revealed when swiping left */}
      <View style={{position: 'absolute', right: 0, top: 0, bottom: 0, width: 70, zIndex: 0}}>
        <Pressable
          onPress={() => {
            snap(0);
            onSendReminder();
          }}
          style={{
            flex: 1,
            backgroundColor: '#F59E0B', // Amber
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Icon name="bell" size={18} color="#ffffff" />
        </Pressable>
      </View>

      {/* Foreground Content */}
      <Animated.View
        style={{
          transform: [{translateX}],
          width: '100%',
          backgroundColor: tokens.surfaceCard,
        }}
        {...panResponder.panHandlers}
      >
        {children}
      </Animated.View>
    </View>
  );
}

// Single Tenant Row Item
function TenantRow({
  tenant,
  onPress,
  onCall,
  onSendReminder,
  tokens,
}: {
  tenant: TenantListItem;
  onPress: () => void;
  onCall: () => void;
  onSendReminder: () => void;
  tokens: any;
}) {
  const initials = (tenant.full_name ?? 'T')
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();

  // Rent status visual styles
  const renderRentStatus = () => {
    if (tenant.rent_status === 'paid') {
      return (
        <View style={{flexDirection: 'row', alignItems: 'center'}}>
          <Icon name="check-circle" size={12} color="#10B981" style={{marginRight: 4}} />
          <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Paid</Text>
        </View>
      );
    }
    if (tenant.rent_status === 'due') {
      return (
        <Text style={{color: '#F59E0B', fontSize: 11, fontWeight: '800', fontFamily: 'monospace'}}>
          Due in {tenant.rent_due_days}d
        </Text>
      );
    }
    if (tenant.rent_status === 'overdue') {
      return (
        <View style={{flexDirection: 'row', alignItems: 'center'}}>
          <PulsingDot />
          <Text style={{color: '#EF4444', fontSize: 11, fontWeight: '800', fontFamily: 'monospace'}}>
            Overdue — {tenant.rent_due_days}d
          </Text>
        </View>
      );
    }
    return null;
  };

  // Format lease end date description if expiring within 60 days
  const formatLeaseEnd = () => {
    if (tenant.lease_ends_soon && tenant.lease_end_date) {
      // e.g. "2026-08-14" to "14 Aug"
      const dateParts = tenant.lease_end_date.split('-');
      if (dateParts.length === 3) {
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = parseInt(dateParts[2], 10);
        const month = monthNames[parseInt(dateParts[1], 10) - 1];
        return `Lease ends ${day} ${month}`;
      }
    }
    return null;
  };

  const leaseExpText = formatLeaseEnd();

  return (
    <TenantSwipeableRow onCall={onCall} onSendReminder={onSendReminder} tokens={tokens}>
      <Pressable
        onPress={onPress}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          padding: 16,
          backgroundColor: tokens.surfaceCard,
        }}
      >
        {/* Avatar with building overlay */}
        <View style={{position: 'relative', marginRight: 12}}>
          <View
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: `${tokens.brandPrimary}1E`,
              alignItems: 'center',
              justifyContent: 'center',
              borderWidth: 1,
              borderColor: tokens.borderDefault,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 13}}>
              {initials}
            </Text>
          </View>
          {/* Small building indicator badge */}
          <View
            style={{
              position: 'absolute',
              bottom: -2,
              right: -2,
              width: 14,
              height: 14,
              borderRadius: 7,
              backgroundColor: tokens.brandPrimary,
              alignItems: 'center',
              justifyContent: 'center',
              borderWidth: 1,
              borderColor: tokens.surfaceCard,
            }}
          >
            <Icon name="home" size={8} color="#ffffff" />
          </View>
        </View>

        {/* Text Details */}
        <View style={{flex: 1, marginRight: 8}}>
          <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700'}}>
            {tenant.full_name}
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '600', marginTop: 2}} numberOfLines={1}>
            {tenant.property}
          </Text>
        </View>

        {/* Rent status block */}
        <View style={{alignItems: 'flex-end', justifyContent: 'center'}}>
          {renderRentStatus()}
          {leaseExpText && (
            <Text style={{color: '#F59E0B', fontSize: 10, fontWeight: '700', marginTop: 4}}>
              {leaseExpText}
            </Text>
          )}
        </View>
      </Pressable>
    </TenantSwipeableRow>
  );
}

export function TenantsScreen() {
  const {t} = useTranslation();
  const {tokens} = useTheme();
  const navigation = useNavigation<NavProp>();

  // State
  const [search, setSearch] = useState('');
  const [filter, setFilter] = useState<'all' | 'due' | 'overdue' | 'expiring' | 'active'>('all');
  const [groupByStatus, setGroupByStatus] = useState(true);
  const [expandedPaidSection, setExpandedPaidSection] = useState(false);

  // AI Reminder Modal state
  const [activeTenantReminder, setActiveTenantReminder] = useState<TenantListItem | null>(null);
  const [reminderDraft, setReminderDraft] = useState('');
  const [isSendingReminder, setIsSendingReminder] = useState(false);

  // Sync / staleness timestamp
  const [syncTimeText] = useState('as of 12:05 PM');

  // Query API
  const {data, isLoading, refetch, isRefetching} = useQuery({
    queryKey: ['tenants', filter, search],
    queryFn: () =>
      tenantsApi.list({
        status: filter === 'all' ? undefined : filter,
        search: search || undefined,
      }),
  });

  const tenants = data?.data?.data ?? [];

  // Filter chips count totals
  const overdueCount = tenants.filter((t) => t.rent_status === 'overdue').length;

  const filterChips = [
    {label: 'All', value: 'all' as const},
    {label: 'Rent Due', value: 'due' as const},
    {
      label: overdueCount > 0 ? `Overdue (${overdueCount})` : 'Overdue',
      value: 'overdue' as const,
      isDanger: overdueCount > 0,
    },
    {label: 'Lease Expiring Soon', value: 'expiring' as const, isWarning: true},
    {label: 'Active', value: 'active' as const},
  ];

  // Quick action calls
  const triggerCall = (tenant: TenantListItem) => {
    Vibration.vibrate(10);
    Alert.alert(
      'Calling Tenant',
      `Dialing ${tenant.full_name} at +27 82 123 4567...`,
      [{text: 'End Call', style: 'cancel'}]
    );
  };

  // Draft AI Reminder message
  const triggerReminderDraft = (tenant: TenantListItem) => {
    Vibration.vibrate(10);
    const draftText = `Hi ${tenant.full_name},\n\nThis is a friendly reminder that the rent of R${tenant.monthly_rent?.toLocaleString()} for ${tenant.property} is currently overdue by ${tenant.rent_due_days} days. Please let us know if payment has been made.\n\nWarm regards,\nVillaCRM Lettings`;
    setReminderDraft(draftText);
    setActiveTenantReminder(tenant);
  };

  const sendReminder = () => {
    setIsSendingReminder(true);
    setTimeout(() => {
      setIsSendingReminder(false);
      setActiveTenantReminder(null);
      Alert.alert('Success', 'Rent reminder notification sent to tenant.');
    }, 800);
  };

  // Grouping items logic
  const renderList = () => {
    if (!groupByStatus) {
      // Plain Flat list, sorted A-Z
      const sorted = [...tenants].sort((a, b) =>
        (a.full_name ?? '').localeCompare(b.full_name ?? '')
      );
      return (
        <FlatList
          data={sorted}
          keyExtractor={(item) => String(item.id)}
          renderItem={({item}) => (
            <TenantRow
              tenant={item}
              onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
              onCall={() => triggerCall(item)}
              onSendReminder={() => triggerReminderDraft(item)}
              tokens={tokens}
            />
          )}
          onRefresh={refetch}
          refreshing={isRefetching}
          ListEmptyComponent={renderEmptyState()}
        />
      );
    }

    // Grouped List logic
    const overdue = tenants.filter((t) => t.rent_status === 'overdue');
    const dueThisWeek = tenants.filter((t) => t.rent_status === 'due');
    const paid = tenants.filter((t) => t.rent_status === 'paid');
    const other = tenants.filter(
      (t) => t.rent_status !== 'paid' && t.rent_status !== 'due' && t.rent_status !== 'overdue'
    );

    return (
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 40}}
        showsVerticalScrollIndicator={false}
      >
        {/* Overdue Section */}
        {overdue.length > 0 && (
          <View style={{marginBottom: 16}}>
            <View style={{paddingHorizontal: 20, paddingVertical: 6, flexDirection: 'row', alignItems: 'center', marginBottom: 8}}>
              <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: '#EF4444', marginRight: 6}} />
              <Text style={{color: '#EF4444', fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5}}>
                Overdue ({overdue.length})
              </Text>
            </View>
            {overdue.map((item) => (
              <TenantRow
                key={item.id}
                tenant={item}
                onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
                onCall={() => triggerCall(item)}
                onSendReminder={() => triggerReminderDraft(item)}
                tokens={tokens}
              />
            ))}
          </View>
        )}

        {/* Due This Week Section */}
        {dueThisWeek.length > 0 && (
          <View style={{marginBottom: 16}}>
            <View style={{paddingHorizontal: 20, paddingVertical: 6, flexDirection: 'row', alignItems: 'center', marginBottom: 8}}>
              <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: '#F59E0B', marginRight: 6}} />
              <Text style={{color: '#F59E0B', fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5}}>
                Due Soon ({dueThisWeek.length})
              </Text>
            </View>
            {dueThisWeek.map((item) => (
              <TenantRow
                key={item.id}
                tenant={item}
                onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
                onCall={() => triggerCall(item)}
                onSendReminder={() => triggerReminderDraft(item)}
                tokens={tokens}
              />
            ))}
          </View>
        )}

        {/* Paid Section (Collapsed by default) */}
        {paid.length > 0 && (
          <View style={{marginBottom: 16}}>
            <Pressable
              onPress={() => {
                Vibration.vibrate(5);
                setExpandedPaidSection(!expandedPaidSection);
              }}
              style={{
                paddingHorizontal: 20,
                paddingVertical: 10,
                flexDirection: 'row',
                alignItems: 'center',
                justifyContent: 'space-between',
              }}
            >
              <View style={{flexDirection: 'row', alignItems: 'center'}}>
                <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: '#10B981', marginRight: 6}} />
                <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5}}>
                  Paid ({paid.length})
                </Text>
              </View>
              <Icon
                name={expandedPaidSection ? 'chevron-up' : 'chevron-down'}
                size={14}
                color={tokens.textTertiary}
              />
            </Pressable>
            {expandedPaidSection &&
              paid.map((item) => (
                <TenantRow
                  key={item.id}
                  tenant={item}
                  onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
                  onCall={() => triggerCall(item)}
                  onSendReminder={() => triggerReminderDraft(item)}
                  tokens={tokens}
                />
              ))}
          </View>
        )}

        {/* Other Section */}
        {other.length > 0 && (
          <View style={{marginBottom: 16}}>
            <View style={{paddingHorizontal: 20, paddingVertical: 6, flexDirection: 'row', alignItems: 'center', marginBottom: 8}}>
              <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 0.5}}>
                Other ({other.length})
              </Text>
            </View>
            {other.map((item) => (
              <TenantRow
                key={item.id}
                tenant={item}
                onPress={() => navigation.navigate('TenantDetail', {tenantId: item.id})}
                onCall={() => triggerCall(item)}
                onSendReminder={() => triggerReminderDraft(item)}
                tokens={tokens}
              />
            ))}
          </View>
        )}

        {tenants.length === 0 && renderEmptyState()}
      </ScrollView>
    );
  };

  // Explanatory empty states
  const renderEmptyState = () => (
    <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 60, paddingHorizontal: 32}}>
      <View
        style={{
          width: 72,
          height: 72,
          borderRadius: 36,
          backgroundColor: tokens.surfaceRaised,
          alignItems: 'center',
          justifyContent: 'center',
          marginBottom: 16,
        }}
      >
        <Icon name="key" size={32} color={tokens.textTertiary} />
      </View>
      <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 6}}>
        No tenants yet
      </Text>
      <Text style={{color: tokens.textTertiary, fontSize: 12, textAlign: 'center', lineHeight: 17, marginBottom: 20}}>
        Tenants are contacts with active leases. Use this to track monthly rent payments and lease expirations, distinct from regular leads.
      </Text>

      <Pressable
        onPress={() => Alert.alert('Action', 'Add Tenant Wizard triggered.')}
        style={{
          backgroundColor: tokens.brandPrimary,
          borderRadius: 12,
          paddingVertical: 12,
          paddingHorizontal: 20,
          ...tokens.shadowSm,
        }}
      >
        <Text style={{color: '#ffffff', fontSize: 13, fontWeight: '800'}}>
          Add your first tenant
        </Text>
      </Pressable>
    </View>
  );

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* HEADER SECTION */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          paddingVertical: 12,
          ...tokens.shadowSm,
        }}
      >
        <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, marginBottom: 12}}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
            <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', letterSpacing: -0.5}}>
              Tenants
            </Text>
            <View style={{backgroundColor: `${tokens.brandPrimary}22`, borderRadius: 8, paddingHorizontal: 6, paddingVertical: 2}}>
              <Text style={{color: tokens.brandPrimary, fontSize: 11, fontWeight: '800'}}>
                {tenants.length}
              </Text>
            </View>
          </View>

          {/* Grouping toggler */}
          <Pressable
            onPress={() => {
              Vibration.vibrate(5);
              setGroupByStatus(!groupByStatus);
            }}
            style={{
              paddingVertical: 6,
              paddingHorizontal: 12,
              borderRadius: 8,
              backgroundColor: tokens.surfaceRaised,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
            }}
          >
            <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '800'}}>
              {groupByStatus ? 'Group: Status' : 'Sort: A-Z'}
            </Text>
          </Pressable>
        </View>

        {/* Search Input bar */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: tokens.surfaceRaised,
            borderRadius: 12,
            paddingHorizontal: 12,
            paddingVertical: 8,
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
            placeholder="Search tenant name or property..."
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

        {/* Horizontal filter chips list */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, gap: 8}}
        >
          {filterChips.map((chip) => {
            const isActive = filter === chip.value;
            let bgColor = tokens.surfaceRaised;
            let textColor = tokens.textSecondary;
            let borderColor = tokens.borderDefault;

            if (isActive) {
              bgColor = tokens.brandPrimary;
              textColor = '#FFFFFF';
              borderColor = tokens.brandPrimary;
            } else if (chip.isDanger) {
              bgColor = '#FEE2E2'; // Red-100
              textColor = '#EF4444'; // Red-500
              borderColor = '#FCA5A5'; // Red-300
            } else if (chip.isWarning) {
              bgColor = '#FEF3C7'; // Amber-100
              textColor = '#D97706'; // Amber-600
              borderColor = '#FCD34D'; // Amber-300
            }

            return (
              <Pressable
                key={chip.value}
                onPress={() => {
                  Vibration.vibrate(5);
                  setFilter(chip.value);
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
      </View>

      {/* OFFLINE / SYNC DATA NOTE */}
      <View
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          justifyContent: 'center',
          paddingVertical: 4,
          backgroundColor: tokens.surfaceSunken,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
        }}
      >
        <Icon name="cloud-lightning" size={10} color={tokens.textTertiary} style={{marginRight: 4}} />
        <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '600'}}>
          Rent status cached {syncTimeText}
        </Text>
      </View>

      {/* CORE LIST BODY */}
      <View style={{flex: 1, paddingTop: 12}}>
        {isLoading ? (
          <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
            <ActivityIndicator color={tokens.brandPrimary} />
          </View>
        ) : (
          renderList()
        )}
      </View>

      {/* AI RENT REMINDER MODAL */}
      <Modal
        visible={activeTenantReminder !== null}
        transparent
        animationType="slide"
        onRequestClose={() => setActiveTenantReminder(null)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'center',
            alignItems: 'center',
            padding: 20,
          }}
          onPress={() => setActiveTenantReminder(null)}
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
            {/* Header */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14}}>
              <View style={{flexDirection: 'row', alignItems: 'center', gap: 6}}>
                <Text style={{color: '#F59E0B', fontSize: 14}}>✦</Text>
                <Text style={{color: tokens.textPrimary, fontSize: 15, fontWeight: '800'}}>
                  AI Rent Reminder
                </Text>
              </View>
              <Pressable
                onPress={() => setActiveTenantReminder(null)}
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
              DRAFT MESSAGE FOR {activeTenantReminder?.full_name?.toUpperCase()}
            </Text>

            {/* Message input text field */}
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
                value={reminderDraft}
                onChangeText={setReminderDraft}
                style={{
                  color: tokens.textPrimary,
                  fontSize: 13,
                  lineHeight: 18,
                  textAlignVertical: 'top',
                  padding: 0,
                }}
              />
            </View>

            {/* CTAs */}
            <View style={{flexDirection: 'row', gap: 10}}>
              <Pressable
                onPress={() => setActiveTenantReminder(null)}
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
                onPress={sendReminder}
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
