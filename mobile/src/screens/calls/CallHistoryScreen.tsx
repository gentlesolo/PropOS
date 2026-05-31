import React, {useState} from 'react';
import {ActivityIndicator, FlatList, Pressable, Text, TextInput, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {callsApi} from '../../api/calls';
import {apiClient} from '../../api/client';
import {Call} from '../../types';
import {format} from 'date-fns';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_DOT: Record<string, string> = {
  hot: 'bg-red-500', warm: 'bg-amber-500', cold: 'bg-blue-400', neutral: 'bg-slate-500',
};

function CallRow({call, onPress}: {call: Call; onPress: () => void}) {
  const contact = call.contact;
  const summary = call.summary;

  return (
    <Pressable
      className="flex-row items-center px-4 py-3.5 border-b border-slate-800"
      onPress={onPress}>
      <View className="mr-3">
        <Text className="text-2xl">{call.direction === 'inbound' ? '📲' : '📞'}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-white font-medium">
          {contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number}
        </Text>
        {summary ? (
          <Text className="text-slate-400 text-xs mt-0.5" numberOfLines={1}>
            {summary.summary_text}
          </Text>
        ) : (
          <Text className="text-slate-600 text-xs mt-0.5 italic">No summary</Text>
        )}
      </View>
      <View className="items-end ml-3 gap-1">
        <Text className="text-slate-400 text-xs">
          {call.started_at ? format(new Date(call.started_at), 'MMM d') : '—'}
        </Text>
        <Text className="text-slate-500 text-xs">{call.duration_formatted}</Text>
        {summary && (
          <View className={`w-2 h-2 rounded-full ${SENTIMENT_DOT[summary.sentiment]}`} />
        )}
      </View>
    </Pressable>
  );
}

export function CallHistoryScreen() {
  const navigation = useNavigation<NavProp>();
  const [search, setSearch] = useState('');
  const [isSearching, setIsSearching] = useState(false);

  const {data: history, isLoading: historyLoading, refetch} = useQuery({
    queryKey: ['calls'],
    queryFn: () => callsApi.list().then(r => r.data),
    enabled: !isSearching,
  });

  const {data: searchResults, isLoading: searchLoading} = useQuery({
    queryKey: ['calls', 'search', search],
    queryFn: () =>
      apiClient
        .get<{data: Call[]}>('/calls/search', {params: {q: search}})
        .then(r => r.data),
    enabled: isSearching && search.trim().length >= 2,
  });

  const calls = isSearching ? (searchResults?.data ?? []) : (history?.data ?? []);
  const isLoading = isSearching ? searchLoading : historyLoading;

  return (
    <View className="flex-1 bg-surface">
      <View className="pt-14 px-4 pb-3">
        <Text className="text-white text-2xl font-bold mb-3">Calls</Text>
        <View className="flex-row items-center bg-surface-input rounded-xl px-3">
          <Text className="text-slate-500 mr-2">🔍</Text>
          <TextInput
            className="flex-1 text-white py-2.5 text-sm"
            placeholder="Search transcripts…"
            placeholderTextColor="#64748b"
            value={search}
            onChangeText={text => {
              setSearch(text);
              setIsSearching(text.trim().length >= 2);
            }}
            clearButtonMode="while-editing"
            returnKeyType="search"
          />
        </View>
        {isSearching && (
          <Pressable
            className="mt-2"
            onPress={() => { setSearch(''); setIsSearching(false); }}>
            <Text className="text-brand-500 text-sm">Clear search</Text>
          </Pressable>
        )}
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <FlatList
          data={calls}
          keyExtractor={c => String(c.id)}
          renderItem={({item}) => (
            <CallRow
              call={item}
              onPress={() => navigation.navigate('CallDetail', {callId: item.id})}
            />
          )}
          onRefresh={refetch}
          refreshing={historyLoading}
          ListEmptyComponent={
            <View className="py-16 items-center px-8">
              <Text className="text-slate-500 text-center">
                {isSearching
                  ? `No calls found matching "${search}"`
                  : 'No calls yet'}
              </Text>
            </View>
          }
        />
      )}
    </View>
  );
}
