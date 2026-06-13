import React from 'react';
import {
  Pressable,
  ScrollView,
  Text,
  View,
  SafeAreaView,
  useColorScheme,
} from 'react-native';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {useAuthStore} from '../../store/authStore';
import {useNotificationStore} from '../../store/notificationStore';
import {useQuery} from '@tanstack/react-query';
import {callsApi} from '../../api/calls';
import {viewingsApi} from '../../api/viewings';
import {isToday} from 'date-fns';

export function MoreScreen() {
  const navigation = useNavigation<NativeStackNavigationProp<any>>();
  const colorScheme = useColorScheme();
  const isDarkMode = colorScheme !== 'light';
  const {user, clearAuth} = useAuthStore();

  const isManager = (user as any)?.roles?.some?.(
    (r: string) => r === 'admin' || r === 'manager',
  ) ?? false;

  // Store for notifications
  const {unreadCount: notificationsUnread} = useNotificationStore();

  // Fetch pending calls to show badge
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

  // Fetch today's viewings count
  const {data: todayViewings} = useQuery({
    queryKey: ['viewings', 'today'],
    queryFn: () => viewingsApi.today().then((r) => r.data),
  });

  const pendingCallsCount = pendingCalls?.length ?? 0;
  const viewingsCount = todayViewings?.length ?? 0;

  // Custom list items configuration
  const menuItems = [
    {
      id: 'viewings',
      title: 'Viewings',
      subtitle: 'Schedule, itineraries, and geofenced showing sheets',
      icon: 'calendar',
      route: 'Viewings',
      badge: viewingsCount > 0 ? `${viewingsCount} today` : null,
      badgeColor: 'bg-brand-500/10 text-brand-500',
    },
    {
      id: 'calls',
      title: 'Call History',
      subtitle: 'Call transcriptions, audio playback, and AI summaries',
      icon: 'phone-call',
      route: 'Calls',
      badge: pendingCallsCount > 0 ? `${pendingCallsCount} pending` : null,
      badgeColor: 'bg-amber-500/10 text-amber-500',
    },
    {
      id: 'notifications',
      title: 'Notification History',
      subtitle: 'Read/unread notifications, alerts, and audit logs',
      icon: 'bell',
      route: 'Notifications',
      badge: notificationsUnread > 0 ? `${notificationsUnread} new` : null,
      badgeColor: 'bg-emerald-500 text-white font-extrabold',
    },
    {
      id: 'tenants',
      title: 'Tenants & Leases',
      subtitle: 'Active leases, rent schedules, and tenant profiles',
      icon: 'key',
      route: 'Tenants',
    },
    {
      id: 'finance',
      title: 'Finance & Invoices',
      subtitle: 'Rent collections, expense tracking, and invoicing',
      icon: 'dollar-sign',
      route: 'Finance',
    },
    ...(isManager
      ? [
          {
            id: 'intel',
            title: 'Intelligence & Intel',
            subtitle: 'Manager analytics, agency performance, and insights',
            icon: 'bar-chart-2',
            route: 'Intelligence',
          },
        ]
      : []),
    {
      id: 'profile',
      title: 'Profile Settings',
      subtitle: 'Account details, notifications preferences, and device auth',
      icon: 'user',
      route: 'Profile',
    },
  ];

  // Styling tokens
  const styles = {
    bgPage: isDarkMode ? 'bg-[#030712]' : 'bg-slate-50',
    bgCard: isDarkMode ? 'bg-[#090d16]' : 'bg-white',
    borderCard: isDarkMode ? 'border-zinc-800/80' : 'border-slate-100',
    textPrimary: isDarkMode ? 'text-text-primary' : 'text-slate-900',
    textSecondary: isDarkMode ? 'text-text-secondary' : 'text-slate-500',
    textTertiary: isDarkMode ? 'text-text-tertiary' : 'text-slate-400',
    borderHeader: isDarkMode ? 'border-zinc-900' : 'border-slate-200/60',
  };

  const handleLogout = async () => {
    try {
      clearAuth();
    } catch (err) {
      console.warn('Logout error', err);
    }
  };

  return (
    <SafeAreaView className={`flex-1 ${styles.bgPage}`}>
      {/* Header */}
      <View className={`px-5 pt-4 pb-4 ${styles.bgCard} border-b ${styles.borderHeader} flex-row justify-between items-center z-10`}>
        <Text className={`${styles.textPrimary} text-2xl font-extrabold tracking-tight`}>More Options</Text>
        <Pressable
          onPress={handleLogout}
          className="bg-rose-500/10 border border-rose-500/20 px-3.5 py-1.5 rounded-full active:bg-rose-500/20"
        >
          <Text className="text-rose-500 font-extrabold text-xs">Sign Out</Text>
        </Pressable>
      </View>

      <ScrollView className="flex-1 px-4 pt-4" showsVerticalScrollIndicator={false}>
        {/* User Card */}
        <View className={`p-4 rounded-2xl border ${styles.borderCard} ${styles.bgCard} mb-4 flex-row items-center`}>
          <View className="w-12 h-12 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center mr-4">
            <Text className="text-brand-500 font-black text-lg">
              {(user?.first_name?.[0] || 'T').toUpperCase()}
            </Text>
          </View>
          <View className="flex-1">
            <Text className={`${styles.textPrimary} font-extrabold text-base`}>
              {user?.first_name} {user?.last_name}
            </Text>
            <Text className={`${styles.textSecondary} text-xs font-semibold mt-0.5`}>
              {user?.email}
            </Text>
            {isManager && (
              <View className="bg-brand-500/10 border border-brand-500/20 self-start px-2 py-0.5 rounded-md mt-1.5">
                <Text className="text-brand-500 text-[9px] font-extrabold uppercase">Agency Manager</Text>
              </View>
            )}
          </View>
        </View>

        {/* Menu Items List */}
        <View className="mb-10">
          {menuItems.map((item) => (
            <Pressable
              key={item.id}
              onPress={() => navigation.navigate(item.route)}
              className={`flex-row items-center border ${styles.borderCard} ${styles.bgCard} rounded-2xl p-4 mb-3 shadow-sm active:scale-[0.99] transition-transform`}
            >
              {/* Icon */}
              <View className={`w-10 h-10 rounded-full ${isDarkMode ? 'bg-zinc-900/50' : 'bg-slate-100'} items-center justify-center mr-4 border border-zinc-800/10`}>
                <Icon name={item.icon} size={18} color={isDarkMode ? '#10B981' : '#10B981'} />
              </View>

              {/* Title & Subtitle */}
              <View className="flex-1 mr-2">
                <Text className={`${styles.textPrimary} text-sm font-extrabold`}>
                  {item.title}
                </Text>
                <Text className={`${styles.textSecondary} text-[11px] leading-4 mt-0.5`}>
                  {item.subtitle}
                </Text>
              </View>

              {/* Badge or Arrow */}
              {item.badge ? (
                <View className={`px-2.5 py-1 rounded-full ${item.badgeColor} mr-1`}>
                  <Text className="text-[10px] font-black">{item.badge}</Text>
                </View>
              ) : (
                <Icon name="chevron-right" size={16} color={isDarkMode ? '#3f3f46' : '#cbd5e1'} />
              )}
            </Pressable>
          ))}
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}
