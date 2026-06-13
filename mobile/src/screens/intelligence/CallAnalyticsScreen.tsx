import React, {useState, useRef, useEffect} from 'react';
import {
  ActivityIndicator,
  Animated,
  Dimensions,
  Image,
  Modal,
  Pressable,
  ScrollView,
  Text,
  View,
  Vibration,
} from 'react-native';
import {useQuery} from '@tanstack/react-query';
import Icon from 'react-native-vector-icons/Feather';
import {useTheme} from '../../theme/ThemeProvider';
import {useAuthStore} from '../../store/authStore';
import {managerApi, CallAnalyticsResponse} from '../../api/manager';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

const SENTIMENT_COLORS: Record<string, string> = {
  hot: '#EF4444',     // Red
  warm: '#F59E0B',    // Amber
  cold: '#3B82F6',    // Blue
  neutral: '#71717A', // Zinc
};

// Custom duration formatter
function formatDuration(secs: number): string {
  if (secs <= 0) return '0s';
  const m = Math.floor(secs / 60);
  const s = secs % 60;
  return m > 0 ? `${m}m ${s}s` : `${s}s`;
}

export function CallAnalyticsScreen() {
  const {tokens} = useTheme();
  const {user} = useAuthStore();

  // Filters State
  const [period, setPeriod] = useState<'Today' | 'This Week' | 'This Month' | 'Custom'>('This Week');
  const [direction, setDirection] = useState<'All' | 'Inbound' | 'Outbound'>('All');
  const [selectedAgentId, setSelectedAgentId] = useState<string | null>('All');
  const [showFilterModal, setShowFilterModal] = useState(false);
  const [selectedBarIndex, setSelectedBarIndex] = useState<number | null>(null);

  // Shimmer opacity animation
  const skeletonOpacity = useRef(new Animated.Value(0.3)).current;
  useEffect(() => {
    const shimmer = Animated.loop(
      Animated.sequence([
        Animated.timing(skeletonOpacity, {toValue: 0.7, duration: 800, useNativeDriver: true}),
        Animated.timing(skeletonOpacity, {toValue: 0.3, duration: 800, useNativeDriver: true}),
      ])
    );
    shimmer.start();
    return () => shimmer.stop();
  }, []);

  // Manager status check
  const isManager = (user as any)?.roles?.some((r: string) => r === 'admin' || r === 'manager' || r === 'principal' || r === 'super_admin' || r === 'branch_manager') ?? false;

  // Query fetching data
  const {data: analytics, isPending} = useQuery<CallAnalyticsResponse>({
    queryKey: ['manager', 'callAnalytics', period, selectedAgentId, direction],
    queryFn: () => managerApi.callAnalytics(period, selectedAgentId, direction).then((r) => r.data),
  });

  const periods = ['Today', 'This Week', 'This Month', 'Custom'] as const;

  // Delta UI component helper
  const renderDelta = (delta: number) => {
    if (delta === 0) return '—';
    const isPositive = delta > 0;
    return (
      <Text style={{color: isPositive ? tokens.successText : tokens.dangerText, fontSize: 10, fontWeight: '800'}}>
        {isPositive ? '↑' : '↓'} {Math.abs(delta)}%
      </Text>
    );
  };

  const hasNoCalls = !analytics || analytics.chart_data.length === 0;

  // Maximum value calculation for Call Volume chart height scaling
  const maxChartVal = analytics?.chart_data
    ? Math.max(...analytics.chart_data.map((d) => d.total), 1)
    : 1;

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* HEADER ROW */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          paddingVertical: 12,
          ...tokens.shadowSm,
        }}
      >
        <View style={{flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: 20, marginBottom: 14}}>
          <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', letterSpacing: -0.5}}>
            {isManager ? 'Call Analytics' : 'My Call Analytics'}
          </Text>

          <Pressable
            onPress={() => {
              Vibration.vibrate(5);
              setShowFilterModal(true);
            }}
            style={{
              width: 36,
              height: 36,
              borderRadius: 18,
              backgroundColor: tokens.surfaceRaised,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              alignItems: 'center',
              justifyContent: 'center',
            }}
          >
            <Icon name="sliders" size={16} color={tokens.brandPrimary} />
          </Pressable>
        </View>

        {/* Period Selector Scroll */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, gap: 8}}
        >
          {periods.map((p) => {
            const isActive = period === p;
            return (
              <Pressable
                key={p}
                onPress={() => {
                  Vibration.vibrate(5);
                  setPeriod(p);
                  setSelectedBarIndex(null);
                }}
                style={{
                  paddingHorizontal: 14,
                  paddingVertical: 6,
                  borderRadius: 999,
                  backgroundColor: isActive ? tokens.brandPrimary : tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: isActive ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text
                  style={{
                    fontSize: 12,
                    fontWeight: '700',
                    color: isActive ? '#FFFFFF' : tokens.textSecondary,
                  }}
                >
                  {p}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* DASHBOARD SCROLL BODY */}
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 40, paddingTop: 16}}
        showsVerticalScrollIndicator={false}
      >
        {isPending ? (
          /* SKELETON LOADING STATE */
          <View style={{paddingHorizontal: 20, gap: 16}}>
            {/* 2x2 Grid Shimmer */}
            <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 12}}>
              {[1, 2, 3, 4].map((i) => (
                <Animated.View
                  key={i}
                  style={{
                    width: (SCREEN_WIDTH - 52) / 2,
                    height: 84,
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    opacity: skeletonOpacity,
                  }}
                >
                  <View style={{height: 16, width: '60%', backgroundColor: tokens.surfaceRaised, borderRadius: 4, marginBottom: 8}} />
                  <View style={{height: 20, width: '40%', backgroundColor: tokens.surfaceRaised, borderRadius: 6}} />
                </Animated.View>
              ))}
            </View>

            {/* Chart Shimmer */}
            <Animated.View
              style={{
                height: 180,
                backgroundColor: tokens.surfaceCard,
                borderRadius: 16,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                opacity: skeletonOpacity,
              }}
            />
          </View>
        ) : hasNoCalls ? (
          /* EMPTY STATE */
          <View style={{paddingHorizontal: 20, paddingVertical: 40, alignItems: 'center'}}>
            <View
              style={{
                width: 72,
                height: 72,
                borderRadius: 36,
                backgroundColor: tokens.surfaceRaised,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: 16,
              }}
            >
              <Icon name="phone-off" size={32} color={tokens.textTertiary} />
            </View>
            <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '700'}}>
              No calls recorded for this period
            </Text>
            <Text style={{color: tokens.textTertiary, fontSize: 12, textAlign: 'center', marginTop: 4, paddingHorizontal: 32}}>
              Try changing the period range or selecting a different call direction filter.
            </Text>
          </View>
        ) : (
          /* LIVE DATA PRESENTATION */
          <View>
            {/* HEADLINE METRICS (2x2 Grid) */}
            <View style={{paddingHorizontal: 20, marginBottom: 20, flexDirection: 'row', flexWrap: 'wrap', gap: 12}}>
              {/* Stat 1: Total Calls */}
              <View
                style={{
                  width: (SCREEN_WIDTH - 52) / 2,
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 16,
                  justifyContent: 'space-between',
                  ...tokens.shadowSm,
                }}
              >
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Total Calls</Text>
                  {renderDelta(analytics.headline_metrics.total_calls.delta)}
                </View>
                <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 8}}>
                  {analytics.headline_metrics.total_calls.value}
                </Text>
              </View>

              {/* Stat 2: Avg Duration */}
              <View
                style={{
                  width: (SCREEN_WIDTH - 52) / 2,
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 16,
                  justifyContent: 'space-between',
                  ...tokens.shadowSm,
                }}
              >
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Avg. Duration</Text>
                  {renderDelta(analytics.headline_metrics.avg_duration.delta)}
                </View>
                <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 8}}>
                  {formatDuration(analytics.headline_metrics.avg_duration.value)}
                </Text>
              </View>

              {/* Stat 3: Answer Rate */}
              <View
                style={{
                  width: (SCREEN_WIDTH - 52) / 2,
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 16,
                  justifyContent: 'space-between',
                  ...tokens.shadowSm,
                }}
              >
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Answer Rate</Text>
                  {renderDelta(analytics.headline_metrics.answer_rate.delta)}
                </View>
                <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 8}}>
                  {analytics.headline_metrics.answer_rate.value}%
                </Text>
              </View>

              {/* Stat 4: Avg Sentiment */}
              <View
                style={{
                  width: (SCREEN_WIDTH - 52) / 2,
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 16,
                  justifyContent: 'space-between',
                  ...tokens.shadowSm,
                }}
              >
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Avg Sentiment</Text>
                  {renderDelta(analytics.headline_metrics.avg_sentiment.delta)}
                </View>
                <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 8, gap: 6}}>
                  <View
                    style={{
                      width: 8,
                      height: 8,
                      borderRadius: 4,
                      backgroundColor: SENTIMENT_COLORS[analytics.headline_metrics.avg_sentiment.rating] || '#71717A',
                    }}
                  />
                  <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700'}}>
                    {analytics.headline_metrics.avg_sentiment.value}%
                  </Text>
                </View>
              </View>
            </View>

            {/* CALL VOLUME STACKED BAR CHART */}
            <View style={{paddingHorizontal: 20, marginBottom: 24}}>
              <Text
                style={{
                  color: tokens.textSecondary,
                  fontSize: 11,
                  fontWeight: '800',
                  textTransform: 'uppercase',
                  letterSpacing: 1,
                  marginBottom: 12,
                }}
              >
                Call Volume
              </Text>

              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 18,
                  position: 'relative',
                  ...tokens.shadowSm,
                }}
              >
                {/* TOOLTIP WINDOW ON TAP */}
                {selectedBarIndex !== null && analytics.chart_data[selectedBarIndex] && (
                  <View
                    style={{
                      backgroundColor: tokens.surfaceOverlay,
                      borderRadius: 8,
                      paddingHorizontal: 12,
                      paddingVertical: 6,
                      borderWidth: 1,
                      borderColor: tokens.borderStrong,
                      alignSelf: 'center',
                      marginBottom: 14,
                    }}
                  >
                    <Text style={{color: '#FFFFFF', fontSize: 11, fontWeight: '700', textAlign: 'center'}}>
                      {analytics.chart_data[selectedBarIndex].label} · {analytics.chart_data[selectedBarIndex].total} calls · {analytics.chart_data[selectedBarIndex].answered} answered · avg {formatDuration(analytics.chart_data[selectedBarIndex].avg_duration_sec)}
                    </Text>
                  </View>
                )}

                {/* Bars track container */}
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-end', height: 110, paddingHorizontal: 8}}>
                  {analytics.chart_data.map((bar, idx) => {
                    const totalHeightPct = (bar.total / maxChartVal) * 100;
                    const answeredRatio = bar.total > 0 ? bar.answered / bar.total : 0;
                    const isSelected = selectedBarIndex === idx;

                    return (
                      <Pressable
                        key={idx}
                        onPress={() => {
                          Vibration.vibrate(5);
                          setSelectedBarIndex(selectedBarIndex === idx ? null : idx);
                        }}
                        style={{
                          alignItems: 'center',
                          flex: 1,
                          height: '100%',
                          justifyContent: 'flex-end',
                        }}
                      >
                        {/* Stacked bar segments */}
                        <View
                          style={{
                            width: 16,
                            height: `${totalHeightPct}%`,
                            borderRadius: 4,
                            backgroundColor: tokens.borderStrong, // Missed calls color track (Zinc-300 / Zinc-700 representation)
                            overflow: 'hidden',
                            borderWidth: isSelected ? 1.5 : 0,
                            borderColor: tokens.brandPrimary,
                          }}
                        >
                          {/* Answered portion filled from bottom */}
                          <View
                            style={{
                              position: 'absolute',
                              bottom: 0,
                              left: 0,
                              right: 0,
                              height: `${answeredRatio * 100}%`,
                              backgroundColor: '#10B981', // Emerald answered
                            }}
                          />
                        </View>

                        {/* X-axis label */}
                        <Text
                          style={{
                            color: tokens.textTertiary,
                            fontSize: 10,
                            fontFamily: 'monospace',
                            marginTop: 8,
                          }}
                        >
                          {bar.label}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            </View>

            {/* SENTIMENT BREAKDOWN BAR */}
            <View style={{paddingHorizontal: 20, marginBottom: 24}}>
              <Text
                style={{
                  color: tokens.textSecondary,
                  fontSize: 11,
                  fontWeight: '800',
                  textTransform: 'uppercase',
                  letterSpacing: 1,
                  marginBottom: 12,
                }}
              >
                Sentiment Proportion
              </Text>

              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 16,
                  ...tokens.shadowSm,
                }}
              >
                {/* Horizontal Segment Bar */}
                <View
                  style={{
                    height: 14,
                    borderRadius: 7,
                    flexDirection: 'row',
                    overflow: 'hidden',
                    backgroundColor: tokens.surfaceSunken,
                    marginBottom: 14,
                  }}
                >
                  <View style={{width: `${analytics.sentiment_breakdown.hot}%`, backgroundColor: SENTIMENT_COLORS.hot}} />
                  <View style={{width: `${analytics.sentiment_breakdown.warm}%`, backgroundColor: SENTIMENT_COLORS.warm}} />
                  <View style={{width: `${analytics.sentiment_breakdown.neutral}%`, backgroundColor: SENTIMENT_COLORS.neutral}} />
                  <View style={{width: `${analytics.sentiment_breakdown.cold}%`, backgroundColor: SENTIMENT_COLORS.cold}} />
                </View>

                {/* Legends */}
                <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 10, justifyContent: 'center', marginBottom: 12}}>
                  <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
                    <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLORS.hot}} />
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                      Hot {analytics.sentiment_breakdown.hot}%
                    </Text>
                  </View>
                  <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
                    <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLORS.warm}} />
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                      Warm {analytics.sentiment_breakdown.warm}%
                    </Text>
                  </View>
                  <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
                    <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLORS.neutral}} />
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                      Neutral {analytics.sentiment_breakdown.neutral}%
                    </Text>
                  </View>
                  <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
                    <View style={{width: 8, height: 8, borderRadius: 4, backgroundColor: SENTIMENT_COLORS.cold}} />
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700'}}>
                      Cold {analytics.sentiment_breakdown.cold}%
                    </Text>
                  </View>
                </View>

                {/* Period delta indicator */}
                <Text
                  style={{
                    color: tokens.successText,
                    fontSize: 11,
                    fontWeight: '700',
                    textAlign: 'center',
                  }}
                >
                  Hot calls up {analytics.sentiment_breakdown.delta_hot}% vs last period
                </Text>
              </View>
            </View>

            {/* CALL OUTCOMES TO CONVERSION FUNNEL */}
            <View style={{paddingHorizontal: 20, marginBottom: 24}}>
              <Text
                style={{
                  color: tokens.textSecondary,
                  fontSize: 11,
                  fontWeight: '800',
                  textTransform: 'uppercase',
                  letterSpacing: 1,
                  marginBottom: 12,
                }}
              >
                Outcomes & Conversion
              </Text>

              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 18,
                  ...tokens.shadowSm,
                }}
              >
                {/* Horizontal funnel stages */}
                <View style={{alignItems: 'center', gap: 8, paddingVertical: 10}}>
                  {/* Stage 1: Calls Made */}
                  <View style={{flexDirection: 'row', alignItems: 'center', width: '100%'}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', width: 90}}>
                      Calls Made
                    </Text>
                    <View style={{flex: 1, height: 16, backgroundColor: `${tokens.brandPrimary}22`, borderRadius: 4, overflow: 'hidden'}}>
                      <View style={{height: '100%', width: '100%', backgroundColor: tokens.brandPrimary}} />
                    </View>
                    <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '800', width: 40, textAlign: 'right'}}>
                      {analytics.conversion.calls_made}
                    </Text>
                  </View>

                  {/* Stage 2: Tasks Created */}
                  <View style={{flexDirection: 'row', alignItems: 'center', width: '100%'}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', width: 90}}>
                      Follow-ups
                    </Text>
                    <View style={{flex: 1, height: 16, backgroundColor: `${tokens.brandPrimary}22`, borderRadius: 4, overflow: 'hidden'}}>
                      <View
                        style={{
                          height: '100%',
                          width: `${analytics.conversion.calls_made > 0 ? (analytics.conversion.tasks_created / analytics.conversion.calls_made) * 100 : 0}%`,
                          backgroundColor: tokens.brandPrimary,
                        }}
                      />
                    </View>
                    <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '800', width: 40, textAlign: 'right'}}>
                      {analytics.conversion.tasks_created}
                    </Text>
                  </View>

                  {/* Stage 3: Viewings Booked */}
                  <View style={{flexDirection: 'row', alignItems: 'center', width: '100%'}}>
                    <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '700', width: 90}}>
                      Viewings
                    </Text>
                    <View style={{flex: 1, height: 16, backgroundColor: `${tokens.brandPrimary}22`, borderRadius: 4, overflow: 'hidden'}}>
                      <View
                        style={{
                          height: '100%',
                          width: `${analytics.conversion.calls_made > 0 ? (analytics.conversion.viewings_booked / analytics.conversion.calls_made) * 100 : 0}%`,
                          backgroundColor: tokens.brandPrimary,
                        }}
                      />
                    </View>
                    <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '800', width: 40, textAlign: 'right'}}>
                      {analytics.conversion.viewings_booked}
                    </Text>
                  </View>
                </View>

                {/* Highlighted rate label */}
                <View
                  style={{
                    marginTop: 14,
                    paddingTop: 14,
                    borderTopWidth: 1,
                    borderTopColor: tokens.borderSubtle,
                    alignItems: 'center',
                  }}
                >
                  <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800'}}>
                    {analytics.conversion.rate_text}
                  </Text>
                </View>
              </View>
            </View>

            {/* TOP PERFORMERS BY SENTIMENT (Managers Only) */}
            {isManager && analytics.top_performers && analytics.top_performers.length > 0 && (
              <View style={{marginBottom: 24}}>
                <Text
                  style={{
                    color: tokens.textSecondary,
                    fontSize: 11,
                    fontWeight: '800',
                    textTransform: 'uppercase',
                    letterSpacing: 1,
                    paddingHorizontal: 20,
                    marginBottom: 12,
                  }}
                >
                  Top Performers by Sentiment
                </Text>

                <ScrollView
                  horizontal
                  showsHorizontalScrollIndicator={false}
                  contentContainerStyle={{paddingHorizontal: 20, gap: 12}}
                >
                  {analytics.top_performers.map((agent) => {
                    const initials = agent.first_name[0] + agent.last_name[0];
                    return (
                      <View
                        key={agent.id}
                        style={{
                          width: 140,
                          backgroundColor: tokens.surfaceCard,
                          borderWidth: 1,
                          borderColor: tokens.borderDefault,
                          borderRadius: 16,
                          padding: 14,
                          alignItems: 'center',
                          ...tokens.shadowSm,
                        }}
                      >
                        <View
                          style={{
                            width: 38,
                            height: 38,
                            borderRadius: 19,
                            backgroundColor: `${tokens.brandPrimary}15`,
                            alignItems: 'center',
                            justifyContent: 'center',
                            marginBottom: 8,
                            borderWidth: 1,
                            borderColor: tokens.borderDefault,
                          }}
                        >
                          <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 12}}>
                            {initials}
                          </Text>
                        </View>
                        <Text style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700', textAlign: 'center'}} numberOfLines={1}>
                          {agent.first_name} {agent.last_name}
                        </Text>
                        <View style={{flexDirection: 'row', alignItems: 'center', marginTop: 4, gap: 4}}>
                          <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '800'}}>
                            {agent.sentiment_score}%
                          </Text>
                          <Icon
                            name={agent.trend === 'up' ? 'trending-up' : agent.trend === 'down' ? 'trending-down' : 'arrow-right'}
                            size={12}
                            color={agent.trend === 'up' ? tokens.successText : agent.trend === 'down' ? tokens.dangerText : tokens.textTertiary}
                          />
                        </View>
                      </View>
                    );
                  })}
                </ScrollView>
              </View>
            )}

            {/* AI INSIGHT CARD */}
            {analytics.ai_insight && (
              <View style={{paddingHorizontal: 20, marginBottom: 12}}>
                <View
                  style={{
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    position: 'relative',
                    overflow: 'hidden',
                    ...tokens.shadowSm,
                  }}
                >
                  <View
                    style={{
                      position: 'absolute',
                      left: 0,
                      top: 0,
                      bottom: 0,
                      width: 4,
                      backgroundColor: '#10B981', // Emerald-500 indicator
                    }}
                  />
                  <View style={{paddingLeft: 6}}>
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 6}}>
                      <Text style={{fontSize: 13, color: '#10B981'}}>✦</Text>
                      <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                        AI Insight
                      </Text>
                    </View>
                    <Text style={{color: tokens.textSecondary, fontSize: 12, lineHeight: 17}}>
                      {analytics.ai_insight}
                    </Text>
                  </View>
                </View>
              </View>
            )}
          </View>
        )}
      </ScrollView>

      {/* FILTER BOTTOM SHEET / MODAL */}
      <Modal
        visible={showFilterModal}
        transparent
        animationType="slide"
        onRequestClose={() => setShowFilterModal(false)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'flex-end',
          }}
          onPress={() => setShowFilterModal(false)}
        >
          <Pressable
            style={{
              backgroundColor: tokens.surfaceCard,
              borderTopLeftRadius: 24,
              borderTopRightRadius: 24,
              borderWidth: 1,
              borderColor: tokens.borderStrong,
              padding: 24,
              paddingBottom: 40,
            }}
            onPress={(e) => e.stopPropagation()}
          >
            {/* Header */}
            <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 20}}>
              <Text style={{color: tokens.textPrimary, fontSize: 16, fontWeight: '800'}}>
                Filter Analytics
              </Text>
              <Pressable
                onPress={() => setShowFilterModal(false)}
                style={{
                  width: 32,
                  height: 32,
                  borderRadius: 16,
                  backgroundColor: tokens.surfaceRaised,
                  alignItems: 'center',
                  justifyContent: 'center',
                }}
              >
                <Icon name="x" size={16} color={tokens.textSecondary} />
              </Pressable>
            </View>

            {/* Agent Filter (Managers Only) */}
            {isManager && (
              <View style={{marginBottom: 20}}>
                <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                  Agent
                </Text>
                <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 8}}>
                  {['All', 'Sarah Jenkins', 'Elena Rostova'].map((ag) => {
                    const active = selectedAgentId === ag;
                    return (
                      <Pressable
                        key={ag}
                        onPress={() => {
                          Vibration.vibrate(5);
                          setSelectedAgentId(ag);
                        }}
                        style={{
                          paddingHorizontal: 12,
                          paddingVertical: 6,
                          borderRadius: 8,
                          backgroundColor: active ? tokens.brandPrimary : tokens.surfaceRaised,
                          borderWidth: 1,
                          borderColor: active ? tokens.brandPrimary : tokens.borderDefault,
                        }}
                      >
                        <Text style={{color: active ? '#FFFFFF' : tokens.textSecondary, fontSize: 12, fontWeight: '700'}}>
                          {ag}
                        </Text>
                      </Pressable>
                    );
                  })}
                </View>
              </View>
            )}

            {/* Direction Filter */}
            <View style={{marginBottom: 24}}>
              <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '700', marginBottom: 8}}>
                Call Direction
              </Text>
              <View style={{flexDirection: 'row', gap: 8}}>
                {['All', 'Inbound', 'Outbound'].map((dir) => {
                  const active = direction === dir;
                  return (
                    <Pressable
                      key={dir}
                      onPress={() => {
                        Vibration.vibrate(5);
                        setDirection(dir as any);
                      }}
                      style={{
                        paddingHorizontal: 12,
                        paddingVertical: 6,
                        borderRadius: 8,
                        backgroundColor: active ? tokens.brandPrimary : tokens.surfaceRaised,
                        borderWidth: 1,
                        borderColor: active ? tokens.brandPrimary : tokens.borderDefault,
                        flex: 1,
                        alignItems: 'center',
                      }}
                    >
                      <Text style={{color: active ? '#FFFFFF' : tokens.textSecondary, fontSize: 12, fontWeight: '700'}}>
                        {dir}
                      </Text>
                    </Pressable>
                  );
                })}
              </View>
            </View>

            {/* Apply Close CTA */}
            <Pressable
              onPress={() => setShowFilterModal(false)}
              style={{
                backgroundColor: tokens.brandPrimary,
                borderRadius: 12,
                paddingVertical: 14,
                alignItems: 'center',
              }}
            >
              <Text style={{color: '#FFFFFF', fontSize: 14, fontWeight: '800'}}>
                Apply Filters
              </Text>
            </Pressable>
          </Pressable>
        </Pressable>
      </Modal>
    </SafeAreaView>
  );
}

// Inline SafeAreaView fallback wrapper
function SafeAreaView({children, style, edges}: {children: any; style: any; edges?: string[]}) {
  const insets = require('react-native-safe-area-context').useSafeAreaInsets();
  const paddingTop = edges?.includes('top') ? insets.top : 0;
  return <View style={[{paddingTop}, style]}>{children}</View>;
}
