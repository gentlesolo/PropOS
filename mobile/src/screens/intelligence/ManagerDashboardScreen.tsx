import React, {useState} from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Pressable,
  ScrollView,
  SectionList,
  Text,
  View,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {intelligenceApi, TeamAnalytics} from '../../api/intelligence';
import {format} from 'date-fns';
import type {IntelligenceStackParamList} from '../../navigation/stacks/IntelligenceStack';

type NavProp = NativeStackNavigationProp<IntelligenceStackParamList>;

function formatDuration(secs: number): string {
  const h = Math.floor(secs / 3600);
  const m = Math.floor((secs % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

const SENTIMENT_COLORS: Record<string, string> = {
  hot: 'text-red-400', warm: 'text-amber-400', cold: 'text-blue-400', neutral: 'text-slate-400',
};

function AgentRow({stat}: {stat: TeamAnalytics['agent_stats'][0]}) {
  const agent = stat.agent;
  const initials = agent.first_name.charAt(0) + agent.last_name.charAt(0);
  return (
    <View className="flex-row items-center py-3 border-b border-slate-800">
      <View className="w-9 h-9 rounded-full bg-brand-700 items-center justify-center mr-3">
        <Text className="text-white text-sm font-semibold">{initials}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-white text-sm font-medium">
          {agent.first_name} {agent.last_name}
        </Text>
        <Text className="text-slate-500 text-xs">
          Avg {formatDuration(stat.avg_duration)} per call
        </Text>
      </View>
      <View className="items-end">
        <Text className="text-white font-bold text-base">{stat.call_count}</Text>
        <Text className="text-slate-500 text-xs">{formatDuration(stat.total_duration)}</Text>
      </View>
    </View>
  );
}

function FlaggedCallRow({
  call,
  onUnflag,
}: {
  call: TeamAnalytics['flagged_calls'][0];
  onUnflag: () => void;
}) {
  const [expanded, setExpanded] = useState(false);
  return (
    <View className="bg-surface-card rounded-xl p-4 mb-3">
      <Pressable onPress={() => setExpanded(v => !v)}>
        <View className="flex-row items-start justify-between">
          <View className="flex-1">
            <Text className="text-white font-medium text-sm">
              {call.agent?.first_name} {call.agent?.last_name}
            </Text>
            {call.contact && (
              <Text className="text-slate-400 text-xs mt-0.5">
                with {call.contact.first_name} {call.contact.last_name}
              </Text>
            )}
            {call.started_at && (
              <Text className="text-slate-500 text-xs mt-0.5">
                {format(new Date(call.started_at), 'PPp')}
              </Text>
            )}
          </View>
          {call.summary && (
            <Text className={`text-xs font-semibold capitalize ${SENTIMENT_COLORS[call.summary.sentiment]}`}>
              {call.summary.sentiment}
            </Text>
          )}
        </View>

        {expanded && (
          <>
            {call.summary?.summary_text && (
              <Text className="text-slate-300 text-xs mt-2 leading-4">
                {call.summary.summary_text}
              </Text>
            )}
            {call.coaching_notes && (
              <View className="mt-2 bg-amber-900/30 border border-amber-700 rounded-lg p-2">
                <Text className="text-amber-400 text-xs font-semibold mb-0.5">Coaching note:</Text>
                <Text className="text-slate-300 text-xs">{call.coaching_notes}</Text>
              </View>
            )}
            <Pressable
              className="mt-3 bg-slate-700 rounded-lg py-2 items-center"
              onPress={onUnflag}>
              <Text className="text-slate-300 text-xs font-semibold">Mark as reviewed</Text>
            </Pressable>
          </>
        )}
      </Pressable>
    </View>
  );
}

export function ManagerDashboardScreen() {
  const [days, setDays] = useState(7);
  const queryClient     = useQueryClient();

  const {data, isLoading, refetch} = useQuery({
    queryKey: ['analytics', 'team', days],
    queryFn: () => intelligenceApi.team(days).then(r => r.data),
  });

  const unflag = useMutation({
    mutationFn: (callId: number) => intelligenceApi.unflagCall(callId),
    onSuccess: () => queryClient.invalidateQueries({queryKey: ['analytics', 'team']}),
    onError: () => Alert.alert('Error', 'Could not mark as reviewed.'),
  });

  if (isLoading || !data) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="pb-10">
      {/* Header */}
      <View className="pt-14 px-4 pb-4">
        <Text className="text-white text-2xl font-bold">Team Dashboard</Text>

        {/* Period toggle */}
        <View className="flex-row gap-2 mt-3">
          {[7, 14, 30].map(d => (
            <Pressable
              key={d}
              className={`px-4 py-1.5 rounded-full ${days === d ? 'bg-brand-600' : 'bg-surface-card'}`}
              onPress={() => setDays(d)}>
              <Text className={`text-xs font-medium ${days === d ? 'text-white' : 'text-slate-400'}`}>
                {d}d
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      {/* Team totals */}
      <View className="flex-row gap-3 px-4 mb-5">
        <View className="flex-1 bg-surface-card rounded-xl p-4">
          <Text className="text-slate-400 text-xs mb-1">Total calls</Text>
          <Text className="text-white text-2xl font-bold">{data.team_totals.calls}</Text>
        </View>
        <View className="flex-1 bg-surface-card rounded-xl p-4">
          <Text className="text-slate-400 text-xs mb-1">Total talk time</Text>
          <Text className="text-white text-2xl font-bold">
            {formatDuration(data.team_totals.total_duration)}
          </Text>
        </View>
      </View>

      {/* Agent leaderboard */}
      <View className="px-4 mb-6">
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-2">
          Agent Leaderboard
        </Text>
        <View className="bg-surface-card rounded-xl px-4">
          {data.agent_stats.length === 0 ? (
            <View className="py-6 items-center">
              <Text className="text-slate-500 text-sm">No call activity yet</Text>
            </View>
          ) : (
            data.agent_stats.map((stat, i) => (
              <AgentRow key={stat.agent.id} stat={stat} />
            ))
          )}
        </View>
      </View>

      {/* Flagged calls */}
      {data.flagged_calls.length > 0 && (
        <View className="px-4">
          <View className="flex-row items-center gap-2 mb-2">
            <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
              Flagged for Coaching
            </Text>
            <View className="bg-red-500 rounded-full w-5 h-5 items-center justify-center">
              <Text className="text-white text-xs font-bold">{data.flagged_calls.length}</Text>
            </View>
          </View>
          {data.flagged_calls.map(call => (
            <FlaggedCallRow
              key={call.id}
              call={call}
              onUnflag={() => unflag.mutate(call.id)}
            />
          ))}
        </View>
      )}
    </ScrollView>
  );
}
