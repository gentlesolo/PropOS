import React, {useState} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
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
  whatsapp: 'bg-green-100 border-green-500',
  sms:      'bg-blue-100 border-blue-500',
  email:    'bg-purple-100 border-purple-500',
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
      className="flex-row items-center bg-white shadow-sm border border-slate-100 rounded-3xl mx-5 mb-3 p-4"
      onPress={onPress}>
      {/* Avatar */}
      <View className="relative mr-4">
        <View className="w-14 h-14 rounded-full bg-brand-50 border border-brand-100 items-center justify-center">
          <Text className="text-brand-600 font-extrabold text-lg">{initials}</Text>
        </View>
        <View
          className={`absolute -bottom-1 -right-1 w-6 h-6 rounded-full border-2 border-white items-center justify-center ${
            CHANNEL_COLOR[last_message.channel]
          }`}>
          <Text style={{fontSize: 10}}>{CHANNEL_ICON[last_message.channel]}</Text>
        </View>
      </View>

      {/* Content */}
      <View className="flex-1">
        <View className="flex-row items-center justify-between mb-1">
          <Text className="text-slate-900 font-bold text-base">
            {contact.first_name} {contact.last_name}
          </Text>
          <Text className="text-slate-400 font-bold text-xs">
            {formatTime(last_message.sent_at)}
          </Text>
        </View>
        <Text className="text-slate-500 text-sm font-medium leading-tight" numberOfLines={2}>
          {last_message.direction === 'outbound' ? <Text className="font-bold text-slate-400">You: </Text> : ''}
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
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <Text className="text-slate-900 text-3xl font-extrabold tracking-tight mb-4">Messages</Text>
        <View className="flex-row items-center bg-slate-50 rounded-2xl px-4 py-3 border border-slate-200">
          <Text className="text-slate-400 mr-2">🔍</Text>
          <TextInput
            className="flex-1 text-slate-900 text-base"
            placeholder="Search conversations…"
            placeholderTextColor="#94a3b8"
            value={search}
            onChangeText={setSearch}
            clearButtonMode="while-editing"
          />
        </View>
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-4"
          contentContainerStyle={{ paddingBottom: 40 }}
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
            <View className="py-20 px-10 items-center">
              <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
                <Text className="text-4xl">💬</Text>
              </View>
              <Text className="text-slate-800 text-xl font-bold mb-2 text-center">No messages yet</Text>
              <Text className="text-slate-500 text-center font-medium">Your active conversations will appear here.</Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
