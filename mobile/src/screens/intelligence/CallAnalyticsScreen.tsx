import React from 'react';
import {ActivityIndicator, Pressable, ScrollView, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import type {NativeStackNavigationProp} from '@react-navigation/native-stack';
import {intelligenceApi, PersonalAnalytics} from '../../api/intelligence';
import {format} from 'date-fns';
import type {IntelligenceStackParamList} from '../../navigation/stacks/IntelligenceStack';

function formatDuration(secs: number): string {
  const h = Math.floor(secs / 3600);
  const m = Math.floor((secs % 3600) / 60);
  if (h > 0) return `${h}h ${m}m`;
  return `${m}m`;
}

const SENTIMENT_COLORS: Record<string, string> = {
  hot:     '#ef4444',
  warm:    '#f59e0b',
  cold:    '#3b82f6',
  neutral: '#64748b',
};

function StatCard({label, value, sub}: {label: string; value: string; sub?: string}) {
  return (
    <View className="flex-1 bg-surface-card rounded-xl p-4">
      <Text className="text-slate-400 text-xs mb-1">{label}</Text>
      <Text className="text-white text-xl font-bold">{value}</Text>
      {sub && <Text className="text-slate-500 text-xs mt-0.5">{sub}</Text>}
    </View>
  );
}

function Sparkline({data}: {data: Array<{date: string; count: number}>}) {
  const max = Math.max(...data.map(d => d.count), 1);
  return (
    <View className="flex-row items-end gap-0.5" style={{height: 40}}>
      {data.map((d, i) => {
        const h = Math.max(2, Math.round((d.count / max) * 40));
        const isToday = i === data.length - 1;
        return (
          <View
            key={d.date}
            style={{
              flex: 1,
              height: h,
              backgroundColor: isToday ? '#3b82f6' : '#334155',
              borderRadius: 2,
            }}
          />
        );
      })}
    </View>
  );
}

type NavProp = NativeStackNavigationProp<IntelligenceStackParamList>;

export function CallAnalyticsScreen() {
  const navigation = useNavigation<NavProp>();
  const {data, isLoading} = useQuery({
    queryKey: ['analytics', 'personal'],
    queryFn: () => intelligenceApi.personal(30).then(r => r.data),
  });

  if (isLoading || !data) {
    return (
      <View className="flex-1 bg-surface items-center justify-center">
        <ActivityIndicator color="#3b82f6" />
      </View>
    );
  }

  const sentimentEntries = Object.entries(data.sentiment)
    .sort((a, b) => b[1] - a[1]);
  const totalWithSentiment = sentimentEntries.reduce((s, [, c]) => s + c, 0);

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="px-4 pt-14 pb-10">
      <View className="flex-row items-center justify-between mb-1">
        <Text className="text-white text-2xl font-bold">My Analytics</Text>
        <Pressable
          className="flex-row items-center gap-1"
          onPress={() => navigation.navigate('Benchmark')}>
          <Text className="text-brand-500 text-sm">Team rank →</Text>
        </Pressable>
      </View>
      <Text className="text-slate-400 text-sm mb-5">Last {data.period_days} days</Text>

      {/* Stat grid */}
      <View className="flex-row gap-3 mb-3">
        <StatCard label="Total calls" value={String(data.total_calls)} />
        <StatCard label="Talk time" value={formatDuration(data.total_duration_sec)} />
      </View>
      <View className="flex-row gap-3 mb-5">
        <StatCard
          label="Avg duration"
          value={formatDuration(data.avg_duration_sec)}
        />
        <StatCard
          label="Avg sentiment"
          value={`${data.avg_sentiment_score}/100`}
          sub={`${data.inbound} in · ${data.outbound} out`}
        />
      </View>

      {/* Daily volume sparkline */}
      <View className="bg-surface-card rounded-xl p-4 mb-5">
        <View className="flex-row items-center justify-between mb-3">
          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
            Daily Volume (14 days)
          </Text>
          <Text className="text-white text-xs font-bold">
            {data.daily_volume[data.daily_volume.length - 1]?.count ?? 0} today
          </Text>
        </View>
        <Sparkline data={data.daily_volume} />
        <View className="flex-row justify-between mt-1">
          <Text className="text-slate-600 text-xs">
            {data.daily_volume[0]?.date ? format(new Date(data.daily_volume[0].date), 'MMM d') : ''}
          </Text>
          <Text className="text-slate-600 text-xs">Today</Text>
        </View>
      </View>

      {/* Sentiment breakdown */}
      {totalWithSentiment > 0 && (
        <View className="bg-surface-card rounded-xl p-4">
          <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide mb-4">
            Sentiment Breakdown
          </Text>
          {sentimentEntries.map(([sentiment, count]) => {
            const pct = Math.round((count / totalWithSentiment) * 100);
            return (
              <View key={sentiment} className="mb-3">
                <View className="flex-row justify-between mb-1">
                  <Text className="text-slate-300 text-sm capitalize">{sentiment}</Text>
                  <Text className="text-slate-400 text-sm">{count} calls · {pct}%</Text>
                </View>
                <View className="h-2 bg-slate-700 rounded-full overflow-hidden">
                  <View
                    style={{
                      width: `${pct}%`,
                      height: 8,
                      backgroundColor: SENTIMENT_COLORS[sentiment] ?? '#64748b',
                      borderRadius: 4,
                    }}
                  />
                </View>
              </View>
            );
          })}
        </View>
      )}
    </ScrollView>
  );
}
