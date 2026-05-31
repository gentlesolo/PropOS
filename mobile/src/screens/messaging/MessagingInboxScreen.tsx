import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, InboxThread} from '../../api/messaging';
import {format, isToday, isYesterday} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';

type NavProp = NativeStackNavigationProp<MessagingStackParamList>;

const CHANNEL_ICON: Record<string, string> = {
  whatsapp: '💬',
  sms:      '📱',
  email:    '✉️',
};

const CHANNEL_COLOR: Record<string, string> = {
  whatsapp: 'bg-green-600',
  sms:      'bg-blue-600',
  email:    'bg-purple-600',
};

function formatTime(iso: string): string {
  const d = new Date(iso);
  if (isToday(d)) return format(d, 'h:mm a');
  if (isYesterday(d)) return 'Yesterday';
  return format(d, 'd MMM');
}

function ThreadRow({thread, onPress}: {thread: InboxThread; onPress: () => void}) {
  const {contact, last_message} = thread;
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  return (
    <Pressable
      className="flex-row items-center px-4 py-3.5 border-b border-slate-800"
      onPress={onPress}>
      {/* Avatar */}
      <View className="relative mr-3">
        <View className="w-12 h-12 rounded-full bg-brand-700 items-center justify-center">
          <Text className="text-white font-semibold text-base">{initials}</Text>
        </View>
        <View
          className={`absolute -bottom-0.5 -right-0.5 w-5 h-5 rounded-full items-center justify-center ${
            CHANNEL_COLOR[last_message.channel]
          }`}>
          <Text style={{fontSize: 10}}>{CHANNEL_ICON[last_message.channel]}</Text>
        </View>
      </View>

      {/* Content */}
      <View className="flex-1">
        <View className="flex-row items-center justify-between">
          <Text className="text-white font-semibold text-sm">
            {contact.first_name} {contact.last_name}
          </Text>
          <Text className="text-slate-500 text-xs">
            {formatTime(last_message.sent_at)}
          </Text>
        </View>
        <Text className="text-slate-400 text-sm mt-0.5" numberOfLines={1}>
          {last_message.direction === 'outbound' ? 'You: ' : ''}
          {last_message.body}
        </Text>
      </View>
    </Pressable>
  );
}

export function MessagingInboxScreen() {
  const [search, setSearch] = useState('');
  const navigation = useNavigation<NavProp>();

  const {data: threads, isLoading, refetch} = useQuery({
    queryKey: ['inbox', search],
    queryFn: () => messagingApi.inbox(search || undefined).then(r => r.data),
    staleTime: 30_000,
  });

  return (
    <View className="flex-1 bg-surface">
      <View className="pt-14 px-4 pb-3">
        <Text className="text-white text-2xl font-bold mb-3">Messages</Text>
        <TextInput
          className="bg-surface-input text-white rounded-xl px-4 py-2.5 text-sm"
          placeholder="Search conversations…"
          placeholderTextColor="#64748b"
          value={search}
          onChangeText={setSearch}
          clearButtonMode="while-editing"
        />
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <FlatList
          data={threads ?? []}
          keyExtractor={t => String(t.contact_id)}
          renderItem={({item}) => (
            <ThreadRow
              thread={item}
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
            <View className="py-16 items-center">
              <Text className="text-slate-500">No conversations yet</Text>
            </View>
          }
        />
      )}
    </View>
  );
}
