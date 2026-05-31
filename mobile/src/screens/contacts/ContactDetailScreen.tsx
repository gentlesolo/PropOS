import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Pressable,
  SectionList,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {contactsApi} from '../../api/contacts';
import {briefApi, TimelineActivity} from '../../api/brief';
import {intelligenceApi} from '../../api/intelligence';
import {SentimentTrendChart} from '../../components/SentimentTrendChart';
import {format} from 'date-fns';
import type {ContactsStackParamList} from '../../navigation/stacks/ContactsStack';

type RoutePropType = RouteProp<ContactsStackParamList, 'ContactDetail'>;
type NavProp = NativeStackNavigationProp<ContactsStackParamList>;

const SENTIMENT_DOT: Record<string, string> = {
  hot: 'bg-red-500', warm: 'bg-amber-500', cold: 'bg-blue-400', neutral: 'bg-slate-500',
};

const ACTIVITY_ICON: Record<string, string> = {
  note:          '📝',
  call:          '📞',
  email:         '✉️',
  sms:           '📱',
  meeting:       '🤝',
  viewing:       '🏠',
  status_change: '🔄',
  system:        '⚙️',
};

function TimelineItem({activity}: {activity: TimelineActivity}) {
  const icon = ACTIVITY_ICON[activity.type] ?? '•';
  return (
    <View className="flex-row mb-4">
      <View className="w-8 h-8 rounded-full bg-surface-input items-center justify-center mr-3 mt-0.5">
        <Text style={{fontSize: 14}}>{icon}</Text>
      </View>
      <View className="flex-1">
        {activity.subject && (
          <Text className="text-white text-sm font-medium">{activity.subject}</Text>
        )}
        {activity.body && (
          <Text className="text-slate-300 text-sm mt-0.5 leading-5">{activity.body}</Text>
        )}
        <View className="flex-row items-center mt-1 gap-2">
          <Text className="text-slate-500 text-xs">
            {format(new Date(activity.occurred_at), 'MMM d, h:mm a')}
          </Text>
          {activity.user && (
            <Text className="text-slate-600 text-xs">
              · {activity.user.first_name}
            </Text>
          )}
        </View>
      </View>
    </View>
  );
}

export function ContactDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {contactId} = route.params;
  const queryClient = useQueryClient();

  const [tab, setTab] = useState<'overview' | 'timeline' | 'calls' | 'sentiment'>('overview');
  const [noteVisible, setNoteVisible] = useState(false);
  const [noteText, setNoteText] = useState('');

  const {data, isLoading} = useQuery({
    queryKey: ['contact', contactId],
    queryFn: () => contactsApi.get(contactId).then(r => r.data),
  });

  const {data: timeline, isLoading: timelineLoading} = useQuery({
    queryKey: ['timeline', contactId],
    queryFn: () => briefApi.timeline(contactId).then(r => r.data),
    enabled: tab === 'timeline',
  });

  const {data: sentimentPoints} = useQuery({
    queryKey: ['sentiment', contactId],
    queryFn: () => intelligenceApi.contactSentiment(contactId).then(r => r.data),
    enabled: tab === 'sentiment',
  });

  const addNote = useMutation({
    mutationFn: () => contactsApi.addNote(contactId, noteText),
    onSuccess: () => {
      setNoteText('');
      setNoteVisible(false);
      queryClient.invalidateQueries({queryKey: ['contact', contactId]});
      queryClient.invalidateQueries({queryKey: ['timeline', contactId]});
    },
    onError: () => Alert.alert('Error', 'Could not save note.'),
  });

  if (isLoading || !data) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  const {contact, recent_calls} = data;

  const handleCall = () => {
    if (!contact.phone) {
      Alert.alert('No phone number', 'This contact has no phone number on record.');
      return;
    }
    navigation.navigate('InCall', {contactId: contact.id, phoneNumber: contact.phone});
  };

  return (
    <View className="flex-1 bg-surface">
      {/* Header */}
      <View className="pt-14 px-4 pb-4">
        <Pressable onPress={() => navigation.goBack()} className="mb-4">
          <Text className="text-brand-500">← Back</Text>
        </Pressable>

        <View className="flex-row items-center">
          <View className="w-16 h-16 rounded-full bg-brand-700 items-center justify-center mr-4">
            <Text className="text-white text-2xl font-bold">
              {contact.first_name.charAt(0)}{contact.last_name.charAt(0)}
            </Text>
          </View>
          <View className="flex-1">
            <Text className="text-white text-xl font-bold">
              {contact.first_name} {contact.last_name}
            </Text>
            <Text className="text-slate-400 text-sm mt-0.5">
              {contact.phone ?? contact.email ?? 'No contact info'}
            </Text>
            <View className="flex-row items-center mt-1">
              <View className="w-2 h-2 rounded-full bg-green-500 mr-1.5" />
              <Text className="text-slate-400 text-xs capitalize">{contact.status}</Text>
            </View>
          </View>
        </View>

        {/* Quick actions */}
        <View className="flex-row gap-2 mt-4">
          <Pressable className="flex-1 bg-brand-600 rounded-xl py-3 items-center" onPress={handleCall}>
            <Text className="text-white font-semibold text-sm">📞 Call</Text>
          </Pressable>
          <Pressable
            className="flex-1 bg-surface-card rounded-xl py-3 items-center"
            onPress={() => navigation.navigate('InCall', {
              contactId: contact.id,
              phoneNumber: contact.phone ?? '',
            })}>
            <Text className="text-white font-semibold text-sm">💬 Message</Text>
          </Pressable>
          <Pressable
            className="flex-1 bg-surface-card rounded-xl py-3 items-center"
            onPress={() => setNoteVisible(true)}>
            <Text className="text-white font-semibold text-sm">📝 Note</Text>
          </Pressable>
        </View>
      </View>

      {/* Tab bar */}
      <View className="flex-row border-b border-slate-800 px-4">
        {(['overview', 'timeline', 'calls', 'sentiment'] as const).map(t => (
          <Pressable
            key={t}
            className={`mr-5 pb-2.5 border-b-2 ${
              tab === t ? 'border-brand-500' : 'border-transparent'
            }`}
            onPress={() => setTab(t)}>
            <Text className={`text-sm font-medium capitalize ${
              tab === t ? 'text-white' : 'text-slate-500'
            }`}>
              {t}
            </Text>
          </Pressable>
        ))}
      </View>

      {/* Tab content */}
      {tab === 'overview' && (
        <FlatList
          data={recent_calls}
          keyExtractor={c => String(c.id)}
          contentContainerClassName="px-4 pt-4"
          ListHeaderComponent={
            <View className="mb-4">
              <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-3">
                Recent Activity
              </Text>
            </View>
          }
          renderItem={({item}) => (
            <Pressable className="flex-row items-center py-3 border-b border-slate-800">
              <Text className="mr-3">📞</Text>
              <View className="flex-1">
                <Text className="text-white text-sm font-medium capitalize">{item.direction} call</Text>
                {item.summary && (
                  <Text className="text-slate-400 text-xs mt-0.5" numberOfLines={1}>
                    {item.summary.summary_text}
                  </Text>
                )}
              </View>
              <View className="items-end gap-1">
                <Text className="text-slate-500 text-xs">
                  {item.started_at ? format(new Date(item.started_at), 'd MMM') : '—'}
                </Text>
                {item.summary && (
                  <View className={`w-2 h-2 rounded-full ${SENTIMENT_DOT[item.summary.sentiment]}`} />
                )}
              </View>
            </Pressable>
          )}
          ListEmptyComponent={
            <View className="py-8 items-center">
              <Text className="text-slate-500 text-sm">No activity yet</Text>
            </View>
          }
        />
      )}

      {tab === 'timeline' && (
        <View className="flex-1 px-4 pt-4">
          {timelineLoading ? (
            <View className="flex-1 items-center justify-center">
              <ActivityIndicator color="#3b82f6" />
            </View>
          ) : (
            <FlatList
              data={timeline?.data ?? []}
              keyExtractor={a => String(a.id)}
              renderItem={({item}) => <TimelineItem activity={item} />}
              ListEmptyComponent={
                <View className="py-8 items-center">
                  <Text className="text-slate-500 text-sm">No timeline activities yet</Text>
                </View>
              }
            />
          )}
        </View>
      )}

      {tab === 'calls' && (
        <FlatList
          data={recent_calls}
          keyExtractor={c => String(c.id)}
          contentContainerClassName="px-4 pt-4"
          renderItem={({item}) => (
            <View className="flex-row items-center py-3 border-b border-slate-800">
              <Text className="mr-3 text-lg">{item.direction === 'inbound' ? '📲' : '📞'}</Text>
              <View className="flex-1">
                <Text className="text-white text-sm font-medium capitalize">{item.direction} call</Text>
                {item.summary ? (
                  <Text className="text-slate-400 text-xs mt-0.5" numberOfLines={1}>
                    {item.summary.summary_text}
                  </Text>
                ) : (
                  <Text className="text-slate-600 text-xs italic">No summary</Text>
                )}
              </View>
              <View className="items-end">
                <Text className="text-slate-400 text-xs">
                  {item.started_at ? format(new Date(item.started_at), 'd MMM') : '—'}
                </Text>
                <Text className="text-slate-500 text-xs">{item.duration_formatted}</Text>
              </View>
            </View>
          )}
          ListEmptyComponent={
            <View className="py-8 items-center">
              <Text className="text-slate-500 text-sm">No calls recorded yet</Text>
            </View>
          }
        />
      )}

      {tab === 'sentiment' && (
        <View className="flex-1 px-4 pt-4">
          <SentimentTrendChart points={sentimentPoints ?? []} />
          {(sentimentPoints ?? []).length > 0 && (
            <View className="mt-4">
              <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
                Call Log
              </Text>
              {sentimentPoints!.map(p => (
                <View key={p.call_id} className="flex-row items-center py-2 border-b border-slate-800">
                  <Text className="text-slate-400 text-xs w-20">{p.date ? format(new Date(p.date), 'MMM d') : '—'}</Text>
                  <Text className="text-slate-300 text-xs capitalize flex-1">{p.sentiment}</Text>
                  <Text className="text-slate-400 text-xs font-bold">{p.sentiment_score}</Text>
                </View>
              ))}
            </View>
          )}
        </View>
      )}

      {/* Note modal */}
      <Modal visible={noteVisible} transparent animationType="slide">
        <View className="flex-1 justify-end bg-black/50">
          <View className="bg-surface-card rounded-t-2xl p-5">
            <Text className="text-white font-semibold text-lg mb-3">Add Note</Text>
            <TextInput
              className="bg-surface text-white rounded-xl px-4 py-3 text-sm"
              placeholder="Type your note…"
              placeholderTextColor="#64748b"
              multiline
              numberOfLines={4}
              value={noteText}
              onChangeText={setNoteText}
              style={{minHeight: 80, textAlignVertical: 'top'}}
              autoFocus
            />
            <View className="flex-row gap-3 mt-4">
              <Pressable
                className="flex-1 bg-surface rounded-xl py-3 items-center"
                onPress={() => setNoteVisible(false)}>
                <Text className="text-slate-300">Cancel</Text>
              </Pressable>
              <Pressable
                className="flex-1 bg-brand-600 rounded-xl py-3 items-center"
                onPress={() => addNote.mutate()}
                disabled={!noteText.trim() || addNote.isPending}>
                {addNote.isPending
                  ? <ActivityIndicator color="#fff" size="small" />
                  : <Text className="text-white font-semibold">Save</Text>
                }
              </Pressable>
            </View>
          </View>
        </View>
      </Modal>
    </View>
  );
}
