export interface User {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  phone?: string;
  job_title?: string;
  agency_id: number;
  avatar_path?: string;
}

export interface Contact {
  id: number;
  first_name: string;
  last_name: string;
  phone?: string;
  email?: string;
  status: 'new' | 'active' | 'qualified' | 'nurturing' | 'closed' | 'archived';
  avatar_path?: string;
  last_contacted_at?: string;
  assigned_agent_id?: number;
  assigned_agent?: User;
  deals?: Deal[];
  type?: 'buyer' | 'seller' | 'landlord' | 'tenant' | 'investor' | 'referral_partner';
  intent_score?: number;
  latestCall?: Call;
  preferences?: {
    min_budget?: number;
    max_budget?: number;
    min_bedrooms?: number;
    areas?: string[];
    property_types?: string[];
    must_have_features?: string[];
    timeline?: string;
  };
}

export type CallDirection = 'inbound' | 'outbound';
export type CallStatus =
  | 'initiated' | 'ringing' | 'in-progress' | 'completed'
  | 'no-answer' | 'busy' | 'failed' | 'canceled';
export type Sentiment = 'hot' | 'warm' | 'cold' | 'neutral';

export interface CallSummary {
  id: number;
  call_id: number;
  summary_text: string;
  key_points: string[];
  sentiment: Sentiment;
  sentiment_score: number;
  action_items: string[];
  suggested_next_step?: string;
  agent_confirmed_at?: string;
  agent_edited: boolean;
}

export interface CallTranscript {
  id: number;
  call_id: number;
  full_text: string;
  speaker_segments: SpeakerSegment[];
  word_count: number;
}

export interface SpeakerSegment {
  speaker: string;
  text: string;
  start: number;
  end: number;
}

export interface Call {
  id: number;
  agent_id: number;
  contact_id?: number;
  contact?: Contact;
  direction: CallDirection;
  status: CallStatus;
  provider_call_sid?: string;
  twilio_number?: string;
  remote_number?: string;
  duration_seconds?: number;
  duration_formatted?: string;
  recording_url?: string;
  started_at?: string;
  ended_at?: string;
  summary?: CallSummary;
  transcript?: CallTranscript;
}

export interface Task {
  id: number;
  title: string;
  status: 'pending' | 'in_progress' | 'completed' | 'snoozed';
  due_at?: string;
  contact_id?: number;
  contact?: Contact;
  source?: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
}

export interface PipelineStage {
  id: number;
  name: string;
  color?: string;
}

export interface Deal {
  id: number;
  name: string;
  value?: number;
  status: 'open' | 'won' | 'lost';
  pipeline_stage_id: number;
  stage?: PipelineStage;
  momentum_score?: number;
  momentum_label?: string;
}
