import {apiClient} from './client';

export interface BenchmarkData {
  period_days: number;
  personal: {
    total_calls: number;
    avg_duration_sec: number;
    avg_sentiment_score: number;
  };
  team_avg: {
    calls_per_period: number;
    avg_duration_sec: number;
    avg_sentiment_score: number;
    agent_count: number;
  } | null;
  percentiles: {
    calls: number;
    duration: number;
    sentiment: number;
  } | null;
  rankings: {
    my_rank: number | null;
    out_of: number;
  };
  message?: string;
}

export interface LeaderboardEntry {
  agent: {id: number; first_name: string; last_name: string; avatar_path?: string};
  call_count: number;
  total_duration: number;
  avg_duration: number;
}

export const benchmarkApi = {
  compare: (days = 30) =>
    apiClient.get<BenchmarkData>('/benchmark', {params: {days}}),

  leaderboard: (metric: 'calls' | 'duration' | 'volume' = 'calls', days = 30) =>
    apiClient.get<{metric: string; period_days: number; leaderboard: LeaderboardEntry[]}>(
      '/benchmark/leaderboard',
      {params: {metric, days}},
    ),

  setLanguage: (language: string) =>
    apiClient.patch('/numbers/language', {language}),
};
