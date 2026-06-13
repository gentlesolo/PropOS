import React from 'react';
import {Pressable, ScrollView, Text, View} from 'react-native';
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

export function MoreScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const {user, clearAuth} = useAuthStore();

  const isManager = (user as any)?.roles?.some?.(
    (r: string) => r === 'admin' || r === 'manager',
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

  const menuItems = [
    {
      id: 'viewings',
      title: 'Viewings',
      subtitle: 'Schedule, itineraries, and geofenced showing sheets',
      icon: 'calendar',
      route: 'Viewings',
      badge: viewingsCount > 0 ? `${viewingsCount} today` : null,
      badgeBg: `${tokens.brandPrimary}1A`,
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
      badgeText: '#F59E0B',
    },
    {
      id: 'notifications',
      title: 'Notification History',
      subtitle: 'Read/unread notifications, alerts, and audit logs',
      icon: 'bell',
      route: 'Notifications',
      badge: notificationsUnread > 0 ? `${notificationsUnread} new` : null,
      badgeBg: tokens.brandPrimary,
      badgeText: '#FFFFFF',
    },
    {
      id: 'tenants',
      title: 'Tenants & Leases',
      subtitle: 'Active leases, rent schedules, and tenant profiles',
      icon: 'key',
      route: 'Tenants',
      badge: null, badgeBg: '', badgeText: '',
    },
    {
      id: 'finance',
      title: 'Finance & Invoices',
      subtitle: 'Rent collections, expense tracking, and invoicing',
      icon: 'dollar-sign',
      route: 'Finance',
      badge: null, badgeBg: '', badgeText: '',
    },
    ...(isManager
      ? [{
          id: 'intel',
          title: 'Intelligence & Intel',
          subtitle: 'Manager analytics, agency performance, and insights',
          icon: 'bar-chart-2',
          route: 'Intelligence',
          badge: null, badgeBg: '', badgeText: '',
        }]
      : []),
    {
      id: 'profile',
      title: 'Profile Settings',
      subtitle: 'Account details, notifications preferences, and device auth',
      icon: 'user',
      route: 'Profile',
      badge: null, badgeBg: '', badgeText: '',
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
        <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '800', letterSpacing: -0.5}}>More Options</Text>
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
              onPress={() => navigation.navigate(item.route)}
              style={({pressed}) => ({
                flexDirection: 'row',
                alignItems: 'center',
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                backgroundColor: tokens.surfaceCard,
                borderRadius: 16,
                padding: 16,
                marginBottom: 12,
                opacity: pressed ? 0.85 : 1,
              })}
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
                  borderColor: `${tokens.brandPrimary}1A`,
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
                <View style={{paddingHorizontal: 10, paddingVertical: 4, borderRadius: 999, backgroundColor: item.badgeBg, marginRight: 4}}>
                  <Text style={{fontSize: 10, fontWeight: '900', color: item.badgeText}}>{item.badge}</Text>
                </View>
              ) : (
                <Icon name="chevron-right" size={16} color={tokens.borderStrong} />
              )}
            </Pressable>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
