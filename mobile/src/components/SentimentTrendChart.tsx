import React from 'react';
import {Text, View} from 'react-native';
import {SentimentPoint} from '../api/intelligence';
import {format} from 'date-fns';

const SENTIMENT_SCORE: Record<string, number> = {
  hot: 90, warm: 65, cold: 25, neutral: 50,
};

const SENTIMENT_COLOR: Record<string, string> = {
  hot: '#ef4444', warm: '#f59e0b', cold: '#3b82f6', neutral: '#64748b',
};

const SENTIMENT_EMOJI: Record<string, string> = {
  hot: '🔥', warm: '☀️', cold: '🧊', neutral: '😐',
};

interface Props {
  points: SentimentPoint[];
}

export function SentimentTrendChart({points}: Props) {
  if (points.length === 0) {
    return (
      <View className="bg-surface-card rounded-xl p-4 items-center">
        <Text className="text-slate-500 text-sm">No call sentiment data yet</Text>
      </View>
    );
  }

  const maxScore  = 100;
  const chartH    = 60;
  const barWidth  = Math.min(32, Math.floor(280 / points.length) - 4);

  const avgScore = Math.round(
    points.reduce((sum, p) => sum + (p.sentiment_score ?? SENTIMENT_SCORE[p.sentiment] ?? 50), 0)
    / points.length,
  );

  const trend = points.length >= 2
    ? (points[points.length - 1].sentiment_score ?? 50) - (points[0].sentiment_score ?? 50)
    : 0;

  return (
    <View className="bg-surface-card rounded-xl p-4">
      {/* Header */}
      <View className="flex-row items-center justify-between mb-3">
        <Text className="text-slate-400 text-xs font-semibold uppercase tracking-wide">
          Sentiment Trend ({points.length} calls)
        </Text>
        <View className="flex-row items-center gap-2">
          <Text className="text-white text-sm font-bold">{avgScore}</Text>
          <Text className={`text-xs ${trend > 0 ? 'text-green-400' : trend < 0 ? 'text-red-400' : 'text-slate-400'}`}>
            {trend > 0 ? `↑${trend}` : trend < 0 ? `↓${Math.abs(trend)}` : '→'}
          </Text>
        </View>
      </View>

      {/* Bar chart */}
      <View className="flex-row items-end justify-center gap-1" style={{height: chartH + 20}}>
        {points.map((p, i) => {
          const score  = p.sentiment_score ?? SENTIMENT_SCORE[p.sentiment] ?? 50;
          const h      = Math.max(4, Math.round((score / maxScore) * chartH));
          const color  = SENTIMENT_COLOR[p.sentiment] ?? '#64748b';
          const isLast = i === points.length - 1;

          return (
            <View key={i} className="items-center" style={{width: barWidth}}>
              <View
                style={{
                  width:  barWidth,
                  height: h,
                  backgroundColor: color,
                  borderRadius: 3,
                  opacity: isLast ? 1 : 0.65,
                }}
              />
              {isLast && (
                <Text style={{fontSize: 10, marginTop: 2}}>
                  {SENTIMENT_EMOJI[p.sentiment]}
                </Text>
              )}
            </View>
          );
        })}
      </View>

      {/* X-axis labels — show first, last, and any inflection */}
      <View className="flex-row justify-between mt-1">
        <Text className="text-slate-600 text-xs">
          {points[0]?.date ? format(new Date(points[0].date), 'MMM d') : ''}
        </Text>
        <Text className="text-slate-600 text-xs">
          {points[points.length - 1]?.date
            ? format(new Date(points[points.length - 1].date), 'MMM d')
            : ''}
        </Text>
      </View>

      {/* Legend */}
      <View className="flex-row gap-3 mt-3 flex-wrap">
        {(['hot', 'warm', 'cold', 'neutral'] as const).map(s => (
          <View key={s} className="flex-row items-center gap-1">
            <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLOR[s]}} />
            <Text className="text-slate-500 text-xs capitalize">{s}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
