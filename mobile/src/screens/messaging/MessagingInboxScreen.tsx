import React, {useState, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
  useColorScheme,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, InboxThread} from '../../api/messaging';
import {format, isToday, isYesterday, parseISO} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';
import Icon from 'react-native-vector-icons/Feather';

type NavProp = NativeStackNavigationProp<MessagingStackParamList>;
type FilterType = 'All' | 'Unread' | 'WhatsApp' | 'SMS' | 'Email';

const CHANNEL_ICON: Record<string, string> = {
  whatsapp: 'message-circle',
  sms:      'message-square',
  email:    'mail',
};

const CHANNEL_ICON_COLOR: Record<string, string> = {
  whatsapp: '#25D366', // WhatsApp Green
  sms:      '#10B981', // SMS Emerald
  email:    '#0EA5E9', // Email Blue
};

function formatTime(iso: string): string {
  try {
    const d = parseISO(iso);
    if (isToday(d)) return format(d, 'h:mm a');
    if (isYesterday(d)) return 'Yesterday';
    return format(d, 'd MMM');
  } catch (e) {
    return '—';
  }
}

function ThreadRow({
  thread,
  onPress,
  isDark,
}: {
  thread: InboxThread;
  onPress: () => void;
  isDark: boolean;
}) {
  const {contact, last_message} = thread;
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  const isUnread = last_message.direction === 'inbound' && last_message.status !== 'read';

  // Styling selectors
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const borderCard = isDark ? 'border-zinc-800/85' : 'border-slate-200/60';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-500';

  return (
    <Pressable
      className={`flex-row items-center border ${bgCard} ${borderCard} rounded-2xl mx-4 mb-3 p-3.5 relative active:opacity-90 ${
        isUnread ? 'border-l-[3.5px] border-l-brand-500 pl-[11px]' : ''
      }`}
      onPress={onPress}
    >
      {/* Avatar (44px) with channel icon badge overlay */}
      <View className="relative mr-3.5">
        <View className="w-11 h-11 rounded-full bg-brand-500/10 border border-brand-500/15 items-center justify-center">
          <Text className="text-brand-500 font-extrabold text-sm">{initials}</Text>
        </View>
        <View
          className={`absolute bottom-[-1.5px] right-[-1.5px] w-[18px] h-[18px] rounded-full items-center justify-center border ${
            isDark ? 'bg-[#090d16] border-zinc-800' : 'bg-white border-slate-100'
          }`}
        >
          <Icon
            name={CHANNEL_ICON[last_message.channel] || 'message-square'}
            size={10}
            color={CHANNEL_ICON_COLOR[last_message.channel]}
          />
        </View>
      </View>

      {/* Content block */}
      <View className="flex-1">
        <View className="flex-row items-center justify-between mb-1">
          <Text className={`text-sm ${textPrimary} ${isUnread ? 'font-black' : 'font-bold'}`}>
            {contact.first_name} {contact.last_name}
          </Text>
          <Text className={`${textSecondary} text-[10px] font-bold`}>
            {formatTime(last_message.sent_at)}
          </Text>
        </View>
        
        <Text
          className={`text-xs leading-4 ${
            isUnread ? `${isDark ? 'text-text-primary font-bold' : 'text-slate-900 font-bold'}` : textSecondary
          }`}
          numberOfLines={1}
        >
          {last_message.direction === 'outbound' ? (
            <Text className={`font-semibold ${isDark ? 'text-zinc-600' : 'text-slate-400'}`}>You: </Text>
          ) : null}
          {last_message.body}
        </Text>
      </View>

      {/* Unread dot count badge (right side) */}
      {isUnread && (
        <View className="bg-brand-500 w-4.5 h-4.5 rounded-full items-center justify-center ml-2.5">
          <Text className="text-white text-[9px] font-black">1</Text>
        </View>
      )}
    </Pressable>
  );
}

export function MessagingInboxScreen() {
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState<FilterType>('All');
  
  const navigation = useNavigation<NavProp>();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';

  const {data: threads, isLoading, refetch} = useQuery({
    queryKey: ['inbox', search],
    queryFn: () => messagingApi.inbox(search || undefined).then((r) => r.data),
    staleTime: 30_000,
  });

  // Calculate unread count for header badge
  const unreadCount = useMemo(() => {
    const rawThreads = threads ?? [];
    return rawThreads.filter(
      (t) => t.last_message.direction === 'inbound' && t.last_message.status !== 'read'
    ).length;
  }, [threads]);

  // Client-side filtering
  const filteredThreads = useMemo(() => {
    const rawThreads = threads ?? [];
    let list = [...rawThreads];

    if (activeFilter === 'Unread') {
      list = list.filter(
        (t) => t.last_message.direction === 'inbound' && t.last_message.status !== 'read'
      );
    } else if (activeFilter === 'WhatsApp') {
      list = list.filter((t) => t.last_message.channel === 'whatsapp');
    } else if (activeFilter === 'SMS') {
      list = list.filter((t) => t.last_message.channel === 'sms');
    } else if (activeFilter === 'Email') {
      list = list.filter((t) => t.last_message.channel === 'email');
    }

    return list;
  }, [threads, activeFilter]);

  // Styling selectors
  const bgScreen = isDark ? 'bg-[#030712]' : 'bg-slate-50';
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const bgInput = isDark ? 'bg-[#111827]' : 'bg-slate-100';
  const borderHeader = isDark ? 'border-zinc-800/85' : 'border-slate-200/60';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';

  return (
    <SafeAreaView className={`flex-1 ${bgScreen}`}>
      {/* Header + Search bar */}
      <View className={`px-4 pt-4 pb-3 ${bgCard} border-b ${borderHeader} z-10`}>
        <View className="flex-row items-center gap-2 mb-3">
          <Text className={`${textPrimary} text-2xl font-black tracking-tight`}>Inbox</Text>
          {unreadCount > 0 && (
            <View className="bg-brand-500 px-2 py-0.5 rounded-full">
              <Text className="text-white text-[10px] font-black">{unreadCount}</Text>
            </View>
          )}
        </View>

        {/* Search bar */}
        <View className={`flex-row items-center ${bgInput} rounded-xl px-3 py-2.5 border ${
          isDark ? 'border-zinc-800' : 'border-slate-200'
        }`}>
          <Icon name="search" size={16} color={isDark ? '#71717A' : '#94a3b8'} className="mr-2" />
          <TextInput
            className={`flex-1 text-sm ${textPrimary} p-0`}
            placeholder="Search conversations…"
            placeholderTextColor={isDark ? '#71717A' : '#94a3b8'}
            value={search}
            onChangeText={setSearch}
            clearButtonMode="while-editing"
          />
        </View>

        {/* Filter chips */}
        <FlatList
          horizontal
          showsHorizontalScrollIndicator={false}
          data={['All', 'Unread', 'WhatsApp', 'SMS', 'Email'] as FilterType[]}
          keyExtractor={(item) => item}
          contentContainerStyle={{paddingTop: 12, paddingBottom: 2}}
          renderItem={({item}) => {
            const isActive = activeFilter === item;
            return (
              <Pressable
                onPress={() => setActiveFilter(item)}
                className={`px-4 py-2 rounded-full border mr-2 ${
                  isActive
                    ? 'bg-brand-500 border-brand-500 shadow-md shadow-brand-500/20'
                    : isDark
                    ? 'bg-[#111827] border-zinc-800 active:bg-zinc-800'
                    : 'bg-slate-100 border-slate-200 active:bg-slate-200'
                }`}
              >
                <Text
                  className={`text-xs font-bold ${
                    isActive ? 'text-white' : isDark ? 'text-text-secondary' : 'text-slate-650'
                  }`}
                >
                  {item}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      {/* Inbox Threads List */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-3"
          contentContainerStyle={{paddingBottom: 40}}
          data={filteredThreads}
          keyExtractor={(t) => String(t.contact_id)}
          renderItem={({item}) => (
            <ThreadRow
              thread={item}
              isDark={isDark}
              onPress={() =>
                navigation.navigate('Conversation', {
                  contactId: item.contact_id,
                  contactName: `${item.contact.first_name} ${item.contact.last_name}`,
                })
              }
            />
          )}
          onRefresh={refetch}
          refreshing={isLoading}
          ListEmptyComponent={
            <View className="flex-1 items-center justify-center py-24 px-8">
              <View className="w-16 h-16 bg-brand-500/10 border border-brand-500/20 rounded-full items-center justify-center mb-4">
                <Icon name="message-square" size={24} color="#10B981" />
              </View>
              <Text className={`${textPrimary} text-base font-bold mb-1.5 text-center`}>
                No conversations found
              </Text>
              <Text className="text-text-secondary text-xs text-center leading-4 max-w-[240px]">
                Active chats, SMS, and emails will show up here.
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
