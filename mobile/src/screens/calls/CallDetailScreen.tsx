import React from 'react';
import {ActivityIndicator, Pressable, ScrollView, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useRoute, useNavigation, RouteProp} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {callsApi} from '../../api/calls';
import {SpeakerSegment} from '../../types';
import {format} from 'date-fns';
import type {CallsStackParamList} from '../../navigation/stacks/CallsStack';

type RoutePropType = RouteProp<CallsStackParamList, 'CallDetail'>;
type NavProp = NativeStackNavigationProp<CallsStackParamList>;

const SENTIMENT_COLORS: Record<string, string> = {
  hot: 'bg-red-500', warm: 'bg-amber-500', cold: 'bg-blue-500', neutral: 'bg-slate-500',
};

export function CallDetailScreen() {
  const route = useRoute<RoutePropType>();
  const navigation = useNavigation<NavProp>();
  const {callId} = route.params;

  const {data: call, isLoading} = useQuery({
    queryKey: ['call', callId],
    queryFn: () => callsApi.get(callId).then(r => r.data),
  });

  if (isLoading || !call) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  const {summary, transcript, contact} = call;

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="pb-10">
      <View className="pt-14 px-4 pb-4">
        <Pressable onPress={() => navigation.goBack()} className="mb-4">
          <Text className="text-brand-500">← Back</Text>
        </Pressable>

        <View className="flex-row items-center justify-between">
          <View>
            <Text className="text-white text-xl font-bold">
              {contact ? `${contact.first_name} ${contact.last_name}` : call.remote_number}
            </Text>
            <Text className="text-slate-400 text-sm">
              {call.started_at ? format(new Date(call.started_at), 'PPpp') : '—'} · {call.duration_formatted}
            </Text>
          </View>
          {summary && (
            <View className={`px-3 py-1.5 rounded-full ${SENTIMENT_COLORS[summary.sentiment]}`}>
              <Text className="text-white text-xs font-semibold capitalize">{summary.sentiment}</Text>
            </View>
          )}
        </View>
      </View>

      {/* Summary */}
      {summary && (
        <View className="px-4 mb-4">
          <View className="bg-surface-card rounded-xl p-4 mb-3">
            <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">
              Summary
            </Text>
            <Text className="text-slate-200 text-sm leading-5">{summary.summary_text}</Text>
          </View>

          {(summary.key_points ?? []).length > 0 && (
            <View className="bg-surface-card rounded-xl p-4 mb-3">
              <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">
                Key Points
              </Text>
              {summary.key_points.map((p, i) => (
                <View key={i} className="flex-row mb-1.5">
                  <Text className="text-brand-500 mr-2">•</Text>
                  <Text className="text-slate-200 text-sm flex-1">{p}</Text>
                </View>
              ))}
            </View>
          )}

          {(summary.action_items ?? []).length > 0 && (
            <View className="bg-surface-card rounded-xl p-4">
              <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">
                Action Items
              </Text>
              {summary.action_items.map((a, i) => (
                <View key={i} className="flex-row mb-1.5">
                  <Text className="text-slate-500 mr-2">☐</Text>
                  <Text className="text-slate-200 text-sm flex-1">{a}</Text>
                </View>
              ))}
            </View>
          )}
        </View>
      )}

      {/* Transcript */}
      {transcript && (
        <View className="px-4">
          <Text className="text-slate-400 text-xs uppercase tracking-wide font-semibold mb-2">
            Transcript
          </Text>
          <View className="bg-surface-card rounded-xl p-4">
            {(transcript.speaker_segments ?? []).map((seg: SpeakerSegment, i: number) => (
              <View key={i} className="mb-3">
                <Text className="text-brand-400 text-xs font-semibold mb-1">{seg.speaker}</Text>
                <Text className="text-slate-300 text-sm leading-5">{seg.text}</Text>
              </View>
            ))}
            {(!transcript.speaker_segments || transcript.speaker_segments.length === 0) && (
              <Text className="text-slate-400 text-sm">{transcript.full_text}</Text>
            )}
          </View>
        </View>
      )}
    </ScrollView>
  );
}
