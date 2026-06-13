import React from 'react';
import {Text, View} from 'react-native';
import {SentimentPoint} from '../api/intelligence';
import {format} from 'date-fns';
import {useTheme} from '../theme/ThemeProvider';

const SENTIMENT_SCORE: Record<string, number> = {
  hot: 90, warm: 65, cold: 25, neutral: 50,
};

const SENTIMENT_COLOR: Record<string, string> = {
  hot: '#ef4444', warm: '#f59e0b', cold: '#3b82f6', neutral: '#64748b',
};


interface Props {
  points: SentimentPoint[];
}

export function SentimentTrendChart({points}: Props) {
  const {tokens} = useTheme();

  if (points.length === 0) {
    return (
      <View style={{borderRadius: 12, padding: 16, alignItems: 'center', backgroundColor: tokens.surfaceCard}}>
        <Text style={{fontSize: 14, color: tokens.textSecondary}}>No call sentiment data yet</Text>
      </View>
    );
  }

  const chartH = 60;
  const barWidth = Math.min(32, Math.floor(280 / points.length) - 4);
  const avgScore = Math.round(
    points.reduce((sum, p) => sum + (p.sentiment_score ?? SENTIMENT_SCORE[p.sentiment] ?? 50), 0) / points.length,
  );
  const trend = points.length >= 2
    ? (points[points.length - 1].sentiment_score ?? 50) - (points[0].sentiment_score ?? 50)
    : 0;

  return (
    <View style={{borderRadius: 12, padding: 16, backgroundColor: tokens.surfaceCard}}>
      {/* Header */}
      <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', marginBottom: 12}}>
        <Text style={{fontSize: 12, fontWeight: '600', textTransform: 'uppercase', letterSpacing: 1, color: tokens.textSecondary}}>
          Sentiment Trend ({points.length} calls)
        </Text>
        <View style={{flexDirection: 'row', alignItems: 'center', gap: 8}}>
          <Text style={{fontSize: 14, fontWeight: '700', color: tokens.textPrimary}}>{avgScore}</Text>
          <Text style={{fontSize: 12, color: trend > 0 ? '#10B981' : trend < 0 ? '#F43F5E' : tokens.textTertiary}}>
            {trend > 0 ? `↑${trend}` : trend < 0 ? `↓${Math.abs(trend)}` : '→'}
          </Text>
        </View>
      </View>

      {/* Bar chart */}
      <View style={{flexDirection: 'row', alignItems: 'flex-end', justifyContent: 'center', gap: 4, height: chartH + 20}}>
        {points.map((p, i) => {
          const score = p.sentiment_score ?? SENTIMENT_SCORE[p.sentiment] ?? 50;
          const h = Math.max(4, Math.round((score / 100) * chartH));
          const color = SENTIMENT_COLOR[p.sentiment] ?? '#64748b';
          const isLast = i === points.length - 1;
          return (
            <View key={i} style={{alignItems: 'center', width: barWidth}}>
              <View style={{width: barWidth, height: h, backgroundColor: color, borderRadius: 3, opacity: isLast ? 1 : 0.65}} />
              {isLast && (
                <View style={{width: 6, height: 6, borderRadius: 3, backgroundColor: color, marginTop: 3}} />
              )}
            </View>
          );
        })}
      </View>

      {/* X-axis labels */}
      <View style={{flexDirection: 'row', justifyContent: 'space-between', marginTop: 4}}>
        <Text style={{fontSize: 12, color: tokens.textTertiary}}>
          {points[0]?.date ? format(new Date(points[0].date), 'MMM d') : ''}
        </Text>
        <Text style={{fontSize: 12, color: tokens.textTertiary}}>
          {points[points.length - 1]?.date ? format(new Date(points[points.length - 1].date), 'MMM d') : ''}
        </Text>
      </View>

      {/* Legend */}
      <View style={{flexDirection: 'row', gap: 12, marginTop: 12, flexWrap: 'wrap'}}>
        {(['hot', 'warm', 'cold', 'neutral'] as const).map((s) => (
          <View key={s} style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
            <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLOR[s]}} />
            <Text style={{fontSize: 12, textTransform: 'capitalize', color: tokens.textSecondary}}>{s}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}
