import React, {useState} from 'react';
import {Alert, Pressable, ScrollView, Text, View} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {useAuthStore} from '../../store/authStore';
import {useNotificationStore} from '../../store/notificationStore';
import {useQuery} from '@tanstack/react-query';
import {callsApi} from '../../api/calls';
import {viewingsApi} from '../../api/viewings';
import {isToday} from 'date-fns';
import {useTheme} from '../../theme/ThemeProvider';
import {createMMKV} from 'react-native-mmkv';

// Safe access to MMKV storage to respect configured module flags and currency
let localStore: any;
try {
  localStore = createMMKV({id: 'invoices-local-store-v1'});
} catch (e) {
  const store: Record<string, string> = {};
  localStore = {
    getString: (key: string) => store[key] || null,
    set: (key: string, val: string) => { store[key] = val; },
  };
}

export function MoreScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const {user, clearAuth} = useAuthStore();

  const isManager = (user as any)?.roles?.some?.(
    (r: string) => r === 'admin' || r === 'manager' || r === 'principal' || r === 'super_admin' || r === 'branch_manager',
  ) ?? false;

  const {unreadCount: notificationsUnread} = useNotificationStore();

  const {data: pendingCalls} = useQuery({
    queryKey: ['calls', 'pending-review'],
    queryFn: () =>
      callsApi
        .list({direction: 'outbound'})
        .then((r) =>
          r.data.data.filter(
            (c) =>
              c.status === 'completed' &&
              c.summary &&
              !c.summary.agent_confirmed_at &&
              c.started_at &&
              isToday(new Date(c.started_at))
          )
        ),
  });

  const {data: todayViewings} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then((r) => r.data),
  });

  const pendingCallsCount = pendingCalls?.length ?? 0;
  const viewingsCount = todayViewings?.length ?? 0;

  const [billingEnabled, setBillingEnabled] = useState(() => {
    return localStore.getString('isBillingEnabled') !== 'false';
  });

  const [selectedCurrency, setSelectedCurrency] = useState(() => {
    return localStore.getString('currency_symbol') || '₦';
  });

  const toggleBilling = () => {
    const newVal = !billingEnabled;
    localStore.set('isBillingEnabled', newVal ? 'true' : 'false');
    setBillingEnabled(newVal);
    Alert.alert('Billing/Lettings Module', `Successfully ${newVal ? 'Enabled' : 'Disabled'} at navigation level.`);
  };

  const selectCurrency = (sym: string) => {
    localStore.set('currency_symbol', sym);
    setSelectedCurrency(sym);
    Alert.alert('Currency Configured', `Global currency symbol set to: ${sym}`);
  };

  const menuItems = [
    {
      id: 'viewings',
      title: 'Viewings',
      subtitle: 'Schedule, itineraries, and geofenced showing sheets',
      icon: 'calendar',
      route: 'Viewings',
      badge: viewingsCount > 0 ? `${viewingsCount} today` : null,
      badgeBg: `${tokens.brandPrimary}1A`,
      badgeBorder: `${tokens.brandPrimary}33`,
      badgeText: tokens.brandPrimary,
    },
    {
      id: 'calls',
      title: 'Call History',
      subtitle: 'Call transcriptions, audio playback, and AI summaries',
      icon: 'phone-call',
      route: 'Calls',
      badge: pendingCallsCount > 0 ? `${pendingCallsCount} pending` : null,
      badgeBg: '#F59E0B1A',
      badgeBorder: '#F59E0B33',
      badgeText: '#F59E0B',
    },
    {
      id: 'notifications',
      title: 'Notification History',
      subtitle: 'Read/unread notifications, alerts, and audit logs',
      icon: 'bell',
      route: 'Notifications',
      badge: notificationsUnread > 0 ? `${notificationsUnread} new` : null,
      badgeBg: `${tokens.brandPrimary}1A`,
      badgeBorder: `${tokens.brandPrimary}33`,
      badgeText: tokens.brandPrimary,
    },
    ...(billingEnabled
      ? [
          {
            id: 'tenants',
            title: 'Tenants & Leases',
            subtitle: 'Active leases, rent schedules, and tenant profiles',
            icon: 'key',
            route: 'Tenants',
            badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
          },
          {
            id: 'finance',
            title: 'Finance & Invoices',
            subtitle: 'Rent collections, expense tracking, and invoicing',
            icon: 'dollar-sign',
            route: 'Finance',
            badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
          },
        ]
      : []),
    ...(isManager
      ? [
          {
            id: 'manager_dashboard',
            title: 'Manager Dashboard',
            subtitle: 'AI financial tracking, agency health, and active leases',
            icon: 'trending-up',
            route: 'Intelligence',
            params: {screen: 'ManagerDashboard'},
            badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
          },
          {
            id: 'team_benchmark',
            title: 'Team Benchmark',
            subtitle: 'Non-punitive performance comparison and agent insights',
            icon: 'users',
            route: 'Intelligence',
            params: {screen: 'Benchmark'},
            badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
          },
          {
            id: 'call_analytics',
            title: 'Call Analytics',
            subtitle: 'AI outbound call reports, sentiment mapping, and transcripts',
            icon: 'bar-chart-2',
            route: 'Intelligence',
            params: {screen: 'Analytics'},
            badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
          },
        ]
      : []),
    {
      id: 'phone_numbers',
      title: 'Phone Numbers',
      subtitle: 'Register your own number or provision a Twilio line for calling',
      icon: 'phone',
      route: 'PhoneNumbers',
      badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
    },
    {
      id: 'profile',
      title: 'Profile Settings',
      subtitle: 'Account details, notifications preferences, and device auth',
      icon: 'user',
      route: 'Profile',
      badge: null, badgeBg: '', badgeBorder: '', badgeText: '',
    },
  ];

  const handleLogout = () => {
    try { clearAuth(); } catch (err) { console.warn('Logout error', err); }
  };

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 20,
          paddingTop: 16,
          paddingBottom: 16,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          flexDirection: 'row',
          justifyContent: 'space-between',
          alignItems: 'center',
          zIndex: 10,
        }}
      >
        <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '900', letterSpacing: -0.5}}>More</Text>
        <Pressable
          onPress={handleLogout}
          style={{backgroundColor: '#F43F5E1A', borderWidth: 1, borderColor: '#F43F5E33', paddingHorizontal: 14, paddingVertical: 6, borderRadius: 999}}
        >
          <Text style={{color: '#F43F5E', fontWeight: '800', fontSize: 12}}>Sign Out</Text>
        </Pressable>
      </View>

      <ScrollView style={{flex: 1, paddingHorizontal: 16, paddingTop: 16}} showsVerticalScrollIndicator={false}>
        {/* User Card */}
        <View
          style={{
            padding: 16,
            borderRadius: 16,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            backgroundColor: tokens.surfaceCard,
            marginBottom: 16,
            flexDirection: 'row',
            alignItems: 'center',
          }}
        >
          <View
            style={{
              width: 48,
              height: 48,
              borderRadius: 24,
              backgroundColor: `${tokens.brandPrimary}1A`,
              borderWidth: 1,
              borderColor: `${tokens.brandPrimary}33`,
              alignItems: 'center',
              justifyContent: 'center',
              marginRight: 16,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '900', fontSize: 18}}>
              {(user?.first_name?.[0] || 'T').toUpperCase()}
            </Text>
          </View>
          <View style={{flex: 1}}>
            <Text style={{color: tokens.textPrimary, fontWeight: '800', fontSize: 16}}>
              {user?.first_name} {user?.last_name}
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '600', marginTop: 2}}>
              {user?.email}
            </Text>
            {isManager && (
              <View
                style={{
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  borderWidth: 1,
                  borderColor: `${tokens.brandPrimary}33`,
                  alignSelf: 'flex-start',
                  paddingHorizontal: 8,
                  paddingVertical: 2,
                  borderRadius: 6,
                  marginTop: 6,
                }}
              >
                <Text style={{color: tokens.brandPrimary, fontSize: 9, fontWeight: '800', textTransform: 'uppercase'}}>Agency Manager</Text>
              </View>
            )}
          </View>
        </View>

        {/* Menu Items */}
        <View style={{marginBottom: 40}}>
          {menuItems.map((item) => (
            <Pressable
              key={item.id}
              onPress={() => navigation.navigate(item.route, (item as any).params)}
              style={({pressed}) => ({marginBottom: 12, opacity: pressed ? 0.85 : 1})}
            >
              <View
                style={{
                  flexDirection: 'row',
                  alignItems: 'center',
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  backgroundColor: tokens.surfaceCard,
                  borderRadius: 16,
                  padding: 16,
                }}
              >
                {/* Icon */}
                <View
                  style={{
                    width: 40,
                    height: 40,
                    borderRadius: 20,
                    backgroundColor: `${tokens.brandPrimary}1A`,
                    alignItems: 'center',
                    justifyContent: 'center',
                    marginRight: 16,
                    borderWidth: 1,
                    borderColor: `${tokens.brandPrimary}33`,
                  }}
                >
                  <Icon name={item.icon} size={18} color={tokens.brandPrimary} />
                </View>

                {/* Text */}
                <View style={{flex: 1, marginRight: 8}}>
                  <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '800'}}>{item.title}</Text>
                  <Text style={{color: tokens.textSecondary, fontSize: 11, lineHeight: 16, marginTop: 2}}>{item.subtitle}</Text>
                </View>

                {/* Badge or chevron */}
                {item.badge ? (
                  <View style={{paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999, backgroundColor: item.badgeBg, borderWidth: 1, borderColor: item.badgeBorder, marginRight: 4}}>
                    <Text style={{fontSize: 10, fontWeight: '900', color: item.badgeText}}>{item.badge}</Text>
                  </View>
                ) : (
                  <Icon name="chevron-right" size={16} color={tokens.borderStrong} />
                )}
              </View>
            </Pressable>
          ))}
        </View>

        {/* Toggle Lettings/Billing & Currency developer widget */}
        <View style={{marginTop: 10, marginBottom: 40}}>
          <View
            style={{
              backgroundColor: tokens.surfaceCard,
              borderRadius: 16,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              padding: 16,
              ...tokens.shadowSm,
            }}
          >
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
              <View style={{flex: 1, marginRight: 12}}>
                <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                  Lettings & Billing Module
                </Text>
                <Text style={{color: tokens.textSecondary, fontSize: 11, marginTop: 2}}>
                  Toggle module to show/hide Tenants and Finance screens globally.
                </Text>
              </View>
              <Pressable
                onPress={toggleBilling}
                style={{
                  backgroundColor: billingEnabled ? tokens.brandPrimary : tokens.surfaceSunken,
                  paddingHorizontal: 12,
                  paddingVertical: 8,
                  borderRadius: 8,
                  borderWidth: 1,
                  borderColor: billingEnabled ? tokens.brandPrimary : tokens.borderStrong,
                }}
              >
                <Text style={{color: billingEnabled ? '#ffffff' : tokens.textSecondary, fontSize: 11, fontWeight: '800'}}>
                  {billingEnabled ? 'Enabled' : 'Disabled'}
                </Text>
              </Pressable>
            </View>

            <View style={{marginTop: 16, borderTopWidth: 1, borderTopColor: tokens.borderSubtle, paddingTop: 16}}>
              <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', marginBottom: 8}}>
                Configure Agency Currency Symbol
              </Text>
              <View style={{flexDirection: 'row', gap: 6}}>
                {['₦', 'R', '$', '€', '£'].map((sym) => {
                  const active = selectedCurrency === sym;
                  return (
                    <Pressable
                      key={sym}
                      onPress={() => selectCurrency(sym)}
                      style={{
                        flex: 1,
                        paddingVertical: 8,
                        borderRadius: 6,
                        backgroundColor: active ? tokens.brandPrimary : tokens.surfaceSunken,
                        borderWidth: 1,
                        borderColor: active ? tokens.brandPrimary : tokens.borderStrong,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{color: active ? '#ffffff' : tokens.textSecondary, fontSize: 12, fontWeight: '800'}}>
                        {sym}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>
          </View>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
