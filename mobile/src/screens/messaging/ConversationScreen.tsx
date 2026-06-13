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
  Modal,
  Vibration,
} from 'react-native';
import {SafeAreaView} from 'react-native-safe-area-context';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {messagingApi, Message} from '../../api/messaging';
import {intelligenceApi} from '../../api/intelligence';
import {tasksApi} from '../../api/tasks';
import {format, isToday, isYesterday} from 'date-fns';
import type {MessagingStackParamList} from '../../navigation/stacks/MessagingStack';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../../theme/ThemeProvider';
import {ThemeTokens} from '../../theme/tokens';

type RoutePropType = RouteProp<MessagingStackParamList, 'Conversation'>;
type NavProp = NativeStackNavigationProp<MessagingStackParamList>;
type Channel = 'whatsapp' | 'sms' | 'email';

const CHANNEL_ICON: Record<string, string> = {
  whatsapp: 'message-circle',
  sms:      'message-square',
  email:    'mail',
};

const CHANNEL_COLOR: Record<string, string> = {
  whatsapp: '#25D366',
  sms:      '#10B981',
  email:    '#0EA5E9',
};

const CHANNEL_LABEL: Record<string, string> = {
  whatsapp: 'WhatsApp',
  sms:      'SMS',
  email:    'Email',
};

const cleanEmailText = (rawText?: string) => {
  if (!rawText) return '';
  let clean = rawText.replace(/<[^>]*>/g, '');
  clean = clean.split(/--\s*$/m)[0];
  clean = clean.split(/Best regards/i)[0];
  clean = clean.split(/Sincerely/i)[0];
  clean = clean.split(/Kind regards/i)[0];
  return clean.trim();
};

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

function EmailCard({message, tokens, onLongPress}: {message: Message; tokens: ThemeTokens; onLongPress: () => void}) {
  const [expanded, setExpanded] = useState(false);
  const cleanBody = useMemo(() => cleanEmailText(message.body_text), [message.body_text]);

  return (
    <Pressable
      onLongPress={onLongPress}
      style={({pressed}) => ({borderRadius: 16, padding: 16, borderWidth: 1, marginBottom: 16, width: '85%', alignSelf: 'flex-start', backgroundColor: tokens.surfaceCard, borderColor: tokens.borderDefault, opacity: pressed ? 0.95 : 1})}
    >
      <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 8}}>
        <Icon name="mail" size={13} color="#0EA5E9" />
        <Text style={{fontSize: 10, fontWeight: '800', textTransform: 'uppercase', letterSpacing: 1, color: '#0EA5E9'}}>Email Card</Text>
      </View>
      <Text style={{fontWeight: '900', fontSize: 14, marginBottom: 6, color: tokens.textPrimary}}>Subject: {message.body}</Text>
      <Text style={{fontSize: 12, lineHeight: 20, color: tokens.textSecondary}} numberOfLines={expanded ? undefined : 3}>
        {cleanBody || '(No content preview)'}
      </Text>
      {cleanBody.length > 120 && (
        <Pressable onPress={() => setExpanded(!expanded)} style={{marginTop: 10, alignSelf: 'flex-start'}}>
          <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12, textDecorationLine: 'underline'}}>
            {expanded ? 'Collapse' : 'View full email'}
          </Text>
        </Pressable>
      )}
    </Pressable>
  );
}

function TypingIndicator() {
  const {tokens} = useTheme();
  const dot1 = useRef(new Animated.Value(0)).current;
  const dot2 = useRef(new Animated.Value(0)).current;
  const dot3 = useRef(new Animated.Value(0)).current;

  useEffect(() => {
    const animateDot = (dot: Animated.Value, delay: number) =>
      Animated.loop(Animated.sequence([
        Animated.delay(delay),
        Animated.timing(dot, {toValue: -5, duration: 300, useNativeDriver: true}),
        Animated.timing(dot, {toValue: 0,  duration: 300, useNativeDriver: true}),
        Animated.delay(600 - delay),
      ]));

    const a1 = animateDot(dot1, 0);
    const a2 = animateDot(dot2, 150);
    const a3 = animateDot(dot3, 300);
    a1.start(); a2.start(); a3.start();
    return () => { a1.stop(); a2.stop(); a3.stop(); };
  }, []);

  return (
    <View style={{flexDirection: 'row', alignItems: 'center', gap: 8, paddingHorizontal: 12, paddingVertical: 8, borderRadius: 16, maxWidth: 150, marginBottom: 12, marginLeft: 8, alignSelf: 'flex-start', backgroundColor: tokens.surfaceRaised}}>
      <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
        {[dot1, dot2, dot3].map((dot, i) => (
          <Animated.View key={i} style={{transform: [{translateY: dot}], width: 6, height: 6, borderRadius: 3, backgroundColor: tokens.brandPrimary}} />
        ))}
      </View>
      <Text style={{fontSize: 10, color: tokens.textTertiary, fontWeight: '700'}}>Drafting reply...</Text>
    </View>
  );
}

function MessageBubble({message, tokens, onLongPress}: {message: Message; tokens: ThemeTokens; onLongPress: () => void}) {
  const isOut = message.direction === 'outbound';
  const isSending = message.status === 'sending';
  const isFailed = message.status === 'failed';

  const bubbleContent = (
    <Pressable
      onLongPress={onLongPress}
      style={{marginBottom: 14, maxWidth: '78%', alignSelf: isOut ? 'flex-end' : 'flex-start'}}
    >
      <View
        style={{
          borderRadius: 16,
          borderTopRightRadius: isOut ? 4 : 16,
          borderTopLeftRadius: isOut ? 16 : 4,
          paddingHorizontal: 14,
          paddingVertical: 10,
          ...(isOut
            ? {backgroundColor: tokens.brandPrimary}
            : {backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault}
          ),
        }}
      >
        <Text style={{fontSize: 14, lineHeight: 20, color: isOut ? '#ffffff' : tokens.textPrimary, fontWeight: isOut ? '500' : '400'}}>
          {message.body}
        </Text>
        <View
          style={{
            position: 'absolute',
            bottom: 4,
            width: 8,
            height: 8,
            borderRadius: 4,
            [isOut ? 'left' : 'right']: -4,
            backgroundColor: CHANNEL_COLOR[message.channel] || tokens.brandPrimary,
          }}
        />
      </View>
      {isOut && (
        <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'flex-end', marginTop: 4, gap: 6}}>
          {isSending ? (
            <Icon name="clock" size={10} color={tokens.textTertiary} />
          ) : isFailed ? (
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 2}}>
              <Icon name="alert-circle" size={10} color="#F43F5E" />
              <Text style={{fontSize: 8, color: '#F43F5E', fontWeight: '700'}}>Failed</Text>
            </View>
          ) : (
            <Icon name="check" size={11} color={message.status === 'read' ? '#10B981' : tokens.textTertiary} />
          )}
        </View>
      )}
    </Pressable>
  );

  return isOut ? bubbleContent : <FadeInUpView>{bubbleContent}</FadeInUpView>;
}

export function ConversationScreen() {
  const {tokens} = useTheme();
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId, contactName} = route.params;
  const queryClient = useQueryClient();

  const [text, setText] = useState('');
  const [channel, setChannel] = useState<Channel>('whatsapp');
  const [channelPickerVisible, setChannelPickerVisible] = useState(false);
  const [suggestion, setSuggestion] = useState<string | null>(null);
  const [draftingSuggestion, setDraftingSuggestion] = useState(false);
  const [pendingMessages, setPendingMessages] = useState<Message[]>([]);
  const [taskModalVisible, setTaskModalVisible] = useState(false);
  const [taskTitle, setTaskTitle] = useState('');
  const [taskDue, setTaskDue] = useState<'today' | 'tomorrow' | 'next_week'>('tomorrow');

  const listRef = useRef<FlatList>(null);

  const {data, isLoading} = useQuery({
    queryKey: ['thread', contactId],
    queryFn: () => messagingApi.thread(contactId).then((r) => r.data),
    refetchInterval: 12_000,
  });

  const messages = data?.messages ?? [];

  const activeChannels = useMemo(() => {
    const channels = new Set<string>();
    messages.forEach((m) => {
      if (m.channel) channels.add(m.channel === 'whatsapp' ? 'WhatsApp' : m.channel === 'sms' ? 'SMS' : 'Email');
    });
    if (channels.size === 0) return 'WhatsApp';
    return Array.from(channels).join(' · ');
  }, [messages]);

  useEffect(() => {
    if (messages.length > 0) {
      const lastInbound = [...messages].reverse().find((m) => m.direction === 'inbound');
      if (lastInbound?.channel) setChannel(lastInbound.channel);
    }
  }, [messages.length]);

  const allMessages = useMemo(
    () => [...messages, ...pendingMessages].sort((a, b) => new Date(a.created_at).getTime() - new Date(b.created_at).getTime()),
    [messages, pendingMessages],
  );

  const listItems = useMemo(() => {
    const result: Array<{type: 'divider'; label: string} | {type: 'message'; message: Message}> = [];
    let lastTime: Date | null = null;
    allMessages.forEach((msg) => {
      const msgTime = new Date(msg.created_at);
      if (!lastTime || msgTime.getTime() - lastTime.getTime() > 15 * 60 * 1000) {
        let label = '';
        if (isToday(msgTime)) label = `Today, ${format(msgTime, 'h:mm a')}`;
        else if (isYesterday(msgTime)) label = `Yesterday, ${format(msgTime, 'h:mm a')}`;
        else label = format(msgTime, 'd MMM yyyy, h:mm a');
        result.push({type: 'divider', label});
      }
      result.push({type: 'message', message: msg});
      lastTime = msgTime;
    });
    return result;
  }, [allMessages]);

  const sendMessage = useMutation({
    mutationFn: (payload: {body: string; channel: Channel; tempId: number}) =>
      messagingApi.send(contactId, payload.body, payload.channel),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({queryKey: ['thread', contactId]});
      queryClient.invalidateQueries({queryKey: ['inbox']});
      setPendingMessages((prev) => prev.filter((m) => m.id !== variables.tempId));
    },
    onError: (_, variables) => {
      setPendingMessages((prev) => prev.map((m) => (m.id === variables.tempId ? {...m, status: 'failed'} : m)));
    },
  });

  const handleSend = () => {
    const trimmed = text.trim();
    if (!trimmed) return;
    Vibration.vibrate(10);
    const tempId = -Date.now();
    const tempMsg: Message = {id: tempId, body: trimmed, channel, direction: 'outbound', status: 'sending', created_at: new Date().toISOString()};
    setPendingMessages((prev) => [...prev, tempMsg]);
    setText('');
    setSuggestion(null);
    sendMessage.mutate({body: trimmed, channel, tempId});
    setTimeout(() => listRef.current?.scrollToEnd({animated: true}), 100);
  };

  const fetchAiSuggestion = async () => {
    const lastInbound = [...messages].reverse().find((m) => m.direction === 'inbound');
    if (!lastInbound) { Alert.alert('No message', 'No incoming messages found to draft a reply for.'); return; }
    setDraftingSuggestion(true);
    try {
      const {data: result} = await intelligenceApi.suggestReply(lastInbound.body, channel, contactId);
      if (result?.suggestion) setSuggestion(result.suggestion);
    } catch {
      Alert.alert('AI Error', 'Could not fetch suggestion at this time.');
    } finally {
      setDraftingSuggestion(false);
    }
  };

  const createTask = useMutation({
    mutationFn: (payload: {title: string; contact_id: number; due_at: string}) => tasksApi.store(payload),
    onSuccess: () => { Alert.alert('Task Created', 'Follow-up task created successfully.'); setTaskModalVisible(false); setTaskTitle(''); },
    onError: () => Alert.alert('Error', 'Could not schedule follow-up task.'),
  });

  const handleCreateTask = () => {
    if (!taskTitle.trim()) { Alert.alert('Required', 'Please enter a task name.'); return; }
    const dueTime = new Date();
    if (taskDue === 'tomorrow') dueTime.setDate(dueTime.getDate() + 1);
    else if (taskDue === 'next_week') dueTime.setDate(dueTime.getDate() + 7);
    createTask.mutate({title: taskTitle.trim(), contact_id: contactId, due_at: dueTime.toISOString()});
  };

  const handleLongPress = (msg: Message) => {
    Alert.alert('Message Options', 'Choose an option', [
      {text: 'Copy Message', onPress: () => Alert.alert('Success', 'Copied to clipboard.')},
      {text: 'Create Task from this', onPress: () => { setTaskTitle(`Follow up: "${msg.body.substring(0, 30)}..."`); setTaskModalVisible(true); }},
      {text: 'Translate', onPress: () => Alert.alert('Translated', 'Standard English translation completed.')},
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  const handleMicPress = () => {
    Alert.alert('Voice Note', 'Transcribing speech to text...', [
      {text: 'Simulate Speech', onPress: () => setText('I can check the properties for you on Tuesday afternoon.')},
      {text: 'Cancel', style: 'cancel'},
    ]);
  };

  const sheetStyle = {
    backgroundColor: tokens.surfaceCard,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    borderTopWidth: 1,
    borderTopColor: tokens.borderDefault,
    padding: 20,
    paddingBottom: 32,
  };

  return (
    <KeyboardAvoidingView
      style={{flex: 1, backgroundColor: tokens.surfacePage}}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}
    >
      {/* Header */}
      <View style={{paddingTop: 48, paddingHorizontal: 16, paddingBottom: 12, flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', borderBottomWidth: 1, zIndex: 10, backgroundColor: tokens.surfaceCard, borderBottomColor: tokens.borderDefault}}>
        <View style={{flexDirection: 'row', alignItems: 'center', flex: 1}}>
          <Pressable onPress={() => navigation.goBack()} style={{marginRight: 12, padding: 4}}>
            <Icon name="arrow-left" size={20} color={tokens.brandPrimary} />
          </Pressable>
          <Pressable
            onPress={() => (navigation as any).navigate('Contacts', {screen: 'ContactDetail', params: {contactId}})}
            style={{flexDirection: 'row', alignItems: 'center', gap: 10, flex: 1}}
          >
            <View style={{width: 40, height: 40, borderRadius: 20, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}26`, alignItems: 'center', justifyContent: 'center'}}>
              <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 14}}>
                {contactName.split(' ').map((n) => n[0]).join('')}
              </Text>
            </View>
            <View style={{flex: 1}}>
              <Text style={{fontSize: 16, fontWeight: '900', color: tokens.textPrimary}} numberOfLines={1}>{contactName}</Text>
              <Text style={{fontSize: 10, fontWeight: '800', color: tokens.textTertiary}}>{activeChannels}</Text>
            </View>
          </Pressable>
        </View>
        <Pressable
          onPress={fetchAiSuggestion}
          style={{marginLeft: 8, paddingHorizontal: 12, paddingVertical: 6, borderRadius: 999, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, flexDirection: 'row', alignItems: 'center', gap: 4}}
        >
          <Icon name="zap" size={12} color={tokens.brandPrimary} />
          <Text style={{color: tokens.brandPrimary, fontSize: 12, fontWeight: '900'}}>Draft</Text>
        </Pressable>
      </View>

      {/* Messages Feed */}
      {isLoading ? (
        <View style={{flex: 1, alignItems: 'center', justifyContent: 'center'}}>
          <ActivityIndicator color={tokens.brandPrimary} size="large" />
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
                <View style={{alignItems: 'center', marginVertical: 16}}>
                  <View style={{paddingHorizontal: 12, paddingVertical: 4, borderRadius: 999, borderWidth: 1, backgroundColor: tokens.surfaceRaised, borderColor: tokens.borderDefault}}>
                    <Text style={{fontSize: 10, fontWeight: '800', color: tokens.textTertiary}}>{item.label}</Text>
                  </View>
                </View>
              );
            }
            const msg = item.message;
            if (msg.channel === 'email') return <EmailCard message={msg} tokens={tokens} onLongPress={() => handleLongPress(msg)} />;
            return <MessageBubble message={msg} tokens={tokens} onLongPress={() => handleLongPress(msg)} />;
          }}
          ListFooterComponent={draftingSuggestion ? <TypingIndicator /> : null}
          ListEmptyComponent={
            <View style={{paddingVertical: 80, paddingHorizontal: 32, alignItems: 'center'}}>
              <View style={{width: 64, height: 64, borderRadius: 32, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}33`, alignItems: 'center', justifyContent: 'center', marginBottom: 16}}>
                <Icon name="message-square" size={24} color={tokens.brandPrimary} />
              </View>
              <Text style={{fontSize: 16, fontWeight: '700', marginBottom: 4, textAlign: 'center', color: tokens.textPrimary}}>Start a conversation with {contactName.split(' ')[0]}</Text>
              <Text style={{fontSize: 12, textAlign: 'center', lineHeight: 16, maxWidth: 240, color: tokens.textSecondary}}>Choose your channel below to draft and send a message.</Text>
            </View>
          }
        />
      )}

      {/* AI suggestion strip */}
      {suggestion && (
        <View style={{paddingHorizontal: 16, paddingVertical: 8, borderTopWidth: 1, zIndex: 20, borderTopColor: tokens.borderDefault, backgroundColor: tokens.surfaceCard}}>
          <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 4}}>
            <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
              <Icon name="zap" size={12} color={tokens.brandPrimary} />
              <Text style={{color: tokens.brandPrimary, fontSize: 10, fontWeight: '900', textTransform: 'uppercase', letterSpacing: 1}}>✦ Suggested reply</Text>
            </View>
            <Pressable onPress={() => setSuggestion(null)} style={{padding: 2}}>
              <Icon name="x" size={13} color={tokens.textTertiary} />
            </Pressable>
          </View>
          <Pressable onPress={() => { setText(suggestion); setSuggestion(null); }}>
            <Text style={{fontSize: 12, fontStyle: 'italic', lineHeight: 16, color: tokens.textPrimary}} numberOfLines={2}>"{suggestion}"</Text>
          </Pressable>
        </View>
      )}

      {/* Compose bar */}
      <SafeAreaView style={{borderTopWidth: 1, borderTopColor: tokens.borderDefault, zIndex: 20, backgroundColor: tokens.surfaceCard}}>
        <View style={{flexDirection: 'row', alignItems: 'flex-end', paddingHorizontal: 12, paddingVertical: 12, gap: 8}}>
          {/* Channel selector */}
          <Pressable
            onPress={() => setChannelPickerVisible(true)}
            style={{paddingHorizontal: 10, height: 40, borderRadius: 999, backgroundColor: `${tokens.brandPrimary}1A`, borderWidth: 1, borderColor: `${tokens.brandPrimary}26`, alignItems: 'center', justifyContent: 'center', flexDirection: 'row', gap: 4}}
          >
            <Icon name={CHANNEL_ICON[channel]} size={14} color={CHANNEL_COLOR[channel]} />
            <Icon name="chevron-up" size={12} color={tokens.brandPrimary} />
          </Pressable>

          {/* Text input */}
          <TextInput
            style={{flex: 1, borderRadius: 20, paddingHorizontal: 16, paddingVertical: 10, fontSize: 14, borderWidth: 1, maxHeight: 100, textAlignVertical: 'center', backgroundColor: tokens.surfaceInput, borderColor: tokens.borderDefault, color: tokens.textPrimary}}
            placeholder={`Message via ${CHANNEL_LABEL[channel] || 'WhatsApp'}…`}
            placeholderTextColor={tokens.textTertiary}
            multiline
            value={text}
            onChangeText={setText}
          />

          {/* Mic */}
          <Pressable onPress={handleMicPress} style={{width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center', backgroundColor: tokens.surfaceRaised}}>
            <Icon name="mic" size={18} color={tokens.brandPrimary} />
          </Pressable>

          {/* Send */}
          <Pressable
            onPress={handleSend}
            style={{width: 40, height: 40, borderRadius: 20, alignItems: 'center', justifyContent: 'center', backgroundColor: text.trim() && !sendMessage.isPending ? tokens.brandPrimary : tokens.surfaceRaised}}
            disabled={!text.trim() || sendMessage.isPending}
          >
            {sendMessage.isPending
              ? <ActivityIndicator color="#fff" size="small" />
              : <Icon name="send" size={16} color={text.trim() ? '#ffffff' : tokens.textTertiary} />
            }
          </Pressable>
        </View>
      </SafeAreaView>

      {/* Channel Picker Modal */}
      <Modal visible={channelPickerVisible} transparent animationType="slide" onRequestClose={() => setChannelPickerVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setChannelPickerVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 20}} />
            <Text style={{fontSize: 18, fontWeight: '900', marginBottom: 16, color: tokens.textPrimary}}>Select Channel</Text>
            <View style={{gap: 10}}>
              {(['whatsapp', 'sms', 'email'] as Channel[]).map((ch) => {
                const isSelected = channel === ch;
                return (
                  <Pressable
                    key={ch}
                    onPress={() => { Vibration.vibrate(10); setChannel(ch); setChannelPickerVisible(false); }}
                    style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', padding: 16, borderRadius: 12, borderWidth: 1, backgroundColor: isSelected ? `${tokens.brandPrimary}0D` : tokens.surfaceRaised, borderColor: isSelected ? tokens.brandPrimary : tokens.borderDefault}}
                  >
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 12}}>
                      <Icon name={CHANNEL_ICON[ch]} size={18} color={CHANNEL_COLOR[ch]} />
                      <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textPrimary}}>{CHANNEL_LABEL[ch]}</Text>
                    </View>
                    {isSelected && <Icon name="check" size={16} color={tokens.brandPrimary} />}
                  </Pressable>
                );
              })}
            </View>
          </View>
        </View>
      </Modal>

      {/* Task Creation Modal */}
      <Modal visible={taskModalVisible} transparent animationType="slide" onRequestClose={() => setTaskModalVisible(false)}>
        <View style={{flex: 1, justifyContent: 'flex-end', backgroundColor: 'rgba(2,6,23,0.6)'}}>
          <Pressable style={{flex: 1}} onPress={() => setTaskModalVisible(false)} />
          <View style={sheetStyle}>
            <View style={{width: 48, height: 4, backgroundColor: tokens.borderStrong, borderRadius: 999, alignSelf: 'center', marginBottom: 20}} />
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 16}}>
              <Text style={{fontSize: 18, fontWeight: '900', color: tokens.textPrimary}}>Create Follow-up Task</Text>
              <Pressable onPress={() => setTaskModalVisible(false)} style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised, alignItems: 'center', justifyContent: 'center'}}>
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            <Text style={{fontSize: 12, fontWeight: '700', marginBottom: 6, color: tokens.textSecondary}}>Task Name</Text>
            <TextInput
              style={{borderRadius: 12, paddingHorizontal: 16, paddingVertical: 12, fontSize: 14, borderWidth: 1, marginBottom: 16, backgroundColor: tokens.surfaceInput, color: tokens.textPrimary, borderColor: tokens.borderDefault}}
              placeholder="e.g. Schedule viewing details"
              placeholderTextColor={tokens.textTertiary}
              value={taskTitle}
              onChangeText={setTaskTitle}
            />

            <Text style={{fontSize: 12, fontWeight: '700', marginBottom: 6, color: tokens.textSecondary}}>Due Date</Text>
            <View style={{flexDirection: 'row', gap: 8, marginBottom: 24}}>
              {([{value: 'today', label: 'Today'}, {value: 'tomorrow', label: 'Tomorrow'}, {value: 'next_week', label: 'Next Week'}] as const).map((due) => {
                const isSelected = taskDue === due.value;
                return (
                  <Pressable
                    key={due.value}
                    onPress={() => { Vibration.vibrate(10); setTaskDue(due.value); }}
                    style={{flex: 1, paddingVertical: 10, borderRadius: 12, borderWidth: 1, alignItems: 'center', backgroundColor: isSelected ? `${tokens.brandPrimary}0D` : tokens.surfaceRaised, borderColor: isSelected ? tokens.brandPrimary : tokens.borderDefault}}
                  >
                    <Text style={{fontSize: 12, fontWeight: '700', color: isSelected ? tokens.brandPrimary : tokens.textSecondary}}>{due.label}</Text>
                  </Pressable>
                );
              })}
            </View>

            <View style={{flexDirection: 'row', gap: 12}}>
              <Pressable style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.surfaceRaised}} onPress={() => setTaskModalVisible(false)}>
                <Text style={{fontWeight: '700', fontSize: 14, color: tokens.textSecondary}}>Cancel</Text>
              </Pressable>
              <Pressable
                style={{flex: 1, borderRadius: 12, paddingVertical: 14, alignItems: 'center', backgroundColor: tokens.brandPrimary, opacity: (!taskTitle.trim() || createTask.isPending) ? 0.55 : 1}}
                onPress={handleCreateTask}
                disabled={!taskTitle.trim() || createTask.isPending}
              >
                {createTask.isPending
                  ? <ActivityIndicator color="#fff" size="small" />
                  : <Text style={{color: '#ffffff', fontWeight: '700', fontSize: 14}}>Create Task</Text>
                }
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </KeyboardAvoidingView>
  );
}
