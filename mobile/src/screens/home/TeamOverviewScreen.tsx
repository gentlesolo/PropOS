import React, {useState, useEffect, useRef, useMemo} from 'react';
import {
  Pressable,
  ScrollView,
  Text,
  View,
  Animated,
  Dimensions,
  Image,
  Modal,
  ActivityIndicator,
  Linking,
  Vibration,
} from 'react-native';
import Icon from 'react-native-vector-icons/Feather';
import {format} from 'date-fns';
import {useQuery, useMutation, useQueryClient} from '@tanstack/react-query';
import {useNavigation} from '@react-navigation/native';
import {useAuthStore} from '../../store/authStore';
import {useNotificationStore} from '../../store/notificationStore';
import {storage} from '../../api/client';
import {useTheme} from '../../theme/ThemeProvider';
import {managerApi, TeamSnapshot, AgentRow, AttentionItem} from '../../api/manager';

const {width: SCREEN_WIDTH} = Dimensions.get('window');

type SortOption = 'activity' | 'pipeline' | 'sentiment';

export function TeamOverviewScreen({
  onToggleTab,
  currentTab,
  showToggle,
}: {
  onToggleTab?: (tab: 'My Day' | 'Team') => void;
  currentTab?: 'My Day' | 'Team';
  showToggle?: boolean;
}) {
  const {tokens} = useTheme();
  const {user} = useAuthStore();
  const {unreadCount} = useNotificationStore();
  const navigation = useNavigation<any>();
  const queryClient = useQueryClient();

  // Sort and display states
  const [sortBy, setSortBy] = useState<SortOption>('activity');
  const [selectedAgent, setSelectedAgent] = useState<AgentRow | null>(null);
  const [smallTeamMode, setSmallTeamMode] = useState<boolean>(false);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [showCheckmark, setShowCheckmark] = useState(false);

  // Animated heights & shimmer opacity
  const refreshHeaderHeight = useRef(new Animated.Value(0)).current;
  const skeletonOpacity = useRef(new Animated.Value(0.3)).current;

  // Retrieve cached data from MMKV
  const [cachedSnapshot] = useState<TeamSnapshot | null>(() => {
    const raw = storage.getString('manager_snapshot_cached');
    if (raw) {
      try {
        return JSON.parse(raw);
      } catch (e) {
        return null;
      }
    }
    return null;
  });

  // Check roles (Manager/Admin redirect gate)
  const isManager = (user as any)?.roles?.some?.(
    (r: string) => r === 'admin' || r === 'manager'
  ) ?? false;

  useEffect(() => {
    if (!isManager) {
      // Redirect silently to Home My Day
      if (onToggleTab) {
        onToggleTab('My Day');
      }
    }
  }, [isManager]);

  // Shimmer pulse animation
  useEffect(() => {
    const shimmer = Animated.loop(
      Animated.sequence([
        Animated.timing(skeletonOpacity, {toValue: 0.7, duration: 900, useNativeDriver: true}),
        Animated.timing(skeletonOpacity, {toValue: 0.3, duration: 900, useNativeDriver: true}),
      ])
    );
    shimmer.start();
    return () => shimmer.stop();
  }, []);

  // Fetch Team snapshot
  const {
    data: snapshot,
    refetch,
    isPending,
    isFetching,
  } = useQuery({
    queryKey: ['manager', 'snapshot', smallTeamMode],
    queryFn: () => managerApi.snapshot(smallTeamMode).then((r) => r.data),
    initialData: cachedSnapshot || undefined,
  });

  // Cache updates to MMKV
  useEffect(() => {
    if (snapshot) {
      storage.set('manager_snapshot_cached', JSON.stringify(snapshot));
    }
  }, [snapshot]);

  // Custom Refresh handler
  const [pullDistance, setPullDistance] = useState(0);
  const handleScroll = (event: any) => {
    const y = event.nativeEvent.contentOffset.y;
    setPullDistance(y < 0 ? -y : 0);
  };

  const handleScrollEnd = () => {
    if (pullDistance > 85 && !isRefreshing) {
      triggerRefresh();
    }
  };

  const triggerRefresh = async () => {
    setIsRefreshing(true);
    Animated.spring(refreshHeaderHeight, {toValue: 60, useNativeDriver: false}).start();
    try {
      await refetch();
      Vibration.vibrate(10);
    } catch (e) {
      console.warn('Refresh error:', e);
    }
    setShowCheckmark(true);
    setTimeout(() => {
      Animated.timing(refreshHeaderHeight, {toValue: 0, duration: 300, useNativeDriver: false}).start(() => {
        setIsRefreshing(false);
        setShowCheckmark(false);
      });
    }, 600);
  };

  // Sort and process agents list
  const sortedAgents = useMemo(() => {
    if (!snapshot?.agents) return [];
    const list = [...snapshot.agents];
    if (sortBy === 'activity') {
      return list.sort((a, b) => (b.calls_today + b.viewings_today) - (a.calls_today + a.viewings_today));
    } else if (sortBy === 'pipeline') {
      return list.sort((a, b) => b.pipeline_value - a.pipeline_value);
    } else if (sortBy === 'sentiment') {
      return list.sort((a, b) => b.avg_sentiment_score - a.avg_sentiment_score);
    }
    return list;
  }, [snapshot?.agents, sortBy]);

  // Helper formatters
  const formatCurrency = (val: number) => {
    if (val >= 1000000) return `$${(val / 1000000).toFixed(1)}M`;
    if (val >= 1000) return `$${(val / 1000).toFixed(0)}k`;
    return `$${val}`;
  };

  const formatDelta = (delta: number) => {
    const icon = delta >= 0 ? '↑' : '↓';
    return `${icon} ${Math.abs(delta)}%`;
  };

  if (!isManager) {
    return null;
  }

  const isSmallTeam = snapshot?.agents ? snapshot.agents.length < 3 : false;

  return (
    <View style={{flex: 1, backgroundColor: tokens.surfacePage}}>
      {/* Scrollable Dashboard */}
      <ScrollView
        style={{flex: 1}}
        contentContainerStyle={{paddingBottom: 40, paddingTop: 8}}
        onScroll={handleScroll}
        scrollEventThrottle={16}
        onScrollEndDrag={handleScrollEnd}
        showsVerticalScrollIndicator={false}
      >
        {/* Pull to refresh indicator */}
        <Animated.View
          style={{
            height: refreshHeaderHeight,
            width: '100%',
            alignItems: 'center',
            justifyContent: 'center',
            overflow: 'hidden',
          }}
        >
          <View
            style={{
              width: 40,
              height: 40,
              borderRadius: 20,
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              alignItems: 'center',
              justifyContent: 'center',
              ...tokens.shadowSm,
            }}
          >
            {showCheckmark ? (
              <Icon name="check" size={20} color={tokens.brandPrimary} />
            ) : (
              <ActivityIndicator size="small" color={tokens.brandPrimary} />
            )}
          </View>
        </Animated.View>

        {/* TEAM PULSE STRIP */}
        <ScrollView
          horizontal
          showsHorizontalScrollIndicator={false}
          contentContainerStyle={{paddingHorizontal: 20, paddingVertical: 12}}
          style={{marginBottom: 20}}
        >
          {/* Active Agents Chip */}
          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 999,
              paddingHorizontal: 16,
              paddingVertical: 10,
              marginRight: 12,
              ...tokens.shadowSm,
            }}
          >
            <Icon name="users" size={15} color={tokens.brandPrimary} style={{marginRight: 8}} />
            <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800', marginRight: 4}}>
              {snapshot?.agents_active_today ?? 0}
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '600'}}>active today</Text>
          </View>

          {/* Calls Today Chip */}
          <View
            style={{
              flexDirection: 'row',
              alignItems: 'center',
              backgroundColor: tokens.surfaceCard,
              borderWidth: 1,
              borderColor: tokens.borderDefault,
              borderRadius: 999,
              paddingHorizontal: 16,
              paddingVertical: 10,
              marginRight: 12,
              ...tokens.shadowSm,
            }}
          >
            <Icon name="phone" size={15} color={tokens.brandPrimary} style={{marginRight: 8}} />
            <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '800', marginRight: 4}}>
              {snapshot?.calls_today ?? 0}
            </Text>
            <Text style={{color: tokens.textSecondary, fontSize: 11, fontWeight: '600'}}>calls today</Text>
          </View>

          {/* Deals at Risk Chip */}
          {snapshot && (
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: snapshot.deals_at_risk > 0 ? tokens.warningBg : tokens.surfaceCard,
                borderWidth: 1,
                borderColor: snapshot.deals_at_risk > 0 ? tokens.warningBorder : tokens.borderDefault,
                borderRadius: 999,
                paddingHorizontal: 16,
                paddingVertical: 10,
                marginRight: 12,
                ...tokens.shadowSm,
              }}
            >
              <Icon
                name="alert-triangle"
                size={15}
                color={snapshot.deals_at_risk > 0 ? tokens.warningText : tokens.textTertiary}
                style={{marginRight: 8}}
              />
              <Text
                style={{
                  color: snapshot.deals_at_risk > 0 ? tokens.warningText : tokens.textPrimary,
                  fontSize: 13,
                  fontWeight: '800',
                  marginRight: 4,
                }}
              >
                {snapshot.deals_at_risk}
              </Text>
              <Text
                style={{
                  color: snapshot.deals_at_risk > 0 ? tokens.warningText : tokens.textSecondary,
                  fontSize: 11,
                  fontWeight: '600',
                }}
              >
                deals at risk
              </Text>
            </View>
          )}

          {/* Coaching Flags Chip */}
          {snapshot && (
            <View
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                backgroundColor: snapshot.coaching_flags > 0 ? tokens.dangerBg : tokens.surfaceCard,
                borderWidth: 1,
                borderColor: snapshot.coaching_flags > 0 ? tokens.dangerBorder : tokens.borderDefault,
                borderRadius: 999,
                paddingHorizontal: 16,
                paddingVertical: 10,
                marginRight: 12,
                ...tokens.shadowSm,
              }}
            >
              <Icon
                name="flag"
                size={15}
                color={snapshot.coaching_flags > 0 ? tokens.dangerText : tokens.textTertiary}
                style={{marginRight: 8}}
              />
              <Text
                style={{
                  color: snapshot.coaching_flags > 0 ? tokens.dangerText : tokens.textPrimary,
                  fontSize: 13,
                  fontWeight: '800',
                  marginRight: 4,
                }}
              >
                {snapshot.coaching_flags}
              </Text>
              <Text
                style={{
                  color: snapshot.coaching_flags > 0 ? tokens.dangerText : tokens.textSecondary,
                  fontSize: 11,
                  fontWeight: '600',
                }}
              >
                coaching flags
              </Text>
            </View>
          )}
        </ScrollView>

        {/* AGENCY SNAPSHOT (2x2 Grid) */}
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
            Agency Snapshot
          </Text>

          {isPending && !snapshot ? (
            // Grid Skeleton Loader
            <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 12}}>
              {[1, 2, 3, 4].map((i) => (
                <Animated.View
                  key={i}
                  style={{
                    width: (SCREEN_WIDTH - 52) / 2,
                    height: 96,
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    opacity: skeletonOpacity,
                  }}
                >
                  <View style={{height: 24, width: '60%', backgroundColor: tokens.surfaceRaised, borderRadius: 6, marginBottom: 8}} />
                  <View style={{height: 12, width: '40%', backgroundColor: tokens.surfaceRaised, borderRadius: 4}} />
                </Animated.View>
              ))}
            </View>
          ) : (
            <View style={{flexDirection: 'row', flexWrap: 'wrap', gap: 12}}>
              {/* Card 1: Pipeline Value */}
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
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Pipeline Value</Text>
                  <Text
                    style={{
                      fontSize: 10,
                      fontWeight: '800',
                      color: (snapshot?.total_pipeline_delta_pct ?? 0) >= 0 ? tokens.successText : tokens.dangerText,
                    }}
                  >
                    {formatDelta(snapshot?.total_pipeline_delta_pct ?? 0)}
                  </Text>
                </View>
                <Text
                  style={{
                    color: tokens.textPrimary,
                    fontSize: 22,
                    fontFamily: 'monospace',
                    fontWeight: '700',
                    marginTop: 8,
                  }}
                >
                  {formatCurrency(snapshot?.total_pipeline_value ?? 0)}
                </Text>
              </View>

              {/* Card 2: Calls Today */}
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
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Calls Today</Text>
                  <Text
                    style={{
                      fontSize: 10,
                      fontWeight: '800',
                      color: (snapshot?.calls_delta_pct ?? 0) >= 0 ? tokens.successText : tokens.dangerText,
                    }}
                  >
                    {formatDelta(snapshot?.calls_delta_pct ?? 0)}
                  </Text>
                </View>
                <Text
                  style={{
                    color: tokens.textPrimary,
                    fontSize: 22,
                    fontFamily: 'monospace',
                    fontWeight: '700',
                    marginTop: 8,
                  }}
                >
                  {snapshot?.calls_today ?? 0}
                </Text>
              </View>

              {/* Card 3: Avg Sentiment Score (Includes Visual Slider) */}
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
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Avg Sentiment</Text>
                  <Text
                    style={{
                      fontSize: 10,
                      fontWeight: '800',
                      color: (snapshot?.sentiment_delta_pct ?? 0) >= 0 ? tokens.successText : tokens.dangerText,
                    }}
                  >
                    {formatDelta(snapshot?.sentiment_delta_pct ?? 0)}
                  </Text>
                </View>
                <View style={{marginTop: 6}}>
                  <Text
                    style={{
                      color: tokens.textPrimary,
                      fontSize: 20,
                      fontFamily: 'monospace',
                      fontWeight: '700',
                    }}
                  >
                    {snapshot?.avg_sentiment_score ?? 0}%
                  </Text>

                  {/* Gradient Bar (Danger -> Amber -> Emerald) with dot indicator */}
                  <View style={{marginTop: 8, height: 6, borderRadius: 3, overflow: 'hidden', position: 'relative'}}>
                    {/* Linear color sections approximation */}
                    <View style={{flexDirection: 'row', height: '100%', width: '100%'}}>
                      <View style={{flex: 1, backgroundColor: tokens.dangerText}} />
                      <View style={{flex: 1, backgroundColor: tokens.brandAccent}} />
                      <View style={{flex: 1, backgroundColor: tokens.brandPrimary}} />
                    </View>
                    {/* Marker */}
                    <View
                      style={{
                        position: 'absolute',
                        left: `${Math.min(Math.max((snapshot?.avg_sentiment_score ?? 50) - 4, 2), 94)}%`,
                        top: 0,
                        width: 6,
                        height: 6,
                        borderRadius: 3,
                        backgroundColor: '#FFFFFF',
                        borderWidth: 1,
                        borderColor: '#000000',
                      }}
                    />
                  </View>
                </View>
              </View>

              {/* Card 4: Active Listings */}
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
                <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start'}}>
                  <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>Active Listings</Text>
                  <Text
                    style={{
                      fontSize: 10,
                      fontWeight: '800',
                      color: (snapshot?.listings_delta ?? 0) >= 0 ? tokens.successText : tokens.dangerText,
                    }}
                  >
                    {snapshot?.listings_delta !== undefined && snapshot.listings_delta !== 0
                      ? `${snapshot.listings_delta >= 0 ? '↑' : '↓'} ${Math.abs(snapshot.listings_delta)}`
                      : '—'}
                  </Text>
                </View>
                <Text
                  style={{
                    color: tokens.textPrimary,
                    fontSize: 22,
                    fontFamily: 'monospace',
                    fontWeight: '700',
                    marginTop: 8,
                  }}
                >
                  {snapshot?.active_listings ?? 0}
                </Text>
              </View>
            </View>
          )}
        </View>

        {/* ATTENTION NEEDED (Priority Cards) */}
        <View style={{paddingHorizontal: 20, marginBottom: 32}}>
          <View style={{flexDirection: 'row', alignItems: 'center', gap: 6, marginBottom: 16}}>
            <Icon name="zap" size={16} color={tokens.brandPrimary} />
            <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '700', letterSpacing: -0.3}}>
              Needs your attention
            </Text>
          </View>

          {snapshot?.attention_items && snapshot.attention_items.length > 0 ? (
            snapshot.attention_items.map((item) => {
              const borderLeftColor =
                item.urgency === 'danger'
                  ? tokens.dangerText
                  : item.urgency === 'amber'
                  ? tokens.brandAccent
                  : tokens.brandPrimary;

              return (
                <View
                  key={item.id}
                  style={{
                    backgroundColor: tokens.surfaceCard,
                    borderWidth: 1,
                    borderColor: tokens.borderDefault,
                    borderRadius: 16,
                    padding: 16,
                    marginBottom: 12,
                    flexDirection: 'row',
                    alignItems: 'center',
                    position: 'relative',
                    overflow: 'hidden',
                    ...tokens.shadowSm,
                  }}
                >
                  {/* Left urgency colored edge bar */}
                  <View
                    style={{
                      position: 'absolute',
                      left: 0,
                      top: 12,
                      bottom: 12,
                      width: 4,
                      borderRadius: 2,
                      backgroundColor: borderLeftColor,
                    }}
                  />

                  {/* Agent Info & Issue */}
                  <View style={{flexDirection: 'row', alignItems: 'center', flex: 1, paddingLeft: 6, marginRight: 8}}>
                    {item.agent_avatar ? (
                      <Image source={{uri: item.agent_avatar}} style={{width: 36, height: 36, borderRadius: 18, marginRight: 12}} />
                    ) : (
                      <View
                        style={{
                          width: 36,
                          height: 36,
                          borderRadius: 18,
                          backgroundColor: tokens.surfaceRaised,
                          borderWidth: 1,
                          borderColor: tokens.borderDefault,
                          alignItems: 'center',
                          justifyContent: 'center',
                          marginRight: 12,
                        }}
                      >
                        <Text style={{color: tokens.textSecondary, fontSize: 12, fontWeight: '800'}}>
                          {item.agent_initials}
                        </Text>
                      </View>
                    )}
                    <View style={{flex: 1}}>
                      <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>{item.agent_name}</Text>
                      <Text style={{color: tokens.textSecondary, fontSize: 12, marginTop: 2, lineHeight: 17}}>
                        {item.issue}
                      </Text>
                    </View>
                  </View>

                  {/* Right Action Nudge button */}
                  <Pressable
                    onPress={() => {
                      Vibration.vibrate(10);
                      if (item.action_type === 'message') {
                        navigation.navigate('Inbox');
                      } else {
                        // Open agent's mini profile modal contextually or go to Intelligence Screen
                        const foundAgent = snapshot.agents.find((a) => a.id === item.agent_id);
                        if (foundAgent) {
                          setSelectedAgent(foundAgent);
                        } else {
                          navigation.navigate('Intelligence', {screen: 'ManagerDashboard'});
                        }
                      }
                    }}
                    style={{
                      width: 38,
                      height: 38,
                      borderRadius: 19,
                      backgroundColor: `${tokens.brandPrimary}1A`,
                      alignItems: 'center',
                      justifyContent: 'center',
                      borderWidth: 1,
                      borderColor: `${tokens.brandPrimary}22`,
                    }}
                  >
                    <Icon
                      name={item.action_type === 'message' ? 'message-square' : 'eye'}
                      size={16}
                      color={tokens.brandPrimary}
                    />
                  </Pressable>
                </View>
              );
            })
          ) : (
            <View
              style={{
                backgroundColor: tokens.surfaceCard,
                borderRadius: 16,
                padding: 24,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                alignItems: 'center',
              }}
            >
              <Icon name="check-circle" size={32} color={tokens.brandPrimary} style={{marginBottom: 8}} />
              <Text style={{color: tokens.textPrimary, fontWeight: '700'}}>All items attended to</Text>
              <Text style={{color: tokens.textTertiary, fontSize: 11, marginTop: 2}}>No flags or stuck deals today.</Text>
            </View>
          )}
        </View>

        {/* AGENT ACTIVITY LIST */}
        <View style={{paddingHorizontal: 20, marginBottom: 32}}>
          <View style={{flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 14}}>
            <Text style={{color: tokens.textPrimary, fontSize: 17, fontWeight: '700', letterSpacing: -0.3}}>
              Agent Activity
            </Text>

            {/* Sort options (Hidden if isSmallTeam mode < 3 agents) */}
            {!isSmallTeam && (
              <View style={{flexDirection: 'row', gap: 8}}>
                <Pressable onPress={() => setSortBy('activity')}>
                  <Text
                    style={{
                      fontSize: 11,
                      fontWeight: '700',
                      color: sortBy === 'activity' ? tokens.brandPrimary : tokens.textTertiary,
                    }}
                  >
                    Activity
                  </Text>
                </Pressable>
                <Text style={{color: tokens.textDisabled, fontSize: 11}}>·</Text>
                <Pressable onPress={() => setSortBy('pipeline')}>
                  <Text
                    style={{
                      fontSize: 11,
                      fontWeight: '700',
                      color: sortBy === 'pipeline' ? tokens.brandPrimary : tokens.textTertiary,
                    }}
                  >
                    Pipeline
                  </Text>
                </Pressable>
                <Text style={{color: tokens.textDisabled, fontSize: 11}}>·</Text>
                <Pressable onPress={() => setSortBy('sentiment')}>
                  <Text
                    style={{
                      fontSize: 11,
                      fontWeight: '700',
                      color: sortBy === 'sentiment' ? tokens.brandPrimary : tokens.textTertiary,
                    }}
                  >
                    Sentiment
                  </Text>
                </Pressable>
              </View>
            )}
          </View>

          {isPending && !snapshot ? (
            // Skeleton for Agent Rows
            <View style={{backgroundColor: tokens.surfaceCard, borderWidth: 1, borderColor: tokens.borderDefault, borderRadius: 16, padding: 8}}>
              {[1, 2, 3].map((i) => (
                <View key={i} style={{flexDirection: 'row', alignItems: 'center', padding: 12, gap: 12}}>
                  <View style={{width: 32, height: 32, borderRadius: 16, backgroundColor: tokens.surfaceRaised}} />
                  <View style={{flex: 1, gap: 6}}>
                    <View style={{height: 12, width: '40%', backgroundColor: tokens.surfaceRaised, borderRadius: 4}} />
                    <View style={{height: 8, width: '60%', backgroundColor: tokens.surfaceRaised, borderRadius: 3}} />
                  </View>
                </View>
              ))}
            </View>
          ) : (
            <View
              style={{
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                overflow: 'hidden',
                padding: 6,
                ...tokens.shadowSm,
              }}
            >
              {sortedAgents.map((agent, index) => {
                const sentimentTrendIcon =
                  agent.sentiment_trend === 'up'
                    ? 'trending-up'
                    : agent.sentiment_trend === 'down'
                    ? 'trending-down'
                    : 'arrow-right';

                const sentimentTrendColor =
                  agent.sentiment_trend === 'up'
                    ? tokens.successText
                    : agent.sentiment_trend === 'down'
                    ? tokens.dangerText
                    : tokens.textTertiary;

                return (
                  <Pressable
                    key={agent.id}
                    onPress={() => {
                      Vibration.vibrate(5);
                      setSelectedAgent(agent);
                    }}
                    style={({pressed}) => ({
                      flexDirection: 'row',
                      alignItems: 'center',
                      padding: 12,
                      borderBottomWidth: index < sortedAgents.length - 1 ? 1 : 0,
                      borderBottomColor: tokens.borderSubtle,
                      backgroundColor: pressed ? tokens.statePressedBg : 'transparent',
                    })}
                  >
                    {/* Status dot + Avatar */}
                    <View style={{position: 'relative', marginRight: 12}}>
                      <View
                        style={{
                          width: 36,
                          height: 36,
                          borderRadius: 18,
                          backgroundColor: `${tokens.brandPrimary}1A`,
                          alignItems: 'center',
                          justifyContent: 'center',
                          borderWidth: 1,
                          borderColor: tokens.borderDefault,
                        }}
                      >
                        <Text style={{color: tokens.brandPrimary, fontWeight: '800', fontSize: 13}}>
                          {agent.first_name[0]}
                          {agent.last_name[0]}
                        </Text>
                      </View>
                      {/* Active green dot or zinc inactive dot */}
                      <View
                        style={{
                          position: 'absolute',
                          bottom: 0,
                          right: 0,
                          width: 10,
                          height: 10,
                          borderRadius: 5,
                          backgroundColor: agent.active_today ? '#10B981' : '#71717A',
                          borderWidth: 1.5,
                          borderColor: tokens.surfaceCard,
                        }}
                      />
                    </View>

                    {/* Agent Name & Metrics */}
                    <View style={{flex: 1}}>
                      <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700'}}>
                        {agent.first_name} {agent.last_name}
                      </Text>
                      <Text style={{color: tokens.textSecondary, fontSize: 11, marginTop: 3}}>
                        {agent.calls_today} calls · {agent.viewings_today} viewings · {formatCurrency(agent.pipeline_value)}
                      </Text>
                    </View>

                    {/* Sentiment Trend Indicator */}
                    <View style={{flexDirection: 'row', alignItems: 'center', gap: 4}}>
                      <Icon name={sentimentTrendIcon} size={14} color={sentimentTrendColor} />
                      <Text style={{color: tokens.textTertiary, fontSize: 11, fontWeight: '700'}}>
                        {agent.avg_sentiment_score}%
                      </Text>
                    </View>
                  </Pressable>
                );
              })}
            </View>
          )}
        </View>

        {/* QUICK LINKS ROW (3 tiles across) */}
        <View style={{paddingHorizontal: 20, marginBottom: 20}}>
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
            Quick Links
          </Text>

          <View style={{flexDirection: 'row', gap: 10}}>
            {/* Link 1: Team Benchmark */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(10);
                navigation.navigate('Intelligence', {screen: 'Benchmark'});
              }}
              style={({pressed}) => ({
                flex: 1,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                paddingVertical: 16,
                paddingHorizontal: 12,
                alignItems: 'center',
                opacity: pressed ? 0.85 : 1,
                ...tokens.shadowSm,
              })}
            >
              <View
                style={{
                  width: 36,
                  height: 36,
                  borderRadius: 18,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 8,
                }}
              >
                <Icon name="bar-chart-2" size={16} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '700', textAlign: 'center'}}>
                Team Benchmark
              </Text>
            </Pressable>

            {/* Link 2: Call Analytics */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(10);
                navigation.navigate('Intelligence', {screen: 'Analytics'});
              }}
              style={({pressed}) => ({
                flex: 1,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                paddingVertical: 16,
                paddingHorizontal: 12,
                alignItems: 'center',
                opacity: pressed ? 0.85 : 1,
                ...tokens.shadowSm,
              })}
            >
              <View
                style={{
                  width: 36,
                  height: 36,
                  borderRadius: 18,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 8,
                }}
              >
                <Icon name="activity" size={16} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '700', textAlign: 'center'}}>
                Call Analytics
              </Text>
            </Pressable>

            {/* Link 3: Coaching Queue */}
            <Pressable
              onPress={() => {
                Vibration.vibrate(10);
                // Navigates to Call history screen with state to review calls
                navigation.navigate('Calls', {screen: 'CallHistory', params: {coachingOnly: true}});
              }}
              style={({pressed}) => ({
                flex: 1,
                backgroundColor: tokens.surfaceCard,
                borderWidth: 1,
                borderColor: tokens.borderDefault,
                borderRadius: 16,
                paddingVertical: 16,
                paddingHorizontal: 12,
                alignItems: 'center',
                opacity: pressed ? 0.85 : 1,
                ...tokens.shadowSm,
              })}
            >
              <View
                style={{
                  width: 36,
                  height: 36,
                  borderRadius: 18,
                  backgroundColor: `${tokens.brandPrimary}1A`,
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: 8,
                }}
              >
                <Icon name="headphones" size={16} color={tokens.brandPrimary} />
              </View>
              <Text style={{color: tokens.textPrimary, fontSize: 11, fontWeight: '700', textAlign: 'center'}}>
                Coaching Queue
              </Text>
            </Pressable>
          </View>
        </View>

        {/* Small Team Mode Switcher (WOW Debug element for validation) */}
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
              Simulate: {smallTeamMode ? 'Small Team (<3 Agents)' : 'Large Agency (12 Agents)'}
            </Text>
          </Pressable>
        </View>
      </ScrollView>

      {/* MINI PROFILE READ-ONLY DIALOG / MODAL */}
      <Modal
        visible={selectedAgent !== null}
        transparent
        animationType="fade"
        onRequestClose={() => setSelectedAgent(null)}
      >
        <Pressable
          style={{
            flex: 1,
            backgroundColor: tokens.surfaceOverlay,
            justifyContent: 'center',
            alignItems: 'center',
            padding: 20,
          }}
          onPress={() => setSelectedAgent(null)}
        >
          <Pressable
            style={{
              width: '100%',
              maxWidth: 340,
              backgroundColor: tokens.surfaceCard,
              borderRadius: 24,
              borderWidth: 1,
              borderColor: tokens.borderStrong,
              padding: 24,
              ...tokens.shadowMd,
            }}
            onPress={(e) => e.stopPropagation()}
          >
            {/* Header info */}
            {selectedAgent && (
              <View style={{alignItems: 'center', marginBottom: 20}}>
                <View
                  style={{
                    width: 64,
                    height: 64,
                    borderRadius: 32,
                    backgroundColor: `${tokens.brandPrimary}1A`,
                    alignItems: 'center',
                    justifyContent: 'center',
                    borderWidth: 1.5,
                    borderColor: tokens.brandPrimary,
                    marginBottom: 10,
                  }}
                >
                  <Text style={{color: tokens.brandPrimary, fontSize: 22, fontWeight: '800'}}>
                    {selectedAgent.first_name[0]}
                    {selectedAgent.last_name[0]}
                  </Text>
                </View>
                <Text style={{color: tokens.textPrimary, fontSize: 18, fontWeight: '800'}}>
                  {selectedAgent.first_name} {selectedAgent.last_name}
                </Text>
                <Text style={{color: tokens.textTertiary, fontSize: 12, marginTop: 2}}>
                  Active Today: {selectedAgent.active_today ? 'Yes (Online)' : 'No (Offline)'}
                </Text>
              </View>
            )}

            {/* Read-Only Stats */}
            {selectedAgent && (
              <View style={{gap: 12, marginBottom: 20}}>
                {/* Stat 1: Pipeline Value */}
                <View
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingBottom: 10,
                    borderBottomWidth: 1,
                    borderBottomColor: tokens.borderSubtle,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 13}}>Pipeline Value</Text>
                  <Text style={{color: tokens.textPrimary, fontSize: 14, fontFamily: 'monospace', fontWeight: '700'}}>
                    {formatCurrency(selectedAgent.pipeline_value)}
                  </Text>
                </View>

                {/* Stat 2: Daily Activity summary */}
                <View
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingBottom: 10,
                    borderBottomWidth: 1,
                    borderBottomColor: tokens.borderSubtle,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 13}}>Calls today</Text>
                  <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700'}}>
                    {selectedAgent.calls_today}
                  </Text>
                </View>

                {/* Stat 3: Viewings */}
                <View
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingBottom: 10,
                    borderBottomWidth: 1,
                    borderBottomColor: tokens.borderSubtle,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 13}}>Viewings today</Text>
                  <Text style={{color: tokens.textPrimary, fontSize: 14, fontWeight: '700'}}>
                    {selectedAgent.viewings_today}
                  </Text>
                </View>

                {/* Stat 4: Sentiment */}
                <View
                  style={{
                    flexDirection: 'row',
                    justifyContent: 'space-between',
                    alignItems: 'center',
                    paddingBottom: 10,
                  }}
                >
                  <Text style={{color: tokens.textSecondary, fontSize: 13}}>Avg sentiment</Text>
                  <Text style={{color: tokens.brandPrimary, fontSize: 14, fontWeight: '700'}}>
                    {selectedAgent.avg_sentiment_score}%
                  </Text>
                </View>
              </View>
            )}

            {/* Privacy Shield note */}
            <View
              style={{
                backgroundColor: tokens.surfaceSunken,
                borderRadius: 12,
                padding: 12,
                flexDirection: 'row',
                gap: 8,
                alignItems: 'flex-start',
                marginBottom: 20,
              }}
            >
              <Icon name="shield" size={14} color={tokens.textTertiary} style={{marginTop: 1}} />
              <Text style={{color: tokens.textTertiary, fontSize: 11, lineHeight: 16, flex: 1}}>
                This mini-profile is read-only. CRM text details are kept private to protect agent-client relationships.
              </Text>
            </View>

            {/* CTA Close / Action */}
            <View style={{flexDirection: 'row', gap: 10}}>
              <Pressable
                onPress={() => setSelectedAgent(null)}
                style={{
                  flex: 1,
                  backgroundColor: tokens.surfaceRaised,
                  borderWidth: 1,
                  borderColor: tokens.borderStrong,
                  borderRadius: 12,
                  paddingVertical: 12,
                  alignItems: 'center',
                }}
              >
                <Text style={{color: tokens.textPrimary, fontSize: 13, fontWeight: '700'}}>Close</Text>
              </Pressable>

              <Pressable
                onPress={() => {
                  setSelectedAgent(null);
                  navigation.navigate('Inbox');
                }}
                style={{
                  flex: 1.3,
                  backgroundColor: tokens.brandPrimary,
                  borderRadius: 12,
                  paddingVertical: 12,
                  alignItems: 'center',
                  flexDirection: 'row',
                  justifyContent: 'center',
                  gap: 6,
                }}
              >
                <Icon name="message-square" size={14} color="#FFFFFF" />
                <Text style={{color: '#FFFFFF', fontSize: 13, fontWeight: '700'}}>Message Agent</Text>
              </Pressable>
            </View>
          </Pressable>
        </Pressable>
      </Modal>
    </View>
  );
}
