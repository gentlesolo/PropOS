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
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_DOT_COLORS: Record<string, string> = {
  hot: '#F43F5E',
  warm: '#F59E0B',
  cold: '#0EA5E9',
  neutral: '#64748B',
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
  tokens: ThemeTokens;
}

function CallRow({call, onPress, searchQuery, tokens}: CallRowProps) {
  const contact = call.contact;
  const summary = call.summary;
  const initials = contact ? `${contact.first_name.charAt(0)}${contact.last_name.charAt(0)}`.toUpperCase() : '?';
  const name = contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number;
  const isMissed = (call.status as string) === 'missed' || (call.status as string) === 'no-answer' || call.duration_seconds === 0;

  const snippet = useMemo(
    () => getSearchSnippet(call.transcript?.full_text, searchQuery),
    [call.transcript?.full_text, searchQuery]
  );

  const renderSnippetText = (text: string, query: string) => {
    const cleanQuery = query.trim();
    const parts = text.split(new RegExp(`(${cleanQuery})`, 'gi'));
    return (
      <Text style={{color: tokens.textSecondary, fontSize: 12, marginTop: 4, lineHeight: 20}} numberOfLines={2}>
        {parts.map((part, i) =>
          part.toLowerCase() === cleanQuery.toLowerCase() ? (
            <Text key={i} style={{color: '#F59E0B', fontWeight: '600'}}>{part}</Text>
          ) : part
        )}
      </Text>
    );
  };

  const directionBg =
    isMissed ? '#F43F5E1A'
    : call.direction === 'inbound' ? `${tokens.brandPrimary}1A`
    : tokens.surfaceRaised;

  const directionColor =
    isMissed ? '#F43F5E'
    : call.direction === 'inbound' ? tokens.brandPrimary
    : tokens.textTertiary;

  const directionIcon =
    isMissed ? 'phone-missed'
    : call.direction === 'inbound' ? 'arrow-down-left'
    : 'arrow-up-right';

  return (
    <Pressable
      onPress={onPress}
      style={({pressed}) => [{
        flexDirection: 'row',
        alignItems: 'center',
        backgroundColor: tokens.surfaceCard,
        borderWidth: 1,
        borderColor: tokens.borderDefault,
        borderRadius: 16,
        marginHorizontal: 16,
        marginBottom: 12,
        padding: 16,
        transform: [{scale: pressed ? 0.98 : 1}],
        ...tokens.shadowSm,
      }]}
    >
      {/* Avatar with direction indicator */}
      <View style={{marginRight: 16, position: 'relative'}}>
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
          }}
        >
          <Text style={{color: tokens.brandPrimary, fontWeight: '700', fontSize: 16}}>{initials}</Text>
        </View>
        <View
          style={{
            position: 'absolute',
            bottom: -6,
            right: -4,
            width: 20,
            height: 20,
            borderRadius: 10,
            borderWidth: 1.5,
            borderColor: tokens.surfacePage,
            backgroundColor: directionBg,
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <Icon name={directionIcon} size={10} color={directionColor} />
        </View>
      </View>

      {/* Content */}
      <View style={{flex: 1, paddingRight: 8}}>
        <Text style={{fontWeight: '600', fontSize: 14, lineHeight: 20, color: isMissed ? '#F43F5E' : tokens.textPrimary}}>
          {name}
        </Text>
        {isMissed ? (
          <Text style={{color: tokens.textTertiary, fontSize: 12, marginTop: 4, fontWeight: '600', letterSpacing: 1, textTransform: 'uppercase'}}>
            No Answer
          </Text>
        ) : searchQuery && snippet ? (
          renderSnippetText(snippet, searchQuery)
        ) : summary ? (
          <Text style={{color: tokens.textSecondary, fontSize: 12, marginTop: 4, lineHeight: 20}} numberOfLines={2}>
            {summary.summary_text}
          </Text>
        ) : (
          <Text style={{color: tokens.textTertiary, fontSize: 12, fontStyle: 'italic', marginTop: 4}}>
            AI transcript processing…
          </Text>
        )}
      </View>

      {/* Right metadata */}
      <View style={{alignItems: 'flex-end', justifyContent: 'space-between', paddingVertical: 2, minWidth: 50}}>
        <View style={{alignItems: 'flex-end', gap: 4, marginBottom: 8}}>
          <Text style={{color: tokens.textPrimary, fontFamily: 'monospace', fontSize: 11, fontWeight: '600'}}>
            {call.duration_formatted}
          </Text>
          <Text style={{color: tokens.textTertiary, fontSize: 10, textTransform: 'uppercase', fontWeight: '700', letterSpacing: 1}}>
            {getRelativeTime(call.started_at)}
          </Text>
        </View>
        {summary?.sentiment && (
          <View style={{width: 10, height: 10, borderRadius: 5, backgroundColor: SENTIMENT_DOT_COLORS[summary.sentiment] || tokens.textTertiary}} />
        )}
      </View>
    </Pressable>
  );
}

export function CallHistoryScreen() {
  const {tokens} = useTheme();
  const navigation = useNavigation<NavProp>();
  const insets = useSafeAreaInsets();

  const [search, setSearch] = useState('');
  const [isSearching, setIsSearching] = useState(false);
  const [activeFilter, setActiveFilter] = useState('All');

  const {data: history, isLoading: historyLoading, refetch} = useQuery({
    queryKey: ['calls'],
    queryFn: () => callsApi.list().then(r => r.data),
    enabled: !search.trim(),
  });

  const {data: searchResults, isLoading: searchLoading} = useQuery({
    queryKey: ['calls', 'search', search],
    queryFn: () =>
      apiClient.get<{data: Call[]}>('/calls/search', {params: {q: search}}).then(r => r.data),
    enabled: !!search.trim(),
  });

  const calls = search.trim() ? (searchResults?.data ?? []) : (history?.data ?? []);
  const isLoading = search.trim() ? searchLoading : historyLoading;

  const filteredCalls = useMemo(() => {
    return calls.filter(c => {
      if (activeFilter === 'All') return true;
      if (activeFilter === 'Inbound') return c.direction === 'inbound';
      if (activeFilter === 'Outbound') return c.direction === 'outbound';
      if (activeFilter === 'Hot') return c.summary?.sentiment === 'hot';
      if (activeFilter === 'Warm') return c.summary?.sentiment === 'warm';
      if (activeFilter === 'Cold') return c.summary?.sentiment === 'cold';
      if (activeFilter === 'This Week') return c.started_at ? isThisWeek(parseISO(c.started_at)) : false;
      return true;
    });
  }, [calls, activeFilter]);

  const sections = useMemo(() => {
    const today: Call[] = [], yesterday: Call[] = [], thisWeek: Call[] = [], earlier: Call[] = [];
    filteredCalls.forEach(c => {
      if (!c.started_at) { earlier.push(c); return; }
      try {
        const date = parseISO(c.started_at);
        if (isToday(date)) today.push(c);
        else if (isYesterday(date)) yesterday.push(c);
        else if (isThisWeek(date)) thisWeek.push(c);
        else earlier.push(c);
      } catch { earlier.push(c); }
    });
    const list = [];
    if (today.length > 0) list.push({title: 'Today', data: today});
    if (yesterday.length > 0) list.push({title: 'Yesterday', data: yesterday});
    if (thisWeek.length > 0) list.push({title: 'This Week', data: thisWeek});
    if (earlier.length > 0) list.push({title: 'Earlier', data: earlier});
    return list;
  }, [filteredCalls]);

  return (
    <SafeAreaView style={{paddingTop: Math.max(insets.top, 16), flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Header */}
      <View
        style={{
          paddingHorizontal: 20,
          paddingBottom: 12,
          backgroundColor: tokens.surfacePage,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderSubtle,
          zIndex: 10,
        }}
      >
        <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
          <Text style={{color: tokens.textPrimary, fontSize: 30, fontWeight: '900', letterSpacing: -0.5}}>Calls</Text>
          <Pressable
            onPress={() => { Vibration.vibrate(10); setIsSearching(!isSearching); if (isSearching) setSearch(''); }}
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              borderWidth: 1,
              alignItems: 'center',
              justifyContent: 'center',
              backgroundColor: isSearching ? `${tokens.brandPrimary}33` : tokens.surfaceRaised,
              borderColor: isSearching ? tokens.brandPrimary : tokens.borderDefault,
            }}
          >
            <Icon name={isSearching ? 'x' : 'search'} size={18} color={isSearching ? tokens.brandPrimary : tokens.textPrimary} />
          </Pressable>
        </View>

        {isSearching && (
          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: tokens.surfaceInput,
              borderRadius: 16,
              paddingHorizontal: 16,
              paddingVertical: 12,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              gap: 8,
            }}
          >
            <Icon name="search" size={16} color={tokens.textTertiary} />
            <TextInput
              autoFocus
              style={{flex: 1, color: tokens.textPrimary, fontSize: 14}}
              placeholder="Search transcripts server-side…"
              placeholderTextColor={tokens.textTertiary}
              value={search}
              onChangeText={setSearch}
              clearButtonMode="while-editing"
              returnKeyType="search"
            />
          </View>
        )}
      </View>

      {/* Filter strip */}
      <View style={{backgroundColor: tokens.surfacePage, borderBottomWidth: 1, borderBottomColor: tokens.borderSubtle}}>
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, paddingVertical: 12, gap: 8}}
        >
          {FILTER_OPTIONS.map(filter => {
            const isActive = activeFilter === filter;
            return (
              <Pressable
                key={filter}
                onPress={() => { Vibration.vibrate(10); setActiveFilter(filter); }}
                style={{
                  paddingHorizontal: 16,
                  paddingVertical: 8,
                  borderRadius: 999,
                  borderWidth: 1,
                  backgroundColor: isActive ? tokens.brandPrimary : tokens.surfaceRaised,
                  borderColor: isActive ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text
                  style={{
                    fontSize: 12,
                    fontWeight: '700',
                    textTransform: 'uppercase',
                    letterSpacing: 0.8,
                    color: isActive ? '#FFFFFF' : tokens.textSecondary,
                  }}
                >
                  {filter}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* List or empty state */}
      {isLoading ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
          <ActivityIndicator color={tokens.brandPrimary} size="large" />
        </View>
      ) : (
        <SectionList
          style={{flex: 1, paddingTop: 12}}
          contentContainerStyle={{paddingBottom: 40}}
          sections={sections}
          keyExtractor={c => String(c.id)}
          stickySectionHeadersEnabled
          renderSectionHeader={({section: {title}}) => (
            <View
              style={{
                backgroundColor: tokens.surfacePage,
                borderBottomWidth: 1,
                borderBottomColor: tokens.borderSubtle,
                paddingHorizontal: 20,
                paddingVertical: 8,
              }}
            >
              <Text style={{color: tokens.brandPrimary, fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 2}}>
                {title}
              </Text>
            </View>
          )}
          renderItem={({item}) => (
            <CallRow
              call={item}
              tokens={tokens}
              searchQuery={search}
              onPress={() => navigation.navigate('CallDetail', {callId: item.id})}
            />
          )}
          onRefresh={refetch}
          refreshing={historyLoading}
          ListEmptyComponent={
            <View style={{flex: 1, alignItems: 'center', justifyContent: 'center', paddingVertical: 80, paddingHorizontal: 32}}>
              <View
                style={{
                  width: 80,
                  height: 80,
                  borderRadius: 40,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  borderWidth: 1,
                  borderColor: `${tokens.brandPrimary}33`,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 24,
                }}
              >
                <Icon name="phone" size={32} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '700', textAlign: 'center', marginBottom: 8}}>
                {search ? 'No search matches' : 'No calls yet'}
              </Text>
              <Text style={{color: tokens.textSecondary, fontSize: 12, textAlign: 'center', lineHeight: 20, maxWidth: 280}}>
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
