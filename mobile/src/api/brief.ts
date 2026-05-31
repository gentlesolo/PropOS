import {apiClient} from './client';

export interface DailyBrief {
  date: string;
  content: string;
  priority_actions: Array<{title: string; type?: string; id?: number}>;
  deal_alerts: Array<{message: string; deal_id?: number}>;
  viewing_schedule: Array<{time: string; contact_name: string; address: string}>;
  market_snapshot?: string;
}

export interface TimelineActivity {
  id: number;
  type: 'note' | 'call' | 'email' | 'meeting' | 'sms' | 'viewing' | 'status_change' | 'system';
  subject?: string;
  body?: string;
  metadata?: Record<string, unknown>;
  occurred_at: string;
  user?: {id: number; first_name: string; last_name: string};
}

export const briefApi = {
  get: () => apiClient.get<DailyBrief>('/brief'),

  timeline: (contactId: number, page = 1) =>
    apiClient.get<{data: TimelineActivity[]}>(`/contacts/${contactId}/timeline`, {
      params: {page},
    }),
};
