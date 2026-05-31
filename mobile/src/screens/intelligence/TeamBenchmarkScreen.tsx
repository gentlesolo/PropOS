import React, {useState} from 'react';
import {ActivityIndicator, FlatList, Pressable, ScrollView, Text, View} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import {benchmarkApi, BenchmarkData, LeaderboardEntry} from '../../api/benchmark';

function formatDuration(secs: number): string {
  const m = Math.floor(secs / 60);
  const s = secs % 60;
  return m > 0 ? `${m}m ${s}s` : `${s}s`;
}

// ── Percentile gauge ─────────────────────────────────────────────────────────
function PercentileGauge({
  label,
  personal,
  teamAvg,
  percentile,
  format: fmt,
}: {
  label: string;
  personal: number;
  teamAvg: number;
  percentile: number;
  format: (v: number) => string;
}) {
  const barColor =
    percentile >= 75 ? '#22c55e' :
    percentile >= 50 ? '#3b82f6' :
    percentile >= 25 ? '#f59e0b' : '#ef4444';

  return (
    <View className="bg-surface-card rounded-xl p-4 mb-3">
      <View className="flex-row items-center justify-between mb-3">
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">{label}</Text>
        <View className="flex-row items-center gap-2">
          <Text className="text-white font-bold text-base">{fmt(personal)}</Text>
          <Text className="text-slate-500 text-xs">vs {fmt(teamAvg)} avg</Text>
        </View>
      </View>

      {/* Percentile bar */}
      <View className="h-2 bg-slate-700 rounded-full overflow-hidden mb-1">
        <View
          style={{
            width: `${percentile}%`,
            height: 8,
            backgroundColor: barColor,
            borderRadius: 4,
          }}
        />
      </View>

      <View className="flex-row justify-between">
        <Text className="text-slate-600 text-xs">0th</Text>
        <Text style={{color: barColor}} className="text-xs font-semibold">
          {percentile}th percentile
        </Text>
        <Text className="text-slate-600 text-xs">100th</Text>
      </View>
    </View>
  );
}

// ── Leaderboard row ──────────────────────────────────────────────────────────
function LeaderRow({
  entry,
  rank,
  isMe,
  metric,
}: {
  entry: LeaderboardEntry;
  rank: number;
  isMe: boolean;
  metric: string;
}) {
  const initials =
    entry.agent.first_name.charAt(0) + entry.agent.last_name.charAt(0);

  const metricValue =
    metric === 'duration'
      ? formatDuration(entry.avg_duration)
      : String(entry.call_count);

  const rankEmoji = rank === 1 ? '🥇' : rank === 2 ? '🥈' : rank === 3 ? '🥉' : `#${rank}`;

  return (
    <View className={`flex-row items-center py-3 border-b border-slate-800 ${isMe ? 'bg-brand-950/30' : ''}`}>
      <Text className="text-slate-400 text-sm w-8">{rankEmoji}</Text>
      <View className="w-9 h-9 rounded-full bg-brand-700 items-center justify-center mr-3">
        <Text className="text-white text-sm font-semibold">{initials}</Text>
      </View>
      <Text className={`flex-1 text-sm font-medium ${isMe ? 'text-brand-400' : 'text-white'}`}>
        {entry.agent.first_name} {entry.agent.last_name}
        {isMe ? ' (you)' : ''}
      </Text>
      <Text className="text-white font-bold text-sm">{metricValue}</Text>
    </View>
  );
}

// ── Main screen ──────────────────────────────────────────────────────────────
export function TeamBenchmarkScreen() {
  const [days, setDays]         = useState(30);
  const [metric, setMetric]     = useState<'calls' | 'duration'>('calls');

  const {data: bench, isLoading: benchLoading} = useQuery({
    queryKey: ['benchmark', days],
    queryFn: () => benchmarkApi.compare(days).then(r => r.data),
  });

  const {data: lb, isLoading: lbLoading} = useQuery({
    queryKey: ['leaderboard', metric, days],
    queryFn: () => benchmarkApi.leaderboard(metric, days).then(r => r.data),
  });

  const isLoading = benchLoading || lbLoading;

  return (
    <ScrollView className="flex-1 bg-surface" contentContainerClassName="px-4 pt-14 pb-10">
      {/* Header */}
      <Text className="text-white text-2xl font-bold mb-1">My Benchmark</Text>

      {/* Period selector */}
      <View className="flex-row gap-2 mb-5 mt-2">
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

      {isLoading ? (
        <View className="py-20 items-center">
          <ActivityIndicator color="#3b82f6" />
        </View>
      ) : (
        <>
          {/* Rank badge */}
          {bench?.rankings.my_rank && (
            <View className="bg-brand-900 border border-brand-700 rounded-xl p-4 mb-5 flex-row items-center">
              <Text className="text-4xl mr-4">
                {bench.rankings.my_rank === 1 ? '🥇' :
                 bench.rankings.my_rank === 2 ? '🥈' :
                 bench.rankings.my_rank === 3 ? '🥉' : '📊'}
              </Text>
              <View>
                <Text className="text-white text-xl font-bold">
                  #{bench.rankings.my_rank} of {bench.rankings.out_of}
                </Text>
                <Text className="text-slate-400 text-sm">
                  agents in your agency
                </Text>
              </View>
            </View>
          )}

          {/* No-peer message */}
          {bench?.message && (
            <View className="bg-surface-card rounded-xl p-4 mb-4">
              <Text className="text-slate-400 text-sm">{bench.message}</Text>
            </View>
          )}

          {/* Percentile gauges */}
          {bench?.percentiles && bench.team_avg && (
            <>
              <PercentileGauge
                label="Call Volume"
                personal={bench.personal.total_calls}
                teamAvg={bench.team_avg.calls_per_period}
                percentile={bench.percentiles.calls}
                format={v => String(Math.round(v))}
              />
              <PercentileGauge
                label="Avg Call Duration"
                personal={bench.personal.avg_duration_sec}
                teamAvg={bench.team_avg.avg_duration_sec}
                percentile={bench.percentiles.duration}
                format={formatDuration}
              />
              <PercentileGauge
                label="Lead Sentiment Score"
                personal={bench.personal.avg_sentiment_score}
                teamAvg={bench.team_avg.avg_sentiment_score}
                percentile={bench.percentiles.sentiment}
                format={v => `${Math.round(v)}/100`}
              />
            </>
          )}

          {/* Leaderboard */}
          <View className="mt-4">
            <View className="flex-row items-center justify-between mb-3">
              <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
                Leaderboard
              </Text>
              <View className="flex-row gap-2">
                {(['calls', 'duration'] as const).map(m => (
                  <Pressable
                    key={m}
                    className={`px-3 py-1 rounded-full ${metric === m ? 'bg-brand-600' : 'bg-surface-card'}`}
                    onPress={() => setMetric(m)}>
                    <Text className={`text-xs ${metric === m ? 'text-white' : 'text-slate-400'}`}>
                      {m === 'calls' ? 'Volume' : 'Duration'}
                    </Text>
                  </Pressable>
                ))}
              </View>
            </View>

            <View className="bg-surface-card rounded-xl px-4">
              {(lb?.leaderboard ?? []).map((entry, i) => (
                <LeaderRow
                  key={entry.agent.id}
                  entry={entry}
                  rank={i + 1}
                  isMe={false}
                  metric={metric}
                />
              ))}
              {(lb?.leaderboard ?? []).length === 0 && (
                <View className="py-6 items-center">
                  <Text className="text-slate-500 text-sm">No data yet</Text>
                </View>
              )}
            </View>
          </View>
        </>
      )}
    </ScrollView>
  );
}
