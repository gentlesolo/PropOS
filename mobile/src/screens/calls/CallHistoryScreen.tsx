import React, {useState} from 'react';
import {ActivityIndicator, FlatList, Pressable, Text, TextInput, View, SafeAreaView} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {callsApi} from '../../api/calls';
import {apiClient} from '../../api/client';
import {Call} from '../../types';
import {format} from 'date-fns';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_STYLES: Record<string, string> = {
  hot: 'bg-red-50 text-red-700 border-red-200',
  warm: 'bg-amber-50 text-amber-700 border-amber-200',
  cold: 'bg-blue-50 text-blue-700 border-blue-200',
  neutral: 'bg-slate-50 text-slate-600 border-slate-200',
};

function CallRow({call, onPress}: {call: Call; onPress: () => void}) {
  const contact = call.contact;
  const summary = call.summary;

  return (
    <Pressable
      className="flex-row items-center bg-white shadow-sm border border-slate-100 rounded-3xl mx-5 mb-3 p-4"
      onPress={onPress}>
      <View className="w-12 h-12 rounded-full bg-brand-50 border border-brand-100 items-center justify-center mr-4">
        <Text className="text-2xl">{call.direction === 'inbound' ? '📲' : '📞'}</Text>
      </View>
      
      <View className="flex-1 pr-2">
        <Text className="text-slate-900 font-bold text-base mb-0.5">
          {contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number}
        </Text>
        {summary ? (
          <Text className="text-slate-500 text-sm font-medium leading-tight" numberOfLines={2}>
            {summary.summary_text}
          </Text>
        ) : (
          <Text className="text-slate-400 text-sm italic font-medium mt-0.5">No summary available</Text>
        )}
      </View>

      <View className="items-end ml-2 justify-between py-1">
        <View className="items-end gap-0.5 mb-2">
          <Text className="text-slate-400 font-bold text-xs">
            {call.started_at ? format(new Date(call.started_at), 'MMM d') : '—'}
          </Text>
          <Text className="text-slate-500 font-bold text-xs">{call.duration_formatted}</Text>
        </View>
        
        {summary && (
          <View className={`px-2 py-0.5 rounded-md border ${SENTIMENT_STYLES[summary.sentiment] || SENTIMENT_STYLES.neutral}`}>
            <Text className={`text-[10px] font-bold uppercase tracking-wider ${SENTIMENT_STYLES[summary.sentiment]?.split(' ')[1] || 'text-slate-600'}`}>
              {summary.sentiment}
            </Text>
          </View>
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
    <SafeAreaView className="flex-1 bg-slate-50">
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">Calls</Text>
          <View className="w-10 h-10 bg-brand-50 rounded-full items-center justify-center">
            <Text className="text-brand-600 font-bold text-lg">{history?.data?.length || 0}</Text>
          </View>
        </View>
        
        <View className="flex-row items-center bg-slate-50 rounded-2xl px-4 py-3 border border-slate-200">
          <Text className="text-slate-400 mr-2">🔍</Text>
          <TextInput
            className="flex-1 text-slate-900 text-base"
            placeholder="Search transcripts…"
            placeholderTextColor="#94a3b8"
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
            className="mt-3 self-start bg-brand-50 px-3 py-1.5 rounded-full"
            onPress={() => { setSearch(''); setIsSearching(false); }}>
            <Text className="text-brand-600 font-bold text-xs">Clear search ×</Text>
          </Pressable>
        )}
      </View>

      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          className="flex-1 pt-4"
          contentContainerStyle={{ paddingBottom: 40 }}
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
            <View className="py-20 px-10 items-center">
              <View className="w-24 h-24 bg-brand-50 rounded-full items-center justify-center mb-6">
                <Text className="text-4xl">📞</Text>
              </View>
              <Text className="text-slate-800 text-xl font-bold mb-2 text-center">
                {isSearching ? 'No results found' : 'No calls yet'}
              </Text>
              <Text className="text-slate-500 text-center font-medium">
                {isSearching
                  ? `We couldn't find any transcripts matching "${search}".`
                  : 'Your inbound and outbound call history will appear here.'}
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
