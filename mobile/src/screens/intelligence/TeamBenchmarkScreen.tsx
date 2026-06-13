import React, {useState, useEffect, useRef} from 'react';
import {
  ActivityIndicator,
  Animated,
  Dimensions,
  Image,
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
import {managerApi, BenchmarkAgent, TeamBenchmarkResponse} from '../../api/manager';
import {createMMKV} from 'react-native-mmkv';

// Instantiate local storage safely
let localStore: any;
try {
  localStore = createMMKV({id: 'invoices-local-store-v1'});
} catch (e) {
  const store: Record<string, string> = {};
  localStore = {
    getString: (key: string) => store[key] || null,
  };
}

const {width: SCREEN_WIDTH} = Dimensions.get('window');

// Custom Sparkline bar component using small views
function MiniSparkline({data, tokens}: {data: number[]; tokens: any}) {
  if (!data || data.length === 0) return null;
  const max = Math.max(...data, 1);
  return (
    <View style={{flexDirection: 'row', alignItems: 'flex-end', height: 26, gap: 4}}>
      {data.map((val, i) => {
        const heightPct = (val / max) * 100;
        return (
          <View
            key={i}
            style={{
              width: 5,
              height: `${Math.max(15, heightPct)}%`,
              backgroundColor: tokens.brandPrimary,
              borderRadius: 3,
              opacity: 0.5 + (i / data.length) * 0.5,
            }}
          />
        );
      })}
    </View>
  );
}

// Sub-component for individual Agent bar rendering
function AgentRankingRow({
  agent,
  maxVal,
  teamAvg,
  index,
  metric,
  tokens,
  expanded,
  onToggle,
}: {
  agent: BenchmarkAgent;
  maxVal: number;
  teamAvg: number;
  index: number;
  metric: string;
  tokens: any;
  expanded: boolean;
  onToggle: () => void;
}) {
  const animatedWidth = useRef(new Animated.Value(0)).current;

  // Animate the bar when agent data or metric changes
  useEffect(() => {
    animatedWidth.setValue(0);
    Animated.spring(animatedWidth, {
      toValue: maxVal > 0 ? agent.value / maxVal : 0,
      tension: 40,
      friction: 8,
      delay: index * 40,
      useNativeDriver: false,
    }).start();
  }, [agent.value, maxVal]);

  const isAboveAverage = agent.value >= teamAvg;
  const barColor = isAboveAverage ? '#10B981' : tokens.borderStrong; // Emerald-500 vs Zinc-300 / Zinc-700 representation

  // Formatter for values
  const formatVal = (val: number) => {
    if (metric === 'Pipeline Value') {
      const symbol = localStore.getString('currency_symbol') || '₦';
      if (val >= 1000000) return `${symbol}${(val / 1000000).toFixed(2)}M`;
      if (val >= 1000) return `${symbol}${(val / 1000).toFixed(0)}k`;
      return `${symbol}${val}`;
    }
    if (metric === 'Sentiment') {
      return `${val}%`;
    }
    return String(val);
  };

  const initials = agent.first_name[0] + agent.last_name[0];
  const accessibilityLabelText = `${agent.first_name} ${agent.last_name}, ${formatVal(
    agent.value
  )} ${metric}, ${isAboveAverage ? 'at or above' : 'below'} team average of ${formatVal(teamAvg)}`;

  return (
    <View
      style={{
        borderBottomWidth: 1,
        borderBottomColor: tokens.borderSubtle,
        backgroundColor: expanded ? tokens.surfaceSunken : 'transparent',
      }}
    >
      <Pressable
        onPress={() => {
          Vibration.vibrate(5);
          onToggle();
        }}
        accessibilityLabel={accessibilityLabelText}
        accessibilityRole="summary"
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          paddingVertical: 14,
          paddingHorizontal: 16,
        }}
      >
        {/* Agent identifier info */}
        <View style={{flexDirection: 'row', alignItems: 'center', width: 110, marginRight: 12}}>
          <View
            style={{
              width: 28,
              height: 28,
              borderRadius: 14,
              backgroundColor: `${tokens.brandPrimary}1D`,
              alignItems: 'center',
              justifyContent: 'center',
              marginRight: 8,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
            }}
          >
            <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 10}}>
              {initials}
            </Text>
          </View>
          <Text
            style={{color: tokens.textPrimary, fontSize: 12, fontWeight: '700'}}
            numberOfLines={1}
          >
            {agent.first_name} {agent.last_name}
          </Text>
        </View>

        {/* Bar & value layout */}
        <View style={{flex: 1, flexDirection: 'row', alignItems: 'center', position: 'relative'}}>
          {/* Animated Bar Track */}
          <View
            style={{
              flex: 1,
              height: 14,
              backgroundColor: tokens.surfaceRaised,
              borderRadius: 7,
              marginRight: 10,
              overflow: 'hidden',
              borderWidth: 0.5,
              borderColor: tokens.borderSubtle,
            }}
          >
            <Animated.View
              style={{
                height: '100%',
                backgroundColor: barColor,
                borderRadius: 7,
                width: animatedWidth.interpolate({
                  inputRange: [0, 1],
                  outputRange: ['0%', '100%'],
                }),
              }}
            />
          </View>

          {/* Value Display */}
          <Text
            style={{
              color: tokens.textPrimary,
              fontSize: 12,
              fontFamily: 'monospace',
              fontWeight: '700',
              textAlign: 'right',
              width: 58,
            }}
          >
            {formatVal(agent.value)}
          </Text>
        </View>
      </Pressable>

      {/* Accordion inline metrics expansion */}
      {expanded && (
        <View
          style={{
            paddingHorizontal: 16,
            paddingBottom: 16,
            paddingTop: 4,
            alignItems: 'center',
          }}
        >
          <View
            style={{
              flexDirection: 'row',
              flexWrap: 'wrap',
              backgroundColor: tokens.surfaceCard,
              borderRadius: 12,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              padding: 10,
              width: '100%',
              gap: 8,
            }}
          >
            {agent.metrics_grid.map((m, idx) => (
              <View
                key={idx}
                style={{
                  width: '48%',
                  padding: 8,
                  justifyContent: 'center',
                }}
              >
                <Text style={{color: tokens.textTertiary, fontSize: 10, fontWeight: '700'}}>
                  {m.label}
                </Text>
                <Text
                  style={{
                    color: tokens.textPrimary,
                    fontSize: 13,
                    fontFamily: 'monospace',
                    fontWeight: '700',
                    marginTop: 2,
                  }}
                >
                  {m.value}
                </Text>
              </View>
            ))}
          </View>
        </View>
      )}
    </View>
  );
}

export function TeamBenchmarkScreen() {
  const {tokens} = useTheme();
  const {user} = useAuthStore();

  const [period, setPeriod] = useState<'This Week' | 'This Month' | 'This Quarter' | 'Custom'>(
    'This Week'
  );
  const [metric, setMetric] = useState<
    'Calls' | 'Pipeline Value' | 'Sentiment' | 'Tasks Completed' | 'Viewings'
  >('Calls');
  const [expandedAgentId, setExpandedAgentId] = useState<number | null>(null);

  // Debug Small Team Mode toggle
  const [smallTeamMode, setSmallTeamMode] = useState(false);

  const isManager =
    (user as any)?.roles?.some((r: string) => r === 'admin' || r === 'manager') ?? false;

  // Retrieve data using React Query
  const {data, isPending, refetch} = useQuery<TeamBenchmarkResponse>({
    queryKey: ['manager', 'benchmark', period, metric, smallTeamMode],
    queryFn: () => managerApi.benchmark(period, metric, smallTeamMode).then((r) => r.data),
  });

  const periods = ['This Week', 'This Month', 'This Quarter', 'Custom'] as const;
  const metrics = ['Calls', 'Pipeline Value', 'Sentiment', 'Tasks Completed', 'Viewings'] as const;

  // Value formatting helpers
  const formatVal = (val: number) => {
    if (metric === 'Pipeline Value') {
      if (val >= 1000000) return `$${(val / 1000000).toFixed(2)}M`;
      if (val >= 1000) return `$${(val / 1000).toFixed(0)}k`;
      return `$${val}`;
    }
    if (metric === 'Sentiment') {
      return `${val}%`;
    }
    return String(val);
  };

  // Safe maximum value for bar width calculation
  const maxAgentValue = data?.agents ? Math.max(...data.agents.map((a) => a.value), 1) : 1;

  // Render original Agent View if user is NOT a manager
  if (!isManager) {
    return (
      <View style={{flex: 1, backgroundColor: tokens.surfacePage, justifyContent: 'center', alignItems: 'center'}}>
        <Text style={{color: tokens.textSecondary}}>Access restricted to managers & admins.</Text>
      </View>
    );
  }

  return (
    <SafeAreaView style={{flex: 1, backgroundColor: tokens.surfacePage}} edges={['top', 'left', 'right']}>
      {/* HEADER SECTION */}
      <View
        style={{
          backgroundColor: tokens.surfaceCard,
          borderBottomWidth: 1,
          borderBottomColor: tokens.borderDefault,
          paddingVertical: 12,
          ...tokens.shadowSm,
        }}
      >
        <View style={{paddingHorizontal: 20, marginBottom: 14}}>
          <Text style={{color: tokens.textPrimary, fontSize: 22, fontWeight: '700', letterSpacing: -0.5}}>
            Team Benchmark
          </Text>
        </View>

        {/* Period Selector Scroll */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, gap: 8}}
          style={{marginBottom: 12}}
        >
          {periods.map((p) => {
            const isActive = period === p;
            return (
              <Pressable
                key={p}
                onPress={() => {
                  Vibration.vibrate(5);
                  setPeriod(p);
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

        {/* Metric Selector Scroll */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, gap: 8}}
        >
          {metrics.map((m) => {
            const isActive = metric === m;
            return (
              <Pressable
                key={m}
                onPress={() => {
                  Vibration.vibrate(5);
                  setMetric(m);
                  setExpandedAgentId(null);
                }}
                style={{
                  paddingHorizontal: 12,
                  paddingVertical: 6,
                  borderRadius: 999,
                  backgroundColor: isActive ? tokens.brandPrimary : tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: isActive ? tokens.brandPrimary : tokens.borderDefault,
                }}
              >
                <Text
                  style={{
                    fontSize: 11,
                    fontWeight: '700',
                    color: isActive ? '#FFFFFF' : tokens.textTertiary,
                  }}
                >
                  {m}
                </Text>
              </Pressable>
            );
          })}
        </ScrollView>
      </View>

      {/* SCROLL CONTAINER BODY */}
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 40, paddingTop: 16}}
        showsVerticalScrollIndicator={false}
      >
        {isPending ? (
          <View style={{paddingVertical: 60, alignItems: 'center'}}>
            <ActivityIndicator color={tokens.brandPrimary} />
          </View>
        ) : data?.is_small_team ? (
          /* SMALL TEAM FALLBACK STATE */
          <View style={{paddingHorizontal: 20}}>
            <View
              style={{
                backgroundColor: tokens.surfaceCard,
                borderRadius: 16,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                padding: 24,
                marginBottom: 20,
                alignItems: 'center',
                ...tokens.shadowSm,
              }}
            >
              <Icon name="info" size={24} color={tokens.brandPrimary} style={{marginBottom: 8}} />
              <Text
                style={{
                  color: tokens.textPrimary,
                  fontWeight: '700',
                  textAlign: 'center',
                  fontSize: 14,
                  lineHeight: 20,
                }}
              >
                Benchmarking works best with 3+ agents — here's how your team is trending instead
              </Text>
            </View>

            {/* Flat Sparklines Trend List */}
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
              Team Trends
            </Text>

            <View style={{gap: 12}}>
              {metrics.map((met) => (
                <View
                  key={met}
                  style={{
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    flexDirection: 'row',
                    alignItems: 'center',
                    justifyContent: 'space-between',
                    ...tokens.shadowSm,
                  }}
                >
                  <View>
                    <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>
                      {met} Trend
                    </Text>
                    <Text style={{color: tokens.textTertiary, fontSize: 11, marginTop: 2}}>
                      Weekly aggregate trajectory
                    </Text>
                  </View>
                  <MiniSparkline data={[10, 15, 12, 18, 22, 19, 23]} tokens={tokens} />
                </View>
              ))}
            </View>
          </View>
        ) : (
          /* CORE BENCHMARK LAYOUT */
          <View>
            {/* TEAM AVERAGE CARD */}
            <View style={{paddingHorizontal: 20, marginBottom: 24}}>
              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  padding: 18,
                  flexDirection: 'row',
                  alignItems: 'center',
                  justifyContent: 'space-between',
                  ...tokens.shadowSm,
                }}
              >
                <View style={{flex: 1, marginRight: 12}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>
                    TEAM AVERAGE
                  </Text>
                  <Text
                    style={{
                      color: tokens.textPrimary,
                      fontSize: 14,
                      fontWeight: '700',
                      marginTop: 4,
                    }}
                  >
                    {data?.team_average_label}
                  </Text>
                </View>

                {/* Sparkline trend representation */}
                <View style={{alignItems: 'flex-end'}}>
                  <MiniSparkline data={data?.sparkline || []} tokens={tokens} />
                </View>
              </View>
            </View>

            {/* AGENT RANKING horizontal bar chart */}
            <View style={{paddingHorizontal: 20, marginBottom: 28}}>
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
                Agent Comparison
              </Text>

              {/* Bar Chart Container */}
              <View
                style={{
                  backgroundColor: tokens.surfaceCard,
                  borderWidth: 1,
                  borderColor: tokens.borderDefault,
                  borderRadius: 16,
                  overflow: 'hidden',
                  position: 'relative',
                  ...tokens.shadowSm,
                }}
              >
                {/* Vertical Dashed Team Average Line */}
                {data && maxAgentValue > 0 && (
                  <View
                    style={{
                      position: 'absolute',
                      // Shift to account for name label column offset width (110px label + 16px padding)
                      left: 126 + (data.team_average / maxAgentValue) * (SCREEN_WIDTH - 248),
                      top: 0,
                      bottom: 0,
                      width: 1,
                      borderStyle: 'dashed',
                      borderWidth: 1,
                      borderColor: tokens.borderStrong,
                      zIndex: 10,
                    }}
                  />
                )}

                {/* Rendered agent bars */}
                {data?.agents.map((agent, index) => (
                  <AgentRankingRow
                    key={agent.id}
                    agent={agent}
                    maxVal={maxAgentValue}
                    teamAvg={data.team_average}
                    index={index}
                    metric={metric}
                    tokens={tokens}
                    expanded={expandedAgentId === agent.id}
                    onToggle={() =>
                      setExpandedAgentId(expandedAgentId === agent.id ? null : agent.id)
                    }
                  />
                ))}
              </View>
            </View>

            {/* AI INSIGHT CARD */}
            {data?.ai_insight && (
              <View style={{paddingHorizontal: 20, marginBottom: 24}}>
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
                      {data.ai_insight}
                    </Text>
                  </View>
                </View>
              </View>
            )}
          </View>
        )}

        {/* Small Team Mode Switcher (WOW Debug validation switcher) */}
        <View style={{paddingHorizontal: 20, alignItems: 'center', marginTop: 12}}>
          <Pressable
            onPress={() => {
              Vibration.vibrate(10);
              setSmallTeamMode(!smallTeamMode);
            }}
            style={{
              paddingVertical: 8,
              paddingHorizontal: 14,
              borderRadius: 999,
              backgroundColor: tokens.surfaceRaised,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              flexDirection: 'row',
              alignItems: 'center',
              gap: 6,
            }}
          >
            <Icon name="cpu" size={12} color={tokens.brandPrimary} />
            <Text style={{color: tokens.textSecondary, fontSize: 10, fontWeight: '700'}}>
              Simulate: {smallTeamMode ? 'Small Team (<3 Agents)' : 'Large Agency (7 Agents)'}
            </Text>
          </Pressable>
        </View>
      </ScrollView>
    </SafeAreaView>
  );
}

// Inline fallback wrapper for SafeAreaView if not imported
function SafeAreaView({children, style, edges}: {children: any; style: any; edges?: string[]}) {
  const insets = require('react-native-safe-area-context').useSafeAreaInsets();
  const paddingTop = edges?.includes('top') ? insets.top : 0;
  return <View style={[{paddingTop}, style]}>{children}</View>;
}
