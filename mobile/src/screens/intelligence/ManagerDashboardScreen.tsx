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
  SafeAreaView,
} from 'react-native';
import {useMutation, useQuery, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {intelligenceApi, TeamAnalytics} from '../../api/intelligence';
import {format} from 'date-fns';
import type {IntelligenceStackParamList} from '../../navigation/stacks/IntelligenceStack';
import Icon from 'react-native-vector-icons/Feather';

type NavProp = NativeStackNavigationProp<IntelligenceStackParamList>;

function formatDuration(secs: number): string {
  const h = Math.floor(secs / 3600);
  const m = Math.floor((secs % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

const SENTIMENT_COLORS: Record<string, string> = {
  hot: 'bg-red-50 text-red-700 border-red-200',
  warm: 'bg-amber-50 text-amber-700 border-amber-200',
  cold: 'bg-blue-50 text-blue-700 border-blue-200',
  neutral: 'bg-slate-50 text-slate-600 border-slate-200',
};

function AgentRow({stat, index}: {stat: TeamAnalytics['agent_stats'][0], index: number}) {
  const agent = stat.agent;
  const initials = agent.first_name.charAt(0) + agent.last_name.charAt(0);
  return (
    <View className={`flex-row items-center py-4 px-5 ${index > 0 ? 'border-t border-slate-50' : ''}`}>
      <View className="w-12 h-12 rounded-full bg-brand-50 border border-brand-100 items-center justify-center mr-4 shadow-sm">
        <Text className="text-brand-600 text-sm font-extrabold">{initials}</Text>
      </View>
      <View className="flex-1">
        <Text className="text-slate-900 text-base font-bold">
          {agent.first_name} {agent.last_name}
        </Text>
        <Text className="text-slate-500 text-xs font-medium mt-0.5">
          Avg <Text className="font-bold text-slate-600">{formatDuration(stat.avg_duration)}</Text> per call
        </Text>
      </View>
      <View className="items-end gap-1">
        <View className="bg-slate-50 px-2 py-0.5 rounded-md border border-slate-200">
           <Text className="text-slate-700 font-extrabold text-sm">{stat.call_count} <Text className="text-[10px] text-slate-500 font-medium">calls</Text></Text>
        </View>
        <Text className="text-slate-400 text-xs font-bold uppercase tracking-widest">{formatDuration(stat.total_duration)}</Text>
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
  const sentimentStyles = call.summary ? SENTIMENT_COLORS[call.summary.sentiment] : SENTIMENT_COLORS.neutral;
  
  return (
    <View className="bg-white shadow-sm border border-slate-100 rounded-2xl mx-5 mb-4 overflow-hidden">
      <Pressable onPress={() => setExpanded(v => !v)} className="p-4">
        <View className="flex-row items-start justify-between">
          <View className="flex-1 pr-4">
            <Text className="text-slate-900 font-bold text-base mb-0.5">
              {call.agent?.first_name} {call.agent?.last_name}
            </Text>
            {call.contact && (
              <Text className="text-slate-600 text-xs font-medium">
                With: <Text className="font-bold">{call.contact.first_name} {call.contact.last_name}</Text>
              </Text>
            )}
            {call.started_at && (
              <Text className="text-slate-400 text-[10px] font-bold uppercase tracking-widest mt-2">
                {format(new Date(call.started_at), 'PPp')}
              </Text>
            )}
          </View>
          {call.summary && (
            <View className={`px-2 py-1 rounded-md border ${sentimentStyles}`}>
              <Text className={`text-[10px] font-bold uppercase tracking-wider ${sentimentStyles.split(' ')[1]}`}>
                {call.summary.sentiment}
              </Text>
            </View>
          )}
        </View>

        {expanded && (
          <View className="mt-4 pt-4 border-t border-slate-100">
            {call.summary?.summary_text && (
              <Text className="text-slate-600 text-sm font-medium leading-relaxed mb-4">
                {call.summary.summary_text}
              </Text>
            )}
            {call.coaching_notes && (
              <View className="bg-amber-50 border border-amber-200 rounded-xl p-3 mb-4">
                <Text className="text-amber-700 text-xs font-extrabold uppercase tracking-widest mb-1.5">Coaching Note</Text>
                <Text className="text-amber-900 text-sm font-medium">{call.coaching_notes}</Text>
              </View>
            )}
            <Pressable
              className="bg-brand-50 border border-brand-200 rounded-xl py-3 items-center active:bg-brand-100"
              onPress={onUnflag}>
              <Text className="text-brand-700 text-sm font-extrabold uppercase tracking-wide">Mark as Reviewed</Text>
            </Pressable>
          </View>
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
      <View className="flex-1 bg-slate-50 items-center justify-center">
        <ActivityIndicator color="#10b981" size="large" />
      </View>
    );
  }

  return (
    <SafeAreaView className="flex-1 bg-slate-50">
      {/* Header */}
      <View className="px-5 pt-6 pb-4 bg-white border-b border-slate-100 shadow-sm z-10">
        <Text className="text-slate-900 text-3xl font-extrabold tracking-tight mb-4">Team Dashboard</Text>

        {/* Period toggle */}
        <View className="flex-row gap-2">
          {[7, 14, 30].map(d => (
            <Pressable
              key={d}
              className={`px-4 py-2 rounded-full border ${days === d ? 'bg-brand-600 border-brand-600 shadow-sm' : 'bg-slate-50 border-slate-200'}`}
              onPress={() => setDays(d)}>
              <Text className={`text-xs font-bold tracking-wide ${days === d ? 'text-white' : 'text-slate-600'}`}>
                {d} Days
              </Text>
            </Pressable>
          ))}
        </View>
      </View>

      <ScrollView className="flex-1" contentContainerClassName="pt-5 pb-10">
        {/* Team totals */}
        <View className="flex-row gap-4 px-5 mb-8">
          <View className="flex-1 bg-white shadow-sm border border-slate-100 rounded-3xl p-5">
            <View className="w-10 h-10 rounded-full bg-brand-50 items-center justify-center mb-3">
              <Icon name="phone" size={18} color="#10B981" />
            </View>
            <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest mb-1">Total calls</Text>
            <Text className="text-slate-900 text-3xl font-extrabold tracking-tight">{data.team_totals.calls}</Text>
          </View>
          <View className="flex-1 bg-white shadow-sm border border-slate-100 rounded-3xl p-5">
            <View className="w-10 h-10 rounded-full bg-blue-50 items-center justify-center mb-3">
              <Icon name="clock" size={18} color="#0EA5E9" />
            </View>
            <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest mb-1">Talk time</Text>
            <Text className="text-slate-900 text-2xl font-extrabold tracking-tight">
              {formatDuration(data.team_totals.total_duration)}
            </Text>
          </View>
        </View>

        {/* Agent leaderboard */}
        <View className="mb-8">
          <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest mb-3 px-7">
            Agent Leaderboard
          </Text>
          <View className="bg-white shadow-sm border border-slate-100 rounded-3xl mx-5 overflow-hidden">
            {data.agent_stats.length === 0 ? (
              <View className="py-10 items-center">
                <Text className="text-slate-500 font-medium">No call activity yet</Text>
              </View>
            ) : (
              data.agent_stats.map((stat, i) => (
                <AgentRow key={stat.agent.id} stat={stat} index={i} />
              ))
            )}
          </View>
        </View>

        {/* Flagged calls */}
        {data.flagged_calls.length > 0 && (
          <View className="mb-4">
            <View className="flex-row items-center gap-3 mb-3 px-7">
              <Text className="text-slate-400 text-xs font-extrabold uppercase tracking-widest">
                Flagged for Coaching
              </Text>
              <View className="bg-red-50 border border-red-200 rounded-full px-2 py-0.5 items-center justify-center">
                <Text className="text-red-600 text-[10px] font-extrabold">{data.flagged_calls.length}</Text>
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
    </SafeAreaView>
  );
}
