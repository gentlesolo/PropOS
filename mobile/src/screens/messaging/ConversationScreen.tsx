import React, {useRef, useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Animated,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, Message} from '../../api/messaging';
import {intelligenceApi, CoachScore} from '../../api/intelligence';
import {format} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';

type RoutePropType = RouteProp<MessagingStackParamList, 'Conversation'>;
type NavProp       = NativeStackNavigationProp<MessagingStackParamList>;

const CHANNEL_ICON: Record<string, string>  = {whatsapp: '💬', sms: '📱', email: '✉️'};
const CHANNEL_LABEL: Record<string, string> = {whatsapp: 'WhatsApp', sms: 'SMS', email: 'Email'};
type Channel = 'whatsapp' | 'sms' | 'email';

function scoreColor(score: number | null): string {
  if (score === null) return 'text-slate-400';
  if (score >= 75)    return 'text-green-400';
  if (score >= 50)    return 'text-amber-400';
  return 'text-red-400';
}

function MessageBubble({message}: {message: Message}) {
  const isOut = message.direction === 'outbound';
  return (
    <View className={`mb-3 max-w-[80%] ${isOut ? 'self-end' : 'self-start'}`}>
      <View className={`rounded-2xl px-4 py-2.5 ${isOut ? 'bg-brand-600 rounded-tr-sm' : 'bg-surface-card rounded-tl-sm'}`}>
        <Text className="text-white text-sm leading-5">{message.body}</Text>
      </View>
      <View className={`flex-row items-center mt-1 gap-1 ${isOut ? 'justify-end' : ''}`}>
        <Text style={{fontSize: 10}}>{CHANNEL_ICON[message.channel]}</Text>
        <Text className="text-slate-500 text-xs">{format(new Date(message.created_at), 'h:mm a')}</Text>
        {isOut && (
          <Text className={`text-xs ${message.status === 'read' ? 'text-brand-400' : 'text-slate-500'}`}>
            {message.status === 'delivered' || message.status === 'read' ? '✓✓' : '✓'}
          </Text>
        )}
      </View>
    </View>
  );
}

// ── Communication Coach panel ────────────────────────────────────────────────
function CoachPanel({
  score,
  loading,
  onApplyRewrite,
  onDismiss,
}: {
  score: CoachScore | null;
  loading: boolean;
  onApplyRewrite: (text: string) => void;
  onDismiss: () => void;
}) {
  if (loading) {
    return (
      <View className="mx-4 mb-2 bg-slate-900 border border-slate-700 rounded-xl p-3 flex-row items-center gap-2">
        <ActivityIndicator size="small" color="#3b82f6" />
        <Text className="text-slate-400 text-xs">Analysing your message…</Text>
      </View>
    );
  }
  if (!score || score.score === null) return null;

  return (
    <View className="mx-4 mb-2 bg-slate-900 border border-brand-800 rounded-xl p-3">
      <View className="flex-row items-center justify-between mb-2">
        <Text className="text-brand-400 text-xs font-semibold uppercase tracking-wide">
          🎯 Message Coach
        </Text>
        <Pressable onPress={onDismiss}>
          <Text className="text-slate-500 text-xs">✕</Text>
        </Pressable>
      </View>

      {/* Score bars */}
      <View className="flex-row gap-4 mb-3">
        {[
          {label: 'Overall', val: score.score},
          {label: 'Tone', val: score.tone_score},
          {label: 'Clarity', val: score.clarity_score},
          {label: 'Impact', val: score.persuasion_score},
        ].map(({label, val}) => (
          <View key={label} className="flex-1 items-center">
            <Text className={`text-sm font-bold ${scoreColor(val)}`}>{val ?? '—'}</Text>
            <Text className="text-slate-500 text-xs">{label}</Text>
          </View>
        ))}
      </View>

      {/* Issues */}
      {score.issues.length > 0 && (
        <View className="mb-2">
          {score.issues.map((issue, i) => (
            <Text key={i} className="text-amber-400 text-xs">⚠ {issue}</Text>
          ))}
        </View>
      )}

      {/* Rewrite suggestion */}
      {score.rewrite && (
        <View className="bg-brand-900/50 rounded-lg p-2.5 mb-2">
          <Text className="text-slate-400 text-xs mb-1">{score.rewrite_reason}</Text>
          <Text className="text-white text-xs leading-4 italic">"{score.rewrite}"</Text>
          <Pressable
            className="mt-2 bg-brand-600 rounded-lg py-1.5 items-center"
            onPress={() => onApplyRewrite(score.rewrite!)}>
            <Text className="text-white text-xs font-semibold">Use this rewrite</Text>
          </Pressable>
        </View>
      )}
    </View>
  );
}

// ── AI suggestion banner ─────────────────────────────────────────────────────
function SuggestionBanner({
  text,
  onUse,
  onDismiss,
}: {
  text: string;
  onUse: () => void;
  onDismiss: () => void;
}) {
  return (
    <View className="mx-4 mb-2 bg-brand-900/70 border border-brand-700 rounded-xl p-3">
      <View className="flex-row items-start justify-between mb-1.5">
        <Text className="text-brand-400 text-xs font-semibold">✨ AI Suggestion</Text>
        <Pressable onPress={onDismiss}>
          <Text className="text-slate-500 text-xs">✕</Text>
        </Pressable>
      </View>
      <Text className="text-slate-200 text-sm leading-5 mb-2">{text}</Text>
      <Pressable
        className="bg-brand-600 rounded-lg py-1.5 items-center"
        onPress={onUse}>
        <Text className="text-white text-xs font-semibold">Use suggestion</Text>
      </Pressable>
    </View>
  );
}

// ── Main screen ──────────────────────────────────────────────────────────────
export function ConversationScreen() {
  const route      = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId, contactName} = route.params;
  const queryClient = useQueryClient();

  const [text, setText]           = useState('');
  const [channel, setChannel]     = useState<Channel>('whatsapp');
  const [coachScore, setCoachScore] = useState<CoachScore | null>(null);
  const [coachLoading, setCoachLoading] = useState(false);
  const [suggestion, setSuggestion] = useState<string | null>(null);
  const listRef = useRef<FlatList>(null);
  const coachTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

  const {data, isLoading} = useQuery({
    queryKey: ['thread', contactId],
    queryFn: () => messagingApi.thread(contactId).then(r => r.data),
    refetchInterval: 15_000,
  });

  const send = useMutation({
    mutationFn: () => messagingApi.send(contactId, text.trim(), channel),
    onSuccess: () => {
      setText('');
      setCoachScore(null);
      setSuggestion(null);
      queryClient.invalidateQueries({queryKey: ['thread', contactId]});
      queryClient.invalidateQueries({queryKey: ['inbox']});
    },
    onError: () => Alert.alert('Failed to send', 'Please try again.'),
  });

  // Debounced communication coach — fires 1.5 s after typing stops (min 20 chars)
  const handleTextChange = (val: string) => {
    setText(val);
    setCoachScore(null);

    if (coachTimer.current) clearTimeout(coachTimer.current);

    if (val.trim().length >= 20) {
      coachTimer.current = setTimeout(async () => {
        setCoachLoading(true);
        try {
          const {data: score} = await intelligenceApi.scoreMessage(
            val.trim(), channel, contactId,
          );
          setCoachScore(score);
        } catch {
          // non-critical
        } finally {
          setCoachLoading(false);
        }
      }, 1500);
    }
  };

  // Fetch AI reply suggestion based on the last inbound message
  const handleSuggestReply = async () => {
    const messages = data?.messages ?? [];
    const lastInbound = [...messages].reverse().find(m => m.direction === 'inbound');
    if (!lastInbound) return;

    try {
      const {data: result} = await intelligenceApi.suggestReply(
        lastInbound.body, channel, contactId,
      );
      if (result.suggestion) setSuggestion(result.suggestion);
    } catch {
      Alert.alert('Could not generate suggestion', 'Please try again.');
    }
  };

  const messages = data?.messages ?? [];

  return (
    <KeyboardAvoidingView
      className="flex-1 bg-surface"
      behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
      keyboardVerticalOffset={0}>

      {/* Header */}
      <View className="pt-14 px-4 pb-3 flex-row items-center border-b border-slate-800">
        <Pressable onPress={() => navigation.goBack()} className="mr-3">
          <Text className="text-brand-500 text-lg">←</Text>
        </Pressable>
        <Text className="flex-1 text-white font-semibold text-lg">{contactName}</Text>
        <Pressable onPress={handleSuggestReply} className="ml-2">
          <Text className="text-brand-500 text-xs">✨ Suggest</Text>
        </Pressable>
      </View>

      {/* Channel selector */}
      <View className="flex-row px-4 py-2 gap-2">
        {(['whatsapp', 'sms', 'email'] as Channel[]).map(ch => (
          <Pressable
            key={ch}
            className={`flex-row items-center px-3 py-1.5 rounded-full gap-1 ${channel === ch ? 'bg-brand-600' : 'bg-surface-card'}`}
            onPress={() => setChannel(ch)}>
            <Text style={{fontSize: 12}}>{CHANNEL_ICON[ch]}</Text>
            <Text className={`text-xs font-medium ${channel === ch ? 'text-white' : 'text-slate-400'}`}>
              {CHANNEL_LABEL[ch]}
            </Text>
          </Pressable>
        ))}
      </View>

      {/* AI suggestion banner */}
      {suggestion && (
        <SuggestionBanner
          text={suggestion}
          onUse={() => { setText(suggestion); setSuggestion(null); }}
          onDismiss={() => setSuggestion(null)}
        />
      )}

      {/* Communication coach */}
      <CoachPanel
        score={coachScore}
        loading={coachLoading}
        onApplyRewrite={rewrite => { setText(rewrite); setCoachScore(null); }}
        onDismiss={() => setCoachScore(null)}
      />

      {/* Messages */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <FlatList
          ref={listRef}
          data={messages}
          keyExtractor={(m, i) => `${m.channel}-${m.id ?? i}`}
          renderItem={({item}) => <MessageBubble message={item} />}
          contentContainerClassName="px-4 py-3"
          onContentSizeChange={() => listRef.current?.scrollToEnd({animated: false})}
          ListEmptyComponent={
            <View className="py-10 items-center">
              <Text className="text-slate-500 text-sm">No messages yet — send the first one</Text>
            </View>
          }
        />
      )}

      {/* Compose bar */}
      <View className="flex-row items-end px-4 py-3 border-t border-slate-800 gap-2">
        <TextInput
          className="flex-1 bg-surface-input text-white rounded-2xl px-4 py-2.5 text-sm"
          placeholder="Type a message…"
          placeholderTextColor="#64748b"
          multiline
          maxLength={1600}
          value={text}
          onChangeText={handleTextChange}
          style={{maxHeight: 120}}
        />
        <Pressable
          className={`w-10 h-10 rounded-full items-center justify-center ${text.trim() && !send.isPending ? 'bg-brand-600' : 'bg-slate-700'}`}
          onPress={() => send.mutate()}
          disabled={!text.trim() || send.isPending}>
          {send.isPending
            ? <ActivityIndicator color="#fff" size="small" />
            : <Text className="text-white text-lg">↑</Text>
          }
        </Pressable>
      </View>
    </KeyboardAvoidingView>
  );
}
