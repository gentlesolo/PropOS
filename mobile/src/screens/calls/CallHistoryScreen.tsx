import React, {useState, useMemo} from 'react';
import {
  ActivityIndicator,
  SectionList,
  Pressable,
  Text,
  TextInput,
  View,
  SafeAreaView,
  ScrollView,
  Vibration,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import Icon from 'react-native-vector-icons/Feather';
import {useSafeAreaInsets} from 'react-native-safe-area-context';
import {isToday, isYesterday, isThisWeek, parseISO} from 'date-fns';
import {callsApi} from '../../api/calls';
import {apiClient} from '../../api/client';
import {Call} from '../../types';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_DOTS: Record<string, string> = {
  hot: 'bg-danger',
  warm: 'bg-accent',
  cold: 'bg-info',
  neutral: 'bg-slate-500',
};

const FILTER_OPTIONS = ['All', 'Inbound', 'Outbound', 'Hot', 'Warm', 'Cold', 'This Week'];

function getRelativeTime(dateStr?: string) {
  if (!dateStr) return '—';
  try {
    const date = new Date(dateStr);
    const diffMs = Date.now() - date.getTime();
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMins / 60);
    const diffDays = Math.floor(diffHours / 24);

    if (diffMins < 1) return 'now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    return `${diffDays}d ago`;
  } catch {
    return '—';
  }
}

function getSearchSnippet(fullText?: string, query?: string) {
  if (!fullText || !query || !query.trim()) return null;
  const cleanQuery = query.trim().toLowerCase();
  const lowerText = fullText.toLowerCase();
  const idx = lowerText.indexOf(cleanQuery);
  if (idx === -1) return null;

  const start = Math.max(0, idx - 25);
  const end = Math.min(fullText.length, idx + 45);
  let snippet = fullText.substring(start, end);
  if (start > 0) snippet = '...' + snippet;
  if (end < fullText.length) snippet = snippet + '...';
  return snippet;
}

interface CallRowProps {
  call: Call;
  onPress: () => void;
  searchQuery?: string;
}

function CallRow({call, onPress, searchQuery}: CallRowProps) {
  const contact = call.contact;
  const summary = call.summary;

  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}`.toUpperCase() : '?';
  const name = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;
  const isMissed = (call.status as string) === 'missed' || (call.status as string) === 'no-answer' || call.duration_seconds === 0;

  // Search snippet extraction
  const snippet = useMemo(() => {
    return getSearchSnippet(call.transcript?.full_text, searchQuery);
  }, [call.transcript?.full_text, searchQuery]);

  const renderSnippetText = (text: string, query: string) => {
    const cleanQuery = query.trim();
    const parts = text.split(new RegExp(`(${cleanQuery})`, 'gi'));
    return (
      <Text className="text-text-secondary text-xs mt-1 leading-5" numberOfLines={2}>
        {parts.map((part, i) =>
          part.toLowerCase() === cleanQuery.toLowerCase() ? (
            <Text key={i} className="text-accent font-semibold">{part}</Text>
          ) : (
            part
          )
        )}
      </Text>
    );
  };

  return (
    <Pressable
      className="flex-row items-center bg-[#090d16]/75 border border-slate-900 rounded-2xl mx-4 mb-3 p-4 shadow-sm"
      onPress={onPress}
      style={({pressed}) => [{transform: [{scale: pressed ? 0.98 : 1}]}]}
    >
      {/* Left Avatar with small call direction indicator overlay */}
      <View className="relative mr-4">
        <View className="w-12 h-12 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center">
          <Text className="text-brand-500 font-bold text-base">{initials}</Text>
        </View>
        <View className={`absolute -bottom-1.5 -right-1 w-5 h-5 rounded-full border border-[#030712] items-center justify-center ${
          isMissed ? 'bg-danger/20' : call.direction === 'inbound' ? 'bg-[#10B981]/10' : 'bg-slate-800'
        }`}>
          {isMissed ? (
            <Icon name="phone-missed" size={10} color="#F43F5E" />
          ) : call.direction === 'inbound' ? (
            <Icon name="arrow-down-left" size={10} color="#10B981" />
          ) : (
            <Icon name="arrow-up-right" size={10} color="#A1A1AA" />
          )}
        </View>
      </View>

      {/* Middle Context */}
      <View className="flex-1 pr-2">
        <Text className={`font-semibold text-sm leading-tight ${isMissed ? 'text-danger' : 'text-white'}`}>
          {name}
        </Text>
        {isMissed ? (
          <Text className="text-text-tertiary text-xs mt-1 font-semibold uppercase tracking-wider">No Answer</Text>
        ) : searchQuery && snippet ? (
          renderSnippetText(snippet, searchQuery)
        ) : summary ? (
          <Text className="text-text-secondary text-xs mt-1 leading-5" numberOfLines={2}>
            {summary.summary_text}
          </Text>
        ) : (
          <Text className="text-text-tertiary text-xs italic mt-1">AI transcript processing…</Text>
        )}
      </View>

      {/* Right Column */}
      <View className="items-end justify-between py-0.5 min-w-[50px]">
        <View className="items-end gap-1 mb-2">
          <Text className="text-white font-mono text-[11px] font-semibold">
            {call.duration_formatted}
          </Text>
          <Text className="text-text-tertiary text-[10px] uppercase font-bold tracking-wide">
            {getRelativeTime(call.started_at)}
          </Text>
        </View>
        
        {summary?.sentiment && (
          <View className={`w-2.5 h-2.5 rounded-full ${SENTIMENT_DOTS[summary.sentiment]}`} />
        )}
      </View>
    </Pressable>
  );
}

export function CallHistoryScreen() {
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();

  const [search, setSearch] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [activeFilter, setActiveFilter] = useState('All');

  // Load complete call list
  const {data: history, isLoading: historyLoading, refetch} = useQuery({
    queryKey: ['calls'],
    queryFn: () => callsApi.list().then(r => r.data),
    enabled: !search.trim(),
  });

  // Search API lookup
  const {data: searchResults, isLoading: searchLoading} = useQuery({
    queryKey: ['calls', 'search', search],
    queryFn: () =>
      apiClient
        .get<{data: Call[]}>('/calls/search', {params: {q: search}})
        .then(r => r.data),
    enabled: !!search.trim(),
  });

  const calls = search.trim() ? (searchResults?.data ?? []) : (history?.data ?? []);
  const isLoading = search.trim() ? searchLoading : historyLoading;

  // Filter logic
  const filteredCalls = useMemo(() => {
    return calls.filter(c => {
      if (activeFilter === 'All') return true;
      if (activeFilter === 'Inbound') return c.direction === 'inbound';
      if (activeFilter === 'Outbound') return c.direction === 'outbound';
      if (activeFilter === 'Hot') return c.summary?.sentiment === 'hot';
      if (activeFilter === 'Warm') return c.summary?.sentiment === 'warm';
      if (activeFilter === 'Cold') return c.summary?.sentiment === 'cold';
      if (activeFilter === 'This Week') {
        if (!c.started_at) return false;
        return isThisWeek(parseISO(c.started_at));
      }
      return true;
    });
  }, [calls, activeFilter]);

  // Group sections by Date (Today, Yesterday, This Week, Earlier)
  const sections = useMemo(() => {
    const todayGroup: Call[] = [];
    const yesterdayGroup: Call[] = [];
    const thisWeekGroup: Call[] = [];
    const earlierGroup: Call[] = [];

    filteredCalls.forEach(c => {
      if (!c.started_at) {
        earlierGroup.push(c);
        return;
      }
      try {
        const date = parseISO(c.started_at);
        if (isToday(date)) todayGroup.push(c);
        else if (isYesterday(date)) yesterdayGroup.push(c);
        else if (isThisWeek(date)) thisWeekGroup.push(c);
        else earlierGroup.push(c);
      } catch {
        earlierGroup.push(c);
      }
    });

    const list = [];
    if (todayGroup.length > 0) list.push({title: 'Today', data: todayGroup});
    if (yesterdayGroup.length > 0) list.push({title: 'Yesterday', data: yesterdayGroup});
    if (thisWeekGroup.length > 0) list.push({title: 'This Week', data: thisWeekGroup});
    if (earlierGroup.length > 0) list.push({title: 'Earlier', data: earlierGroup});

    return list;
  }, [filteredCalls]);

  return (
    <SafeAreaView style={{paddingTop: Math.max(insets.top, 16)}} className="flex-1 bg-surface">
      
      {/* ── HEADER ────────────────────────────────────────────────────── */}
      <View className="px-5 pb-3 bg-surface border-b border-slate-900/60 z-10">
        <View className="flex-row justify-between items-center mb-4">
          <Text className="text-white text-3xl font-black tracking-tight">Calls</Text>
          <Pressable
            onPress={() => { Vibration.vibrate(10); setIsSearching(!isSearching); if (isSearching) setSearch(''); }}
            className={`w-10 h-10 rounded-full border items-center justify-center ${
              isSearching ? 'bg-brand-500/20 border-brand-500' : 'bg-surface-raised border-slate-800'
            }`}
          >
            <Icon name={isSearching ? 'x' : 'search'} size={18} color={isSearching ? '#10B981' : '#FAFAFA'} />
          </Pressable>
        </View>

        {isSearching && (
          <View className="flex-row items-center bg-surface-raised rounded-2xl px-4 py-3 border border-slate-800/80">
            <Icon name="search" size={16} color="#A1A1AA" className="mr-2" />
            <TextInput
              autoFocus
              className="flex-1 text-white text-sm"
              placeholder="Search transcripts server-side…"
              placeholderTextColor="#71717A"
              value={search}
              onChangeText={setSearch}
              clearButtonMode="while-editing"
              returnKeyType="search"
            />
          </View>
        )}
      </View>

      {/* ── HORIZONTAL FILTER ROW ─────────────────────────────────────── */}
      <View>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, paddingVertical: 12, gap: 8}}
          className="bg-surface py-3 border-b border-slate-900/40"
        >
          {FILTER_OPTIONS.map(filter => {
            const isActive = activeFilter === filter;
            return (
              <Pressable
                key={filter}
                onPress={() => {
                  Vibration.vibrate(10);
                  setActiveFilter(filter);
                }}
                className={`px-4 py-2 rounded-full border ${
                  isActive ? 'bg-brand-500 border-brand-500 shadow-sm shadow-brand-500/20' : 'bg-surface-raised border-slate-850'
                }`}
              >
                <Text className={`text-xs font-bold uppercase tracking-wider ${isActive ? 'text-white' : 'text-text-secondary'}`}>
                  {filter}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* ── LIST OR EMPTY STATE ──────────────────────────────────────── */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <SectionList
          className="flex-1 pt-3"
          contentContainerStyle={{paddingBottom: 40}}
          sections={sections}
          keyExtractor={c => String(c.id)}
          stickySectionHeadersEnabled={true}
          renderSectionHeader={({section: {title}}) => (
            <View className="bg-surface/90 border-b border-slate-900/40 px-5 py-2">
              <Text className="text-brand-500 text-[10px] font-extrabold uppercase tracking-widest">{title}</Text>
            </View>
          )}
          renderItem={({item}) => (
            <CallRow
              call={item}
              searchQuery={search}
              onPress={() => navigation.navigate('CallDetail', {callId: item.id})}
            />
          )}
          onRefresh={refetch}
          refreshing={historyLoading}
          ListEmptyComponent={
            <View className="flex-1 items-center justify-center py-20 px-8">
              <View className="w-20 h-20 rounded-full bg-brand-500/10 border border-brand-500/20 items-center justify-center mb-6 relative">
                <Icon name="phone" size={32} color="#10B981" />
                <View className="absolute w-28 h-28 border border-brand-500/5 rounded-full" />
                <View className="absolute w-36 h-36 border border-brand-500/5 rounded-full animate-ping" />
              </View>
              <Text className="text-white text-lg font-bold text-center mb-2">
                {search ? 'No search matches' : 'No calls yet'}
              </Text>
              <Text className="text-text-secondary text-xs text-center leading-5 max-w-[280px]">
                {search
                  ? `Your calls did not contain any transcripts matching "${search}".`
                  : 'Your calls will appear here once you make or receive your first one.'}
              </Text>
            </View>
          }
        />
      )}
    </SafeAreaView>
  );
}
