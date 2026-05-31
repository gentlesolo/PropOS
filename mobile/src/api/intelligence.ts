import {apiClient} from './client';

export interface InCallHints {
  objection_detected: string | null;
  suggested_response: string | null;
  talking_points: string[];
  urgency_signal: boolean;
  warning: string | null;
}

export interface CoachScore {
  score: number | null;
  tone_score: number | null;
  clarity_score: number | null;
  persuasion_score: number | null;
  issues: string[];
  rewrite: string | null;
  rewrite_reason: string | null;
}

export interface ReplySuggestion {
  suggestion: string | null;
  tone: string | null;
}

export interface PersonalAnalytics {
  period_days: number;
  total_calls: number;
  total_duration_sec: number;
  avg_duration_sec: number;
  inbound: number;
  outbound: number;
  sentiment: Record<string, number>;
  avg_sentiment_score: number;
  daily_volume: Array<{date: string; count: number}>;
}

export interface TeamAnalytics {
  period_days: number;
  team_totals: {calls: number; total_duration: number};
  agent_stats: Array<{
    agent: {id: number; first_name: string; last_name: string; avatar_path?: string};
    call_count: number;
    total_duration: number;
    avg_duration: number;
  }>;
  flagged_calls: Array<{
    id: number;
    agent: {id: number; first_name: string; last_name: string};
    contact?: {id: number; first_name: string; last_name: string};
    direction: string;
    duration_seconds?: number;
    started_at?: string;
    coaching_notes?: string;
    summary?: {sentiment: string; summary_text: string};
  }>;
}

export interface SentimentPoint {
  call_id: number;
  date: string;
  sentiment: 'hot' | 'warm' | 'cold' | 'neutral';
  sentiment_score: number;
  duration_sec?: number;
  direction: string;
}

export const intelligenceApi = {
  // In-call
  getChannel: (callId: number) =>
    apiClient.get<{channel: string; stream_active: boolean}>(`/calls/${callId}/channel`),

  getHints: (callId: number, transcriptSoFar: string) =>
    apiClient.post<InCallHints>(`/calls/${callId}/hints`, {
      transcript_so_far: transcriptSoFar,
    }),

  flagCall: (callId: number, coachingNotes?: string) =>
    apiClient.post(`/calls/${callId}/flag`, {coaching_notes: coachingNotes}),

  startStream: (callId: number) =>
    apiClient.post<{channel: string; stream_active: boolean}>(`/calls/${callId}/stream`),

  // Reply coach
  scoreMessage: (draft: string, channel: string, contactId?: number, context?: string) =>
    apiClient.post<CoachScore>('/coach/score', {draft, channel, contact_id: contactId, context}),

  suggestReply: (lastMessage: string, channel: string, contactId?: number) =>
    apiClient.post<ReplySuggestion>('/coach/suggest', {
      last_message: lastMessage,
      channel,
      contact_id: contactId,
    }),

  // Analytics
  personal: (days = 30) =>
    apiClient.get<PersonalAnalytics>('/analytics/personal', {params: {days}}),

  contactSentiment: (contactId: number) =>
    apiClient.get<SentimentPoint[]>(`/analytics/contact/${contactId}/sentiment`),

  team: (days = 7) =>
    apiClient.get<TeamAnalytics>('/analytics/team', {params: {days}}),

  agentCalls: (agentId: number) =>
    apiClient.get(`/analytics/agents/${agentId}/calls`),

  unflagCall: (callId: number) =>
    apiClient.post(`/analytics/calls/${callId}/unflag`),
};
