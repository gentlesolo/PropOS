import React, {useState, useMemo} from 'react';
import {
  ActivityIndicator,
  FlatList,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, InboxThread} from '../../api/messaging';
import {format, isToday, isYesterday, parseISO} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type NavProp = NativeStackNavigationProp<MessagingStackParamList>;
type FilterType = 'All' | 'Unread' | 'WhatsApp' | 'SMS' | 'Email';

const CHANNEL_ICON: Record<string, string> = {
  whatsapp: 'message-circle',
  sms:      'message-square',
  email:    'mail',
};

const CHANNEL_ICON_COLOR: Record<string, string> = {
  whatsapp: '#25D366',
  sms:      '#10B981',
  email:    '#0EA5E9',
};

function formatTime(iso: string): string {
  try {
    const d = parseISO(iso);
    if (isToday(d)) return format(d, 'h:mm a');
    if (isYesterday(d)) return 'Yesterday';
    return format(d, 'd MMM');
  } catch {
    return '—';
  }
}

function ThreadRow({
  thread,
  onPress,
  tokens,
}: {
  thread: InboxThread;
  onPress: () => void;
  tokens: ThemeTokens;
}) {
  const {contact, last_message} = thread;
  const initials =
    contact.first_name.charAt(0).toUpperCase() +
    contact.last_name.charAt(0).toUpperCase();

  const isUnread = last_message.direction === 'inbound' && last_message.status !== 'read';

  return (
    <Pressable
      style={({pressed}) => ({
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: tokens.surfaceCard,
        borderWidth: 1,
        borderColor: tokens.borderDefault,
        borderLeftWidth: isUnread ? 3.5 : 1,
        borderLeftColor: isUnread ? tokens.brandPrimary : tokens.borderDefault,
        borderRadius: 16,
        marginHorizontal: 16,
        marginBottom: 12,
        padding: isUnread ? 13.5 : 14,
        opacity: pressed ? 0.85 : 1,
      })}
      onPress={onPress}
    >
      {/* Avatar with channel badge */}
      <View style={{position: 'relative', marginRight: 14}}>
        <View
          style={{
            width: 44,
            height: 44,
            borderRadius: 22,
            backgroundColor: `${tokens.brandPrimary}1A`,
            borderWidth: 1,
            borderColor: `${tokens.brandPrimary}26`,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 14}}>{initials}</Text>
        </View>
        <View
          style={{
            position: 'absolute',
            bottom: -1.5,
            right: -1.5,
            width: 18,
            height: 18,
            borderRadius: 9,
            alignItems: 'center',
            justifyContent: 'center',
            borderWidth: 1,
            backgroundColor: tokens.surfaceCard,
            borderColor: tokens.borderDefault,
          }}
        >
          <Icon
            name={CHANNEL_ICON[last_message.channel] || 'message-square'}
            size={10}
            color={CHANNEL_ICON_COLOR[last_message.channel]}
          />
        </View>
      </View>

      {/* Content */}
      <View style={{flex: 1}}>
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 4}}>
          <Text style={{fontSize: 14, fontWeight: isUnread ? '900' : '700', color: tokens.textPrimary}}>
            {contact.first_name} {contact.last_name}
          </Text>
          <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '700'}}>
            {formatTime(last_message.sent_at)}
          </Text>
        </View>
        <Text
          style={{
            fontSize: 12,
            lineHeight: 16,
            fontWeight: isUnread ? '700' : '400',
            color: isUnread ? tokens.textPrimary : tokens.textSecondary,
          }}
          numberOfLines={1}
        >
          {last_message.direction === 'outbound' && (
            <Text style={{color: tokens.textTertiary, fontWeight: '600'}}>You: </Text>
          )}
          {last_message.body}
        </Text>
      </View>

      {/* Unread badge */}
      {isUnread && (
        <View
          style={{
            width: 18,
            height: 18,
            borderRadius: 9,
            backgroundColor: tokens.brandPrimary,
            alignItems: 'center',
            justifyContent: 'center',
            marginLeft: 10,
          }}
        >
          <Text style={{color: '#FFFFFF', fontSize: 9, fontWeight: '900'}}>1</Text>
        </View>
      )}
    </Pressable>
  );
}

export function MessagingInboxScreen() {
  const {tokens} = useTheme();
  const [search, setSearch] = useState('');
  const [activeFilter, setActiveFilter] = useState<FilterType>('All');

  const navigation = useNavigation<NavProp>();

  const {data: threads, isLoading, refetch} = useQuery({
    queryKey: ['inbox', search],
    queryFn: () => messagingApi.inbox(search || undefined).then((r) => r.data),
    staleTime: 30_000,
  });

  const unreadCount = useMemo(() => {
    const rawThreads = threads ?? [];
    return rawThreads.filter(
      (t) => t.last_message.direction === 'inbound' && t.last_message.status !== 'read'
    ).length;
  }, [threads]);

  const filteredThreads = useMemo(() => {
    const rawThreads = threads ?? [];
    let list = [...rawThreads];
    if (activeFilter === 'Unread') list = list.filter((t) => t.last_message.direction === 'inbound' && t.last_message.status !== 'read');
    else if (activeFilter === 'WhatsApp') list = list.filter((t) => t.last_message.channel === 'whatsapp');
    else if (activeFilter === 'SMS') list = list.filter((t) => t.last_message.channel === 'sms');
    else if (activeFilter === 'Email') list = list.filter((t) => t.last_message.channel === 'email');
    return list;
  }, [threads, activeFilter]);

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 16,
          paddingTop: 16,
          paddingBottom: 12,
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          zIndex: 10,
        }}
      >
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 8, marginBottom: 12}}>
          <Text style={{color: tokens.textPrimary, fontSize: 24, fontWeight: '900', letterSpacing: -0.5}}>Inbox</Text>
          {unreadCount > 0 && (
            <View style={{backgroundColor: tokens.brandPrimary, paddingHorizontal: 8, paddingVertical: 2, borderRadius: 999}}>
              <Text style={{color: '#FFFFFF', fontSize: 10, fontWeight: '900'}}>{unreadCount}</Text>
            </View>
          )}
        </View>

        {/* Search */}
        <View
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            backgroundColor: tokens.surfaceInput,
            borderRadius: 12,
            paddingHorizontal: 12,
            paddingVertical: 10,
            borderWidth: 1,
            borderColor: tokens.borderDefault,
            gap: 8,
          }}
        >
          <Icon name="search" size={16} color={tokens.textTertiary} />
          <TextInput
            style={{flex: 1, fontSize: 14, color: tokens.textPrimary, padding: 0}}
            placeholder="Search conversations…"
            placeholderTextColor={tokens.textTertiary}
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
          contentContainerStyle={{paddingTop: 12, paddingBottom: 2, gap: 8}}
          renderItem={({item}) => {
            const isActive = activeFilter === item;
            return (
              <Pressable
                onPress={() => setActiveFilter(item)}
                style={{
                  paddingHorizontal: 16,
                  paddingVertical: 8,
                  borderRadius: 999,
                  borderWidth: 1,
                  backgroundColor: isActive ? tokens.brandPrimary : tokens.surfaceRaised,
                  borderColor: isActive ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text style={{fontSize: 12, fontWeight: '700', color: isActive ? '#FFFFFF' : tokens.textSecondary}}>
                  {item}
                </Text>
              </Pressable>
            );
          }}
        />
      </View>

      {/* Thread list */}
      {isLoading ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
          <ActivityIndicator color={tokens.brandPrimary} size="large" />
        </View>
      ) : (
        <FlatList
          style={{flex: 1, paddingTop: 12}}
          contentContainerStyle={{paddingBottom: 40}}
          data={filteredThreads}
          keyExtractor={(t) => String(t.contact_id)}
          renderItem={({item}) => (
            <ThreadRow
              thread={item}
              tokens={tokens}
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
            <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 96, paddingHorizontal: 32}}>
              <View
                style={{
                  width: 64,
                  height: 64,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  borderWidth: 1,
                  borderColor: `${tokens.brandPrimary}33`,
                  borderRadius: 32,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 16,
                }}
              >
                <Icon name="message-square" size={24} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700', marginBottom: 6, textAlign: 'center'}}>
                No conversations found
              </Text>
              <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center', lineHeight: 16, maxWidth: 240}}>
                Active chats, SMS, and emails will show up here.
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
