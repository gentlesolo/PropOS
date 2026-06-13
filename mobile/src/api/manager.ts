import {apiClient} from './client';

export interface AgentRow {
  id: number;
  first_name: string;
  last_name: string;
  avatar_path?: string;
  active_today: boolean;
  calls_today: number;
  viewings_today: number;
  pipeline_value: number;
  sentiment_trend: 'up' | 'down' | 'flat';
  avg_sentiment_score: number; // 0–100
}

export interface AttentionItem {
  id: string;
  agent_id: number;
  agent_name: string;
  agent_avatar?: string;
  agent_initials: string;
  issue: string;
  urgency: 'danger' | 'amber' | 'emerald';
  action_type: 'message' | 'view';
}

export interface TeamSnapshot {
  total_pipeline_value: number;
  total_pipeline_delta_pct: number;
  calls_today: number;
  calls_delta_pct: number;
  avg_sentiment_score: number;
  sentiment_delta_pct: number;
  active_listings: number;
  listings_delta: number;
  agents_active_today: number;
  deals_at_risk: number;
  coaching_flags: number;
  attention_items: AttentionItem[];
  agents: AgentRow[];
}

// High fidelity mock data for manager dashboard
export const mockTeamSnapshotLarge: TeamSnapshot = {
  total_pipeline_value: 5840000,
  total_pipeline_delta_pct: 12.4,
  calls_today: 47,
  calls_delta_pct: 8.2,
  avg_sentiment_score: 78,
  sentiment_delta_pct: -2.1,
  active_listings: 24,
  listings_delta: 3,
  agents_active_today: 12,
  deals_at_risk: 3,
  coaching_flags: 2,
  attention_items: [
    {
      id: 'attn-1',
      agent_id: 101,
      agent_name: 'Sarah Jenkins',
      agent_initials: 'SJ',
      issue: "Hasn't logged a call in 3 days",
      urgency: 'danger',
      action_type: 'message',
    },
    {
      id: 'attn-2',
      agent_id: 102,
      agent_name: 'David Miller',
      agent_initials: 'DM',
      issue: "2 deals stuck in 'Offer Made' for 14+ days",
      urgency: 'amber',
      action_type: 'view',
    },
    {
      id: 'attn-3',
      agent_id: 103,
      agent_name: 'Marcus Brody',
      agent_initials: 'MB',
      issue: 'Sentiment dropped to Cold after last 2 calls with top client',
      urgency: 'danger',
      action_type: 'view',
    },
    {
      id: 'attn-4',
      agent_id: 104,
      agent_name: 'Elena Rostova',
      agent_initials: 'ER',
      issue: 'Coaching review request: Outbound call with buyer flagged by AI',
      urgency: 'emerald',
      action_type: 'view',
    }
  ],
  agents: [
    {
      id: 101,
      first_name: 'Sarah',
      last_name: 'Jenkins',
      active_today: true,
      calls_today: 12,
      viewings_today: 3,
      pipeline_value: 1250000,
      sentiment_trend: 'up',
      avg_sentiment_score: 85,
    },
    {
      id: 102,
      first_name: 'David',
      last_name: 'Miller',
      active_today: true,
      calls_today: 8,
      viewings_today: 1,
      pipeline_value: 980000,
      sentiment_trend: 'flat',
      avg_sentiment_score: 72,
    },
    {
      id: 103,
      first_name: 'Marcus',
      last_name: 'Brody',
      active_today: false,
      calls_today: 0,
      viewings_today: 0,
      pipeline_value: 1540000,
      sentiment_trend: 'down',
      avg_sentiment_score: 55,
    },
    {
      id: 104,
      first_name: 'Elena',
      last_name: 'Rostova',
      active_today: true,
      calls_today: 15,
      viewings_today: 4,
      pipeline_value: 1100000,
      sentiment_trend: 'up',
      avg_sentiment_score: 92,
    },
    {
      id: 105,
      first_name: 'Kenji',
      last_name: 'Sato',
      active_today: true,
      calls_today: 6,
      viewings_today: 2,
      pipeline_value: 450000,
      sentiment_trend: 'flat',
      avg_sentiment_score: 80,
    },
    {
      id: 106,
      first_name: 'Chloe',
      last_name: 'Dupont',
      active_today: false,
      calls_today: 0,
      viewings_today: 0,
      pipeline_value: 320000,
      sentiment_trend: 'flat',
      avg_sentiment_score: 75,
    },
    {
      id: 107,
      first_name: 'Alex',
      last_name: 'Wong',
      active_today: true,
      calls_today: 4,
      viewings_today: 1,
      pipeline_value: 200000,
      sentiment_trend: 'down',
      avg_sentiment_score: 64,
    }
  ],
};

export const mockTeamSnapshotSmall: TeamSnapshot = {
  total_pipeline_value: 2230000,
  total_pipeline_delta_pct: 4.8,
  calls_today: 14,
  calls_delta_pct: -2.3,
  avg_sentiment_score: 88,
  sentiment_delta_pct: 1.5,
  active_listings: 8,
  listings_delta: 0,
  agents_active_today: 2,
  deals_at_risk: 0,
  coaching_flags: 1,
  attention_items: [
    {
      id: 'attn-small-1',
      agent_id: 101,
      agent_name: 'Sarah Jenkins',
      agent_initials: 'SJ',
      issue: 'Coaching review request: Outbound call with buyer flagged by AI',
      urgency: 'emerald',
      action_type: 'view',
    }
  ],
  agents: [
    {
      id: 101,
      first_name: 'Sarah',
      last_name: 'Jenkins',
      active_today: true,
      calls_today: 9,
      viewings_today: 2,
      pipeline_value: 1250000,
      sentiment_trend: 'up',
      avg_sentiment_score: 91,
    },
    {
      id: 102,
      first_name: 'David',
      last_name: 'Miller',
      active_today: true,
      calls_today: 5,
      viewings_today: 1,
      pipeline_value: 980000,
      sentiment_trend: 'flat',
      avg_sentiment_score: 85,
    }
  ],
};

export interface BenchmarkMetric {
  label: string;
  value: string;
}

export interface BenchmarkAgent {
  id: number;
  first_name: string;
  last_name: string;
  avatar_path?: string;
  value: number;
  metrics_grid: BenchmarkMetric[];
}

export interface TeamBenchmarkResponse {
  metric: string;
  period: string;
  team_average: number;
  team_average_label: string;
  sparkline: number[];
  agents: BenchmarkAgent[];
  ai_insight: string;
  is_small_team: boolean;
}

export interface CallAnalyticsMetric {
  value: number;
  delta: number;
}

export interface CallAnalyticsResponse {
  headline_metrics: {
    total_calls: CallAnalyticsMetric;
    avg_duration: CallAnalyticsMetric;
    answer_rate: CallAnalyticsMetric;
    avg_sentiment: { value: number; delta: number; rating: 'hot' | 'warm' | 'cold' | 'neutral' };
  };
  chart_data: {
    label: string;
    total: number;
    answered: number;
    missed: number;
    avg_duration_sec: number;
  }[];
  sentiment_breakdown: {
    hot: number;
    warm: number;
    cold: number;
    neutral: number;
    delta_hot: number;
  };
  conversion: {
    calls_made: number;
    tasks_created: number;
    viewings_booked: number;
    rate_text: string;
  };
  top_performers: {
    id: number;
    first_name: string;
    last_name: string;
    avatar_path?: string;
    sentiment_score: number;
    trend: 'up' | 'down' | 'flat';
  }[];
  ai_insight: string;
}

export const managerApi = {
  snapshot: async (smallTeamMode = false): Promise<{data: TeamSnapshot}> => {
    try {
      // Try to fetch from real API first
      const res = await apiClient.get<TeamSnapshot>('/manager/snapshot');
      return res;
    } catch (e) {
      // Fallback with high fidelity mock data
      await new Promise(resolve => setTimeout(resolve, 800)); // Simulate realistic load time
      return {
        data: smallTeamMode ? mockTeamSnapshotSmall : mockTeamSnapshotLarge,
      };
    }
  },

  benchmark: async (
    period: string,
    metric: string,
    smallTeamMode = false
  ): Promise<{data: TeamBenchmarkResponse}> => {
    // Simulate API delay
    await new Promise((resolve) => setTimeout(resolve, 600));

    // Dynamic calculations based on metric and period
    let team_average = 0;
    let unit = '';
    let delta = '↑ 8% vs last period';
    let sparkline = [10, 15, 13, 17, 22, 19, 23];
    let ai_insight = '';

    // Adjust parameters for different metrics
    if (metric === 'Calls') {
      team_average = 23;
      unit = 'calls';
      delta = '↑ 8% vs last period';
      sparkline = [18, 20, 19, 24, 21, 25, 23];
      ai_insight = 'Agents with 15+ calls/week average 22% higher pipeline value this quarter. 3 agents are below this call threshold — consider reviewing their call schedules.';
    } else if (metric === 'Pipeline Value') {
      team_average = 1100000;
      unit = 'pipeline';
      delta = '↑ 14% vs last period';
      sparkline = [900000, 950000, 1050000, 1020000, 1120000, 1080000, 1100000];
      ai_insight = 'Total pipeline value is growing, driven by a 15% increase in high-intent buyer engagements. Focus training on moving deals from Offer to Signed.';
    } else if (metric === 'Sentiment') {
      team_average = 78;
      unit = 'sentiment';
      delta = '↓ 2% vs last period';
      sparkline = [82, 80, 81, 79, 77, 79, 78];
      ai_insight = 'Average client sentiment has dipped slightly this period. Stalled response times on viewings are the primary driver; aim to reply within 4 hours.';
    } else if (metric === 'Tasks Completed') {
      team_average = 16;
      unit = 'tasks';
      delta = '↑ 11% vs last period';
      sparkline = [12, 14, 13, 15, 17, 16, 16];
      ai_insight = 'Task completion velocity remains steady. Agents completing pre-viewing checklists on-time show a 30% reduction in deal postponement.';
    } else if (metric === 'Viewings') {
      team_average = 6;
      unit = 'viewings';
      delta = '↑ 5% vs last period';
      sparkline = [4, 5, 5, 6, 7, 5, 6];
      ai_insight = 'Viewings are at a high point. Higher viewing frequencies are correlating directly with quicker offer submissions this month.';
    }

    // Adjust multipliers based on period
    let multiplier = 1;
    if (period === 'This Month') {
      multiplier = 4.2;
      delta = '↑ 12% vs last month';
    } else if (period === 'This Quarter') {
      multiplier = 12.5;
      delta = '↑ 18% vs last quarter';
    } else if (period === 'Custom') {
      multiplier = 2.0;
      delta = '↓ 1% vs selected custom range';
    }

    team_average = Math.round(team_average * multiplier);

    // List of agents
    const allAgents = [
      { id: 101, first_name: 'Sarah', last_name: 'Jenkins', baseVal: 32 },
      { id: 104, first_name: 'Elena', last_name: 'Rostova', baseVal: 28 },
      { id: 102, first_name: 'David', last_name: 'Miller', baseVal: 24 },
      { id: 103, first_name: 'Marcus', last_name: 'Brody', baseVal: 21 },
      { id: 105, first_name: 'Kenji', last_name: 'Sato', baseVal: 19 },
      { id: 106, first_name: 'Chloe', last_name: 'Dupont', baseVal: 15 },
      { id: 107, first_name: 'Alex', last_name: 'Wong', baseVal: 12 },
    ];

    const activeAgents = smallTeamMode ? allAgents.slice(0, 2) : allAgents;

    // Map base value to specific metric and multiply by period multiplier
    const agents = activeAgents.map((a) => {
      let personalValue = 0;
      if (metric === 'Calls') {
        personalValue = Math.round(a.baseVal * multiplier);
      } else if (metric === 'Pipeline Value') {
        personalValue = Math.round((a.baseVal * 45000) * multiplier);
      } else if (metric === 'Sentiment') {
        // Sentiment doesn't multiply by period days, it averages
        personalValue = Math.min(100, Math.round(50 + (a.baseVal * 1.4)));
      } else if (metric === 'Tasks Completed') {
        personalValue = Math.round((a.baseVal * 0.7) * multiplier);
      } else if (metric === 'Viewings') {
        personalValue = Math.round((a.baseVal * 0.25) * multiplier);
      }

      // Generate 2x2 grid values for OTHER metrics
      const grid = [
        { label: 'Pipeline', value: `$${((a.baseVal * 45000 * multiplier) / 1000000).toFixed(2)}M` },
        { label: 'Sentiment', value: `${Math.min(100, Math.round(50 + (a.baseVal * 1.4)))}%` },
        { label: 'Calls', value: String(Math.round(a.baseVal * multiplier)) },
        { label: 'Viewings', value: String(Math.round(a.baseVal * 0.25 * multiplier)) },
      ].filter((item) => {
        // Exclude the current selected metric from the 2x2 grid to make it cleaner
        if (metric === 'Calls' && item.label === 'Calls') return false;
        if (metric === 'Pipeline Value' && item.label === 'Pipeline') return false;
        if (metric === 'Sentiment' && item.label === 'Sentiment') return false;
        if (metric === 'Viewings' && item.label === 'Viewings') return false;
        return true;
      }).slice(0, 4);

      // Pad if it filtered out one item
      while (grid.length < 4) {
        grid.push({ label: 'Tasks', value: String(Math.round(a.baseVal * 0.7 * multiplier)) });
      }

      return {
        id: a.id,
        first_name: a.first_name,
        last_name: a.last_name,
        value: personalValue,
        metrics_grid: grid,
      };
    });

    // Re-sort agents descending by value
    agents.sort((a, b) => b.value - a.value);

    // Calculate real team average from current agent set
    const totalVal = agents.reduce((sum, ag) => sum + ag.value, 0);
    const calculatedAvg = Math.round(totalVal / agents.length);

    // Sparkline points scale
    const scaledSparkline = sparkline.map(v => Math.round(v * multiplier));

    return {
      data: {
        metric,
        period,
        team_average: calculatedAvg,
        team_average_label: `Team avg: ${
          metric === 'Pipeline Value'
            ? `$${(calculatedAvg / 1000000).toFixed(2)}M`
            : calculatedAvg
        } ${unit}/${
          period === 'This Week' ? 'week' : period === 'This Month' ? 'month' : 'quarter'
        } · ${delta}`,
        sparkline: scaledSparkline,
        agents,
        ai_insight: ai_insight,
        is_small_team: activeAgents.length < 3,
      },
    };
  },

  callAnalytics: async (
    period: string,
    agentId: string | null,
    direction: string
  ): Promise<{data: CallAnalyticsResponse}> => {
    // Simulate delay
    await new Promise((resolve) => setTimeout(resolve, 600));

    // Dynamic Empty State Simulation: Inbound + Today has no calls
    if (direction === 'Inbound' && period === 'Today') {
      return {
        data: {
          headline_metrics: {
            total_calls: { value: 0, delta: 0 },
            avg_duration: { value: 0, delta: 0 },
            answer_rate: { value: 0, delta: 0 },
            avg_sentiment: { value: 0, delta: 0, rating: 'neutral' },
          },
          chart_data: [],
          sentiment_breakdown: { hot: 0, warm: 0, cold: 0, neutral: 0, delta_hot: 0 },
          conversion: { calls_made: 0, tasks_created: 0, viewings_booked: 0, rate_text: '0% of calls led to viewings' },
          top_performers: [],
          ai_insight: 'No calls registered for this period. Try expanding your filters or choosing a wider date range.',
        },
      };
    }

    // Dynamic period multipliers
    let mult = 1;
    let periodText = 'week';
    if (period === 'Today') {
      mult = 0.2;
      periodText = 'day';
    } else if (period === 'This Month') {
      mult = 4.2;
      periodText = 'month';
    } else if (period === 'Custom') {
      mult = 2.1;
      periodText = 'range';
    }

    // Direction modifiers
    let dirMult = 1.0;
    if (direction === 'Inbound') dirMult = 0.4;
    else if (direction === 'Outbound') dirMult = 0.6;

    // Agent specific modifier
    let agentMult = 1.0;
    if (agentId && agentId !== 'All') agentMult = 0.25;

    const finalMult = mult * dirMult * agentMult;

    const baseCalls = Math.round(180 * finalMult);
    const baseDuration = Math.round(145); // average call length in seconds
    const baseAnswerRate = Math.round(direction === 'Inbound' ? 95 : 68);

    // Dynamic chart bars depending on period
    let chart_data: any[] = [];
    if (period === 'Today') {
      // Hourly view
      const hours = ['9am', '12pm', '3pm', '6pm'];
      chart_data = hours.map((h, i) => {
        const total = Math.max(1, Math.round((5 + i * 2) * dirMult * agentMult));
        const answered = Math.max(1, Math.round(total * (baseAnswerRate / 100)));
        return {
          label: h,
          total,
          answered,
          missed: total - answered,
          avg_duration_sec: Math.round(baseDuration + (i * 10)),
        };
      });
    } else {
      // Daily view
      const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      chart_data = days.map((d, i) => {
        const total = Math.max(1, Math.round((12 + (i % 3) * 5) * dirMult * agentMult));
        const answered = Math.max(1, Math.round(total * (baseAnswerRate / 100)));
        return {
          label: d,
          total,
          answered,
          missed: total - answered,
          avg_duration_sec: Math.round(baseDuration - 20 + ((i % 4) * 15)),
        };
      });
    }

    // Headline metrics
    const totalCallsMetric = { value: baseCalls, delta: 12 };
    const avgDurationMetric = { value: baseDuration, delta: -4 };
    const answerRateMetric = { value: baseAnswerRate, delta: 2 };
    const avgSentimentMetric = { value: 76, delta: 5, rating: 'warm' as const };

    // Sentiment breakdown ratios
    const hotPct = Math.round(15 * dirMult);
    const warmPct = Math.round(45 * (direction === 'Inbound' ? 1.2 : 0.9));
    const coldPct = Math.max(5, 100 - (hotPct + warmPct + 20));
    const neutralPct = 100 - (hotPct + warmPct + coldPct);

    // Call Outcomes → Conversion Funnel
    const callsMadeCount = baseCalls;
    const tasksCreatedCount = Math.round(baseCalls * 0.45);
    const viewingsBookedCount = Math.round(baseCalls * 0.18);
    const conversionRate = Math.round((viewingsBookedCount / Math.max(1, callsMadeCount)) * 100);

    // Top performers
    const performers = [
      { id: 104, first_name: 'Elena', last_name: 'Rostova', sentiment_score: 92, trend: 'up' as const },
      { id: 101, first_name: 'Sarah', last_name: 'Jenkins', sentiment_score: 85, trend: 'up' as const },
      { id: 105, first_name: 'Kenji', last_name: 'Sato', sentiment_score: 80, trend: 'flat' as const },
      { id: 106, first_name: 'Chloe', last_name: 'Dupont', sentiment_score: 75, trend: 'down' as const },
    ];

    const ai_insight = 'Calls made between 10am-12pm have 31% higher answer rates than calls made after 4pm. Consider shifting follow-up call blocks to mornings.';

    return {
      data: {
        headline_metrics: {
          total_calls: totalCallsMetric,
          avg_duration: avgDurationMetric,
          answer_rate: answerRateMetric,
          avg_sentiment: avgSentimentMetric,
        },
        chart_data,
        sentiment_breakdown: {
          hot: hotPct,
          warm: warmPct,
          cold: coldPct,
          neutral: neutralPct,
          delta_hot: 5,
        },
        conversion: {
          calls_made: callsMadeCount,
          tasks_created: tasksCreatedCount,
          viewings_booked: viewingsBookedCount,
          rate_text: `Conversion: ${conversionRate}% of calls led to a booked viewing this ${periodText}`,
        },
        top_performers: performers,
        ai_insight,
      },
    };
  },
};


