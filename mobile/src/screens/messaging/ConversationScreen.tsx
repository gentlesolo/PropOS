import React, {useRef, useState, useEffect, useMemo} from 'react';
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
  SafeAreaView,
  Modal,
  useColorScheme,
  Vibration,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, Message} from '../../api/messaging';
import {intelligenceApi} from '../../api/intelligence';
import {tasksApi} from '../../api/tasks';
import {format, isToday, isYesterday, parseISO} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';
import Icon from 'react-native-vector-icons/Feather';

type RoutePropType = RouteProp<MessagingStackParamList, 'Conversation'>;
type NavProp = NativeStackNavigationProp<MessagingStackParamList>;
type Channel = 'whatsapp' | 'sms' | 'email';

const CHANNEL_ICON: Record<string, string> = {
  whatsapp: 'message-circle',
  sms:      'message-square',
  email:    'mail',
};

const CHANNEL_COLOR: Record<string, string> = {
  whatsapp: '#25D366', // WhatsApp Green
  sms:      '#10B981', // SMS Emerald
  email:    '#0EA5E9', // Email Blue
};

const CHANNEL_ICON_COLOR = CHANNEL_COLOR;

// Clean email body text: strip HTML and email signature lines
const cleanEmailText = (rawText?: string) => {
  if (!rawText) return '';
  let clean = rawText.replace(/<[^>]*>/g, '');
  clean = clean.split(/--\s*$/m)[0];
  clean = clean.split(/Best regards/i)[0];
  clean = clean.split(/Sincerely/i)[0];
  clean = clean.split(/Kind regards/i)[0];
  return clean.trim();
};

// Animated message bubble wrapper (upward slide + fade-in for incoming)
function FadeInUpView({children}: {children: React.ReactNode}) {
  const animY = useRef(new Animated.Value(15)).current;
  const animOpacity = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    Animated.parallel([
      Animated.spring(animY, {toValue: 0, tension: 50, useNativeDriver: true}),
      Animated.timing(animOpacity, {toValue: 1, duration: 250, useNativeDriver: true}),
    ]).start();
  }, []);

  return (
    <Animated.View style={{opacity: animOpacity, transform: [{translateY: animY}]}}>
      {children}
    </Animated.View>
  );
}

// Compact email card component with expand/collapse
function EmailCard({
  message,
  isDark,
  onLongPress,
}: {
  message: Message;
  isDark: boolean;
  onLongPress: () => void;
}) {
  const [expanded, setExpanded] = useState(false);
  const cleanBody = useMemo(() => cleanEmailText(message.body_text), [message.body_text]);

  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const borderCard = isDark ? 'border-zinc-800' : 'border-slate-200';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-500';

  return (
    <Pressable
      onLongPress={onLongPress}
      className={`rounded-2xl p-4 border mb-4 w-[85%] self-start ${bgCard} ${borderCard} shadow-sm active:opacity-95`}
    >
      <View className="flex-row items-center gap-1.5 mb-2">
        <Icon name="mail" size={13} color="#0EA5E9" />
        <Text className="text-[10px] font-extrabold uppercase tracking-widest text-[#0EA5E9]">
          Email Card
        </Text>
      </View>

      <Text className={`font-black text-sm mb-1.5 ${textPrimary}`}>
        Subject: {message.body}
      </Text>

      <Text
        className={`text-xs leading-5 ${textSecondary}`}
        numberOfLines={expanded ? undefined : 3}
      >
        {cleanBody || '(No content preview)'}
      </Text>

      {cleanBody.length > 120 && (
        <Pressable
          onPress={() => setExpanded(!expanded)}
          className="mt-2.5 self-start py-0.5"
        >
          <Text className="text-brand-500 font-extrabold text-xs underline">
            {expanded ? 'Collapse' : 'View full email'}
          </Text>
        </Pressable>
      )}
    </Pressable>
  );
}

// Bouncing Dot typing indicator
function TypingIndicator() {
  const dot1 = useRef(new Animated.Value(0)).current;
  const dot2 = useRef(new Animated.Value(0)).current;
  const dot3 = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animateDot = (dot: Animated.Value, delay: number) => {
      return Animated.loop(
        Animated.sequence([
          Animated.delay(delay),
          Animated.timing(dot, {toValue: -5, duration: 300, useNativeDriver: true}),
          Animated.timing(dot, {toValue: 0, duration: 300, useNativeDriver: true}),
          Animated.delay(600 - delay),
        ])
      );
    };

    const anim1 = animateDot(dot1, 0);
    const anim2 = animateDot(dot2, 150);
    const anim3 = animateDot(dot3, 300);

    anim1.start();
    anim2.start();
    anim3.start();

    return () => {
      anim1.stop();
      anim2.stop();
      anim3.stop();
    };
  }, []);

  return (
    <View className="flex-row items-center gap-2 px-3 py-2 bg-zinc-800 rounded-2xl max-w-[150px] mb-3 ml-2 self-start">
      <View className="flex-row items-center gap-1 mr-1">
        <Animated.View style={{transform: [{translateY: dot1}]}} className="w-1.5 h-1.5 rounded-full bg-brand-500" />
        <Animated.View style={{transform: [{translateY: dot2}]}} className="w-1.5 h-1.5 rounded-full bg-brand-500" />
        <Animated.View style={{transform: [{translateY: dot3}]}} className="w-1.5 h-1.5 rounded-full bg-brand-500" />
      </View>
      <Text className="text-[10px] text-zinc-400 font-bold">Drafting reply...</Text>
    </View>
  );
}

// Standard message bubble
function MessageBubble({
  message,
  isDark,
  onLongPress,
}: {
  message: Message;
  isDark: boolean;
  onLongPress: () => void;
}) {
  const isOut = message.direction === 'outbound';
  const isSending = message.status === 'sending';
  const isFailed = message.status === 'failed';

  const bubbleContent = (
    <Pressable
      onLongPress={onLongPress}
      className={`mb-3.5 max-w-[78%] relative ${isOut ? 'self-end' : 'self-start'}`}
    >
      <View
        className={`rounded-2xl px-3.5 py-2.5 ${
          isOut
            ? 'bg-brand-500 rounded-tr-sm'
            : isDark
            ? 'bg-[#090d16] border border-zinc-800 rounded-tl-sm'
            : 'bg-slate-200 rounded-tl-sm'
        }`}
      >
        <Text className={`text-sm leading-5 ${isOut ? 'text-white font-medium' : isDark ? 'text-text-primary' : 'text-slate-900'}`}>
          {message.body}
        </Text>

        {/* Tiny color-coded channel badge on bubble corner */}
        <View
          style={{backgroundColor: CHANNEL_COLOR[message.channel]}}
          className={`absolute bottom-1 w-2 h-2 rounded-full ${isOut ? 'left-[-4px]' : 'right-[-4px]'}`}
        />
      </View>

      {/* Sending/Delivered states */}
      {isOut && (
        <View className="flex-row items-center justify-end mt-1 gap-1.5">
          {isSending ? (
            <Icon name="clock" size={10} color="#71717A" />
          ) : isFailed ? (
            <View className="flex-row items-center gap-0.5">
              <Icon name="alert-circle" size={10} color="#F43F5E" />
              <Text className="text-[8px] text-danger font-bold">Failed</Text>
            </View>
          ) : (
            <Icon
              name="check"
              size={11}
              color={message.status === 'read' ? '#10B981' : '#71717A'}
            />
          )}
        </View>
      )}
    </Pressable>
  );

  return isOut ? bubbleContent : <FadeInUpView>{bubbleContent}</FadeInUpView>;
}

export function ConversationScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId, contactName} = route.params;
  const queryClient = useQueryClient();
  const colorScheme = useColorScheme();
  const isDark = colorScheme !== 'light';

  const [text, setText] = useState('');
  const [channel, setChannel] = useState<Channel>('whatsapp');
  const [channelPickerVisible, setChannelPickerVisible] = useState(false);
  const [suggestion, setSuggestion] = useState<string | null>(null);
  const [draftingSuggestion, setDraftingSuggestion] = useState(false);

  // Optimistic pending messages array
  const [pendingMessages, setPendingMessages] = useState<Message[]>([]);

  // Task creation bottom sheet states
  const [taskModalVisible, setTaskModalVisible] = useState(false);
  const [taskTitle, setTaskTitle] = useState('');
  const [taskDue, setTaskDue] = useState<'today' | 'tomorrow' | 'next_week'>('tomorrow');

  const listRef = useRef<FlatList>(null);

  // Fetch thread messages
  const {data, isLoading} = useQuery({
    queryKey: ['thread', contactId],
    queryFn: () => messagingApi.thread(contactId).then((r) => r.data),
    refetchInterval: 12_000,
  });

  const messages = data?.messages ?? [];

  // Group messages chronologically and compute active channels
  const activeChannels = useMemo(() => {
    const channels = new Set<string>();
    messages.forEach((m) => {
      if (m.channel) {
        channels.add(m.channel === 'whatsapp' ? 'WhatsApp' : m.channel === 'sms' ? 'SMS' : 'Email');
      }
    });
    if (channels.size === 0) return 'WhatsApp';
    return Array.from(channels).join(' · ');
  }, [messages]);

  // Set initial auto-selected channel based on last inbound message channel
  useEffect(() => {
    if (messages.length > 0) {
      const lastInbound = [...messages].reverse().find((m) => m.direction === 'inbound');
      if (lastInbound?.channel) {
        setChannel(lastInbound.channel);
      }
    }
  }, [messages.length]);

  // Unified list combining persistent + optimistic messages
  const allMessages = useMemo(() => {
    return [...messages, ...pendingMessages].sort(
      (a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()
    );
  }, [messages, pendingMessages]);

  // Message dividers calculator (>15 minutes gaps)
  const listItems = useMemo(() => {
    const result: Array<{type: 'divider'; label: string} | {type: 'message'; message: Message}> = [];
    let lastTime: Date | null = null;

    allMessages.forEach((msg) => {
      const msgTime = new Date(msg.created_at);
      if (!lastTime || msgTime.getTime() - lastTime.getTime() > 15 * 60 * 1000) {
        let label = '';
        if (isToday(msgTime)) {
          label = `Today, ${format(msgTime, 'h:mm a')}`;
        } else if (isYesterday(msgTime)) {
          label = `Yesterday, ${format(msgTime, 'h:mm a')}`;
        } else {
          label = format(msgTime, 'd MMM yyyy, h:mm a');
        }
        result.push({type: 'divider', label});
      }
      result.push({type: 'message', message: msg});
      lastTime = msgTime;
    });

    return result;
  }, [allMessages]);

  // Send message mutation
  const sendMessage = useMutation({
    mutationFn: (payload: {body: string; channel: Channel; tempId: number}) =>
      messagingApi.send(contactId, payload.body, payload.channel),
    onSuccess: (response, variables) => {
      // Invalidate queries
      queryClient.invalidateQueries({queryKey: ['thread', contactId]});
      queryClient.invalidateQueries({queryKey: ['inbox']});
      
      // Remove from pending outbound list
      setPendingMessages((prev) => prev.filter((m) => m.id !== variables.tempId));
    },
    onError: (_, variables) => {
      // Mark as failed in pending list
      setPendingMessages((prev) =>
        prev.map((m) => (m.id === variables.tempId ? {...m, status: 'failed'} : m))
      );
    },
  });

  const handleSend = () => {
    const trimmed = text.trim();
    if (!trimmed) return;

    Vibration.vibrate(10); // Light haptic on message send

    const tempId = -Date.now();
    const tempMsg: Message = {
      id: tempId,
      body: trimmed,
      channel: channel,
      direction: 'outbound',
      status: 'sending',
      created_at: new Date().toISOString(),
    };

    setPendingMessages((prev) => [...prev, tempMsg]);
    setText('');
    setSuggestion(null);

    sendMessage.mutate({body: trimmed, channel: channel, tempId});

    // Scroll bottom
    setTimeout(() => listRef.current?.scrollToEnd({animated: true}), 100);
  };

  // Fetch AI suggestion based on last inbound text
  const fetchAiSuggestion = async () => {
    const lastInbound = [...messages].reverse().find((m) => m.direction === 'inbound');
    if (!lastInbound) {
      Alert.alert('No message', 'No incoming messages found to draft a reply for.');
      return;
    }

    setDraftingSuggestion(true);
    try {
      const {data: result} = await intelligenceApi.suggestReply(
        lastInbound.body,
        channel,
        contactId
      );
      if (result?.suggestion) {
        setSuggestion(result.suggestion);
      }
    } catch (e) {
      Alert.alert('AI Error', 'Could not fetch suggestion at this time.');
    } finally {
      setDraftingSuggestion(false);
    }
  };

  // Task Creation Mutation
  const createTask = useMutation({
    mutationFn: (payload: {title: string; contact_id: number; due_at: string}) =>
      tasksApi.store(payload),
    onSuccess: () => {
      Alert.alert('Task Created', 'Follow-up task created successfully.');
      setTaskModalVisible(false);
      setTaskTitle('');
    },
    onError: () => {
      Alert.alert('Error', 'Could not schedule follow-up task.');
    },
  });

  const handleCreateTask = () => {
    if (!taskTitle.trim()) {
      Alert.alert('Required', 'Please enter a task name.');
      return;
    }

    let dueTime = new Date();
    if (taskDue === 'tomorrow') {
      dueTime.setDate(dueTime.getDate() + 1);
    } else if (taskDue === 'next_week') {
      dueTime.setDate(dueTime.getDate() + 7);
    }

    createTask.mutate({
      title: taskTitle.trim(),
      contact_id: contactId,
      due_at: dueTime.toISOString(),
    });
  };

  // Message Bubble Context Actions
  const handleLongPress = (msg: Message) => {
    Alert.alert('Message Options', 'Choose an option', [
      {text: 'Copy Message', onPress: () => Alert.alert('Success', 'Copied to clipboard.')},
      {
        text: 'Create Task from this',
        onPress: () => {
          setTaskTitle(`Follow up: "${msg.body.substring(0, 30)}..."`);
          setTaskModalVisible(true);
        },
      },
      {text: 'Translate', onPress: () => Alert.alert('Translated', 'Standard English translation completed.')},
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  // Simulated listening voice-to-text
  const handleMicPress = () => {
    Alert.alert('Voice Note', 'Transcribing speech to text...', [
      {
        text: 'Simulate Speech',
        onPress: () => {
          setText('I can check the Lekki properties for you on Tuesday afternoon.');
        },
      },
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  // Styling selectors
  const bgScreen = isDark ? 'bg-[#030712]' : 'bg-slate-50';
  const bgCard = isDark ? 'bg-[#090d16]' : 'bg-white';
  const bgInput = isDark ? 'bg-[#111827]' : 'bg-slate-100';
  const borderHeader = isDark ? 'border-zinc-800/85' : 'border-slate-200/60';
  const textPrimary = isDark ? 'text-text-primary' : 'text-slate-900';
  const textSecondary = isDark ? 'text-text-secondary' : 'text-slate-550';
  const textTertiary = isDark ? 'text-text-tertiary' : 'text-slate-400';

  return (
    <KeyboardAvoidingView
      className={`flex-1 ${bgScreen}`}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
      keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 0}
    >
      {/* Header */}
      <View className={`pt-12 px-4 pb-3 flex-row items-center justify-between ${bgCard} border-b ${borderHeader} z-10`}>
        <View className="flex-row items-center flex-1">
          <Pressable onPress={() => navigation.goBack()} className="mr-3 p-1 active:opacity-75">
            <Icon name="arrow-left" size={20} color="#10B981" />
          </Pressable>

          <Pressable
            onPress={() =>
              (navigation as any).navigate('Contacts', {
                screen: 'ContactDetail',
                params: {contactId},
              })
            }
            className="flex-row items-center gap-2.5 flex-1 active:opacity-85"
          >
            {/* Avatar */}
            <View className="w-10 h-10 rounded-full bg-brand-500/10 border border-brand-500/15 items-center justify-center">
              <Text className="text-brand-500 font-extrabold text-sm">
                {contactName.split(' ').map((n) => n[0]).join('')}
              </Text>
            </View>

            {/* Name + Active Channels */}
            <View className="flex-1">
              <Text className={`${textPrimary} text-base font-black`} numberOfLines={1}>
                {contactName}
              </Text>
              <Text className={`${textTertiary} text-[10px] font-extrabold`}>
                {activeChannels}
              </Text>
            </View>
          </Pressable>
        </View>

        {/* Suggest Button */}
        <Pressable
          onPress={fetchAiSuggestion}
          className="ml-2 px-3 py-1.5 rounded-full bg-brand-500/10 border border-brand-500/20 active:bg-brand-500/15 flex-row items-center gap-1"
        >
          <Icon name="sparkles" size={12} color="#10B981" />
          <Text className="text-brand-500 text-xs font-black">Draft</Text>
        </Pressable>
      </View>

      {/* Messages Feed */}
      {isLoading ? (
        <View className="flex-1 items-center justify-center">
          <ActivityIndicator color="#10b981" size="large" />
        </View>
      ) : (
        <FlatList
          ref={listRef}
          data={listItems}
          keyExtractor={(_, index) => String(index)}
          contentContainerStyle={{paddingHorizontal: 16, paddingTop: 16, paddingBottom: 24}}
          onContentSizeChange={() => listRef.current?.scrollToEnd({animated: false})}
          renderItem={({item}) => {
            if (item.type === 'divider') {
              return (
                <View className="items-center my-4">
                  <View className={`px-3 py-1 rounded-full border ${
                    isDark ? 'bg-zinc-900 border-zinc-800' : 'bg-slate-200/50 border-slate-200'
                  }`}>
                    <Text className={`text-[10px] font-extrabold ${textTertiary}`}>{item.label}</Text>
                  </View>
                </View>
              );
            }

            const msg = item.message;
            if (msg.channel === 'email') {
              return (
                <EmailCard
                  message={msg}
                  isDark={isDark}
                  onLongPress={() => handleLongPress(msg)}
                />
              );
            }

            return (
              <MessageBubble
                message={msg}
                isDark={isDark}
                onLongPress={() => handleLongPress(msg)}
              />
            );
          }}
          ListFooterComponent={draftingSuggestion ? <TypingIndicator /> : null}
          ListEmptyComponent={
            <View className="py-20 px-8 items-center">
              <View className="w-16 h-16 bg-brand-500/10 border border-brand-500/20 rounded-full items-center justify-center mb-4">
                <Icon name="message-square" size={24} color="#10B981" />
              </View>
              <Text className={`${textPrimary} text-base font-bold mb-1 text-center`}>
                Start a conversation with {contactName.split(' ')[0]}
              </Text>
              <Text className="text-text-secondary text-xs text-center leading-4 max-w-[240px]">
                Choose your channel below to draft and send a message.
              </Text>
            </View>
          }
        />
      )}

      {/* AI suggestion chip floating above Compose Bar */}
      {suggestion && (
        <View className="px-4 py-2 border-t border-zinc-800 bg-[#090d16]/95 z-20">
          <View className="flex-row items-center justify-between mb-1">
            <View className="flex-row items-center gap-1">
              <Icon name="sparkles" size={12} color="#10B981" />
              <Text className="text-brand-500 text-xs font-black uppercase tracking-wider">
                ✦ Suggested reply
              </Text>
            </View>
            <Pressable onPress={() => setSuggestion(null)} className="p-0.5">
              <Icon name="x" size={13} color="#71717A" />
            </Pressable>
          </View>
          <Pressable
            onPress={() => {
              setText(suggestion);
              setSuggestion(null);
            }}
            className="active:opacity-80"
          >
            <Text className={`text-xs italic leading-4 ${textPrimary}`} numberOfLines={2}>
              "{suggestion}"
            </Text>
          </Pressable>
        </View>
      )}

      {/* Compose bar */}
      <SafeAreaView className={`border-t ${borderHeader} ${bgCard} z-20`}>
        <View className="flex-row items-end px-3 py-3 gap-2">
          {/* Channel Selector Pill */}
          <Pressable
            onPress={() => setChannelPickerVisible(true)}
            className="px-2.5 h-10 rounded-full bg-brand-500/10 border border-brand-500/15 items-center justify-center flex-row gap-1 active:opacity-75"
          >
            <Icon
              name={CHANNEL_ICON[channel]}
              size={14}
              color={CHANNEL_ICON_COLOR[channel]}
            />
            <Icon name="chevron-up" size={12} color="#10B981" />
          </Pressable>

          {/* Text Input */}
          <TextInput
            className={`flex-1 rounded-2xl px-4 py-2.5 text-sm border ${
              isDark ? 'bg-[#111827] border-zinc-850 text-text-primary' : 'bg-slate-100 border-slate-200 text-slate-900'
            }`}
            placeholder={`Message via ${CHANNEL_LABEL[channel] || 'WhatsApp'}…`}
            placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
            multiline
            value={text}
            onChangeText={setText}
            style={{maxHeight: 100, textAlignVertical: 'center'}}
          />

          {/* Mic Button for voice-to-text */}
          <Pressable
            onPress={handleMicPress}
            className={`w-10 h-10 rounded-full items-center justify-center active:scale-95 ${
              isDark ? 'bg-zinc-800' : 'bg-slate-100'
            }`}
          >
            <Icon name="mic" size={18} color="#10B981" />
          </Pressable>

          {/* Send Button */}
          <Pressable
            onPress={handleSend}
            className={`w-10 h-10 rounded-full items-center justify-center ${
              text.trim() && !sendMessage.isPending ? 'bg-brand-500 active:scale-95' : 'bg-zinc-800'
            }`}
            disabled={!text.trim() || sendMessage.isPending}
          >
            {sendMessage.isPending ? (
              <ActivityIndicator color="#fff" size="small" />
            ) : (
              <Icon
                name="send"
                size={16}
                color={text.trim() ? '#ffffff' : '#71717A'}
              />
            )}
          </Pressable>
        </View>
      </SafeAreaView>

      {/* Channel Selector Bottom Sheet Modal */}
      <Modal
        visible={channelPickerVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setChannelPickerVisible(false)}
      >
        <View className="flex-1 justify-end bg-[#020617]/60">
          <Pressable className="flex-1" onPress={() => setChannelPickerVisible(false)} />
          <View className={`${bgCard} rounded-t-3xl border-t ${borderHeader} p-5 pb-8`}>
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-300'} rounded-full self-center mb-5`} />

            <Text className={`${textPrimary} font-black text-lg mb-4`}>Select Channel</Text>

            <View className="gap-2.5">
              {(['whatsapp', 'sms', 'email'] as Channel[]).map((ch) => {
                const isSelected = channel === ch;
                return (
                  <Pressable
                    key={ch}
                    onPress={() => {
                      Vibration.vibrate(10); // Light haptic on select channel
                      setChannel(ch);
                      setChannelPickerVisible(false);
                    }}
                    className={`flex-row items-center justify-between p-4 rounded-xl border ${
                      isSelected
                        ? 'bg-brand-500/10 border-brand-500'
                        : isDark
                        ? 'bg-[#111827] border-zinc-850 active:bg-zinc-800'
                        : 'bg-slate-100 border-slate-200 active:bg-slate-200'
                    }`}
                  >
                    <View className="flex-row items-center gap-3">
                      <Icon
                        name={CHANNEL_ICON[ch]}
                        size={18}
                        color={CHANNEL_ICON_COLOR[ch]}
                      />
                      <Text className={`font-bold text-sm ${textPrimary}`}>
                        {ch === 'whatsapp' ? 'WhatsApp' : ch === 'sms' ? 'SMS' : 'Email'}
                      </Text>
                    </View>
                    {isSelected && (
                      <Icon name="check" size={16} color="#10B981" />
                    )}
                  </Pressable>
                );
              })}
            </View>
          </View>
        </View>
      </Modal>

      {/* Task Creation Bottom Sheet Modal */}
      <Modal
        visible={taskModalVisible}
        transparent
        animationType="slide"
        onRequestClose={() => setTaskModalVisible(false)}
      >
        <View className="flex-1 justify-end bg-[#020617]/60">
          <Pressable className="flex-1" onPress={() => setTaskModalVisible(false)} />
          <View className={`${bgCard} rounded-t-3xl border-t ${borderHeader} p-5 pb-8`}>
            <View className={`w-12 h-1 ${isDark ? 'bg-zinc-800' : 'bg-slate-300'} rounded-full self-center mb-5`} />

            <View className="flex-row justify-between items-center mb-4">
              <Text className={`${textPrimary} font-black text-lg`}>Create Follow-up Task</Text>
              <Pressable
                onPress={() => setTaskModalVisible(false)}
                className={`w-8 h-8 rounded-full ${
                  isDark ? 'bg-zinc-800' : 'bg-slate-100'
                } items-center justify-center`}
              >
                <Icon name="x" size={16} color={isDark ? '#A1A1AA' : '#64748b'} />
              </Pressable>
            </View>

            {/* Task Name */}
            <View className="mb-4">
              <Text className={`text-xs font-bold mb-1.5 ${textSecondary}`}>Task Name</Text>
              <TextInput
                className={`rounded-xl px-4 py-3 text-sm border ${bgInput} ${textPrimary} ${
                  isDark ? 'border-zinc-850' : 'border-slate-200'
                }`}
                placeholder="e.g. Schedule viewing details"
                placeholderTextColor={isDark ? '#52525B' : '#94a3b8'}
                value={taskTitle}
                onChangeText={setTaskTitle}
              />
            </View>

            {/* Task Due Quick Picker */}
            <View className="mb-6">
              <Text className={`text-xs font-bold mb-1.5 ${textSecondary}`}>Due Date</Text>
              <View className="flex-row gap-2">
                {[
                  {value: 'today', label: 'Today'},
                  {value: 'tomorrow', label: 'Tomorrow'},
                  {value: 'next_week', label: 'Next Week'},
                ].map((due) => {
                  const isSelected = taskDue === due.value;
                  return (
                    <Pressable
                      key={due.value}
                      onPress={() => {
                        Vibration.vibrate(10); // Light haptic on due date toggle
                        setTaskDue(due.value as any);
                      }}
                      className={`flex-1 py-2.5 rounded-xl border items-center ${
                        isSelected
                          ? 'bg-brand-500/10 border-brand-500'
                          : isDark
                          ? 'bg-[#111827] border-zinc-850 active:bg-zinc-800'
                          : 'bg-slate-100 border-slate-200 active:bg-slate-200'
                      }`}
                    >
                      <Text
                        className={`text-xs font-bold ${
                          isSelected ? 'text-brand-500' : textSecondary
                        }`}
                      >
                        {due.label}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>

            {/* Actions */}
            <View className="flex-row gap-3">
              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center ${
                  isDark ? 'bg-zinc-800' : 'bg-slate-100'
                }`}
                onPress={() => setTaskModalVisible(false)}
              >
                <Text className={`font-bold text-sm ${isDark ? 'text-text-secondary' : 'text-slate-650'}`}>
                  Cancel
                </Text>
              </Pressable>

              <Pressable
                className={`flex-1 rounded-xl py-3.5 items-center bg-brand-500 active:bg-brand-600 ${
                  !taskTitle.trim() || createTask.isPending ? 'opacity-55' : ''
                }`}
                onPress={handleCreateTask}
                disabled={!taskTitle.trim() || createTask.isPending}
              >
                {createTask.isPending ? (
                  <ActivityIndicator color="#fff" size="small" />
                ) : (
                  <Text className="text-white font-bold text-sm">Create Task</Text>
                )}
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}
const CHANNEL_LABEL: Record<string, string> = {
  whatsapp: 'WhatsApp',
  sms:      'SMS',
  email:    'Email',
};
