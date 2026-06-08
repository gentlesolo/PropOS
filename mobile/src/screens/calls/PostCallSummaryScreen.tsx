import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  Text,
  TextInput,
  View,
} from 'react-native';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {callsApi} from '../../api/calls';
import {tasksApi} from '../../api/tasks';
import {CallSummary} from '../../types';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type RoutePropType = RouteProp<CallsStackParamList, 'PostCallSummary'>;
type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_COLORS: Record<string, string> = {
  hot:     'bg-red-500',
  warm:    'bg-amber-500',
  cold:    'bg-blue-500',
  neutral: 'bg-slate-500',
};

export function PostCallSummaryScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {callId} = route.params;
  const queryClient = useQueryClient();

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
    refetchInterval: query => (!query.state.data?.summary ? 5000 : false),
  });

  const summary = call?.summary;

  const [editedSummary, setEditedSummary] = useState('');
  const [checkedItems, setCheckedItems] = useState<Record<number, boolean>>({});

  // Initialise editable summary when data loads
  React.useEffect(() => {
    if (summary && !editedSummary) {
      setEditedSummary(summary.summary_text);
    }
  }, [summary]);

  const confirm = useMutation({
    mutationFn: async () => {
      const checkedActionItems = (summary?.action_items ?? []).filter(
        (_, i) => checkedItems[i] !== false,
      );

      // Confirm/save summary
      await callsApi.confirmSummary(callId, {
        summary_text: editedSummary,
        action_items: summary?.action_items,
        suggested_next_step: summary?.suggested_next_step,
      });

      // Create a task for each checked action item
      await Promise.all(
        checkedActionItems.map(title =>
          tasksApi.store({
            title,
            contact_id: call?.contact_id,
            call_id: callId,
          }),
        ),
      );
    },
    onSuccess: () => {
      queryClient.invalidateQueries({queryKey: ['tasks']});
      navigation.navigate('CallHistory');
    },
    onError: () => {
      Alert.alert('Error', 'Could not save summary. Please try again.');
    },
  });

  if (isLoading) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" size="large" />
        <Text className="text-slate-400 mt-4">Loading call details…</Text>
      </View>
    );
  }

  if (!summary) {
    return (
      <View className="flex-1 bg-surface items-center justify-center px-6">
        <ActivityIndicator color="#3b82f6" size="large" />
        <Text className="text-white text-lg font-semibold mt-4">Generating summary…</Text>
        <Text className="text-slate-400 text-sm mt-2 text-center">
          AI is transcribing and summarising your call. This takes about 60 seconds.
        </Text>
      </View>
    );
  }

  const contact = call?.contact;
  const duration = call?.duration_formatted ?? '—';

  return (
    <ScrollView
      className="flex-1 bg-surface"
      contentContainerClassName="px-4 pt-14 pb-10">

      {/* Header */}
      <View className="flex-row items-center justify-between mb-6">
        <View>
          <Text className="text-white text-xl font-bold">
            {contact ? `${contact.first_name} ${contact.last_name}` : 'Call Summary'}
          </Text>
          <Text className="text-slate-400 text-sm mt-0.5">{duration} · Just now</Text>
        </View>
        <View className={`px-3 py-1.5 rounded-full ${SENTIMENT_COLORS[summary.sentiment]}`}>
          <Text className="text-white text-xs font-semibold capitalize">{summary.sentiment}</Text>
        </View>
      </View>

      {/* Summary */}
      <View className="bg-surface-card rounded-xl p-4 mb-4">
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
          Summary
        </Text>
        <TextInput
          className="text-white text-sm leading-5"
          multiline
          value={editedSummary}
          onChangeText={setEditedSummary}
          style={{minHeight: 80}}
        />
      </View>

      {/* Key points */}
      {(summary.key_points ?? []).length > 0 && (
        <View className="bg-surface-card rounded-xl p-4 mb-4">
          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
            Key Points
          </Text>
          {summary.key_points.map((point, i) => (
            <View key={i} className="flex-row items-start mb-1.5">
              <Text className="text-brand-500 mr-2 mt-0.5">•</Text>
              <Text className="text-slate-200 text-sm flex-1">{point}</Text>
            </View>
          ))}
        </View>
      )}

      {/* Action items */}
      {(summary.action_items ?? []).length > 0 && (
        <View className="bg-surface-card rounded-xl p-4 mb-4">
          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
            Action Items — Create as Tasks?
          </Text>
          {summary.action_items.map((item, i) => {
            const checked = checkedItems[i] !== false;
            return (
              <Pressable
                key={i}
                className="flex-row items-center mb-3"
                onPress={() => setCheckedItems(prev => ({...prev, [i]: !checked}))}>
                <View
                  className={`w-5 h-5 rounded mr-3 border ${
                    checked
                      ? 'bg-brand-600 border-brand-600'
                      : 'border-slate-600'
                  } items-center justify-center`}>
                  {checked && <Text className="text-white text-xs">✓</Text>}
                </View>
                <Text className="text-slate-200 text-sm flex-1">{item}</Text>
              </Pressable>
            );
          })}
        </View>
      )}

      {/* Suggested next step */}
      {summary.suggested_next_step && (
        <View className="bg-brand-900 border border-brand-700 rounded-xl p-4 mb-6">
          <Text className="text-brand-300 text-xs font-semibold uppercase tracking-wide mb-1">
            Suggested Next Step
          </Text>
          <Text className="text-slate-200 text-sm">{summary.suggested_next_step}</Text>
        </View>
      )}

      {/* CTA */}
      <Pressable
        className="bg-brand-600 rounded-xl py-4 items-center"
        onPress={() => confirm.mutate()}
        disabled={confirm.isPending}>
        {confirm.isPending ? (
          <ActivityIndicator color="#fff" />
        ) : (
          <Text className="text-white font-semibold text-base">Confirm &amp; Create Tasks</Text>
        )}
      </Pressable>

      <Pressable
        className="py-3 items-center mt-2"
        onPress={() => navigation.navigate('CallHistory')}>
        <Text className="text-slate-400 text-sm">Skip</Text>
      </Pressable>
    </ScrollView>
  );
}
