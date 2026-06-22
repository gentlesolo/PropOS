import {apiClient} from './client';

export type QuitNoticeStatus =
  | 'drafted'
  | 'sent'
  | 'acknowledged'
  | 'disputed'
  | 'withdrawn'
  | 'completed';

export interface QuitNotice {
  id: number;
  reference: string;
  status: QuitNoticeStatus;
  reason: string;
  vacate_by_date: string;
  issue_date: string;
  delivery_method: string;
  sent_at: string | null;
  notice_period_days: number;
  issued_by_name: string | null;
  notice_body?: string;
  internal_notes?: string | null;
  lease_ref?: string;
}

export interface CreateQuitNoticePayload {
  lease_id: number;
  vacate_by_date: string;
  reason: string;
  notice_body: string;
  delivery_method: 'email' | 'hand_delivered' | 'registered_post' | 'email_and_post';
  internal_notes?: string;
}

const mockNotices: QuitNotice[] = [
  {
    id: 1,
    reference: 'QN-ABCD1234',
    status: 'sent',
    reason: 'Non-payment of rent for 3 consecutive months',
    vacate_by_date: '2026-08-01',
    issue_date: '2026-06-15',
    delivery_method: 'email',
    sent_at: '2026-06-15T10:00:00Z',
    notice_period_days: 40,
    issued_by_name: 'Sarah Jenkins',
    notice_body:
      'Dear Tenant,\n\nThis letter serves as formal notice...',
    lease_ref: 'LSE-MH001',
  },
];

export const quitNoticesApi = {
  forTenant: async (tenantId: number): Promise<QuitNotice[]> => {
    try {
      const res = await apiClient.get<{data: QuitNotice[]}>(`/tenants/${tenantId}/quit-notices`);
      return res.data.data;
    } catch {
      await new Promise(r => setTimeout(r, 300));
      return tenantId === 1 ? mockNotices : [];
    }
  },

  show: async (noticeId: number): Promise<QuitNotice> => {
    try {
      const res = await apiClient.get<{data: QuitNotice}>(`/quit-notices/${noticeId}`);
      return res.data.data;
    } catch {
      await new Promise(r => setTimeout(r, 300));
      return {...mockNotices[0], id: noticeId};
    }
  },

  generateContent: async (payload: {
    lease_id: number;
    reason: string;
    vacate_by_date: string;
  }): Promise<string> => {
    const res = await apiClient.post<{notice_body: string}>(
      '/quit-notices/generate-content',
      payload,
    );
    return res.data.notice_body;
  },

  create: async (payload: CreateQuitNoticePayload): Promise<QuitNotice> => {
    const res = await apiClient.post<{data: QuitNotice}>('/quit-notices', payload);
    return res.data.data;
  },

  send: async (noticeId: number): Promise<QuitNotice> => {
    const res = await apiClient.post<{data: QuitNotice}>(`/quit-notices/${noticeId}/send`, {});
    return res.data.data;
  },

  withdraw: async (noticeId: number): Promise<QuitNotice> => {
    const res = await apiClient.post<{data: QuitNotice}>(`/quit-notices/${noticeId}/withdraw`, {});
    return res.data.data;
  },
};

export const STATUS_LABELS: Record<QuitNoticeStatus, string> = {
  drafted:      'Draft',
  sent:         'Sent',
  acknowledged: 'Acknowledged',
  disputed:     'Disputed',
  withdrawn:    'Withdrawn',
  completed:    'Completed',
};

export const STATUS_COLORS: Record<QuitNoticeStatus, {bg: string; text: string; border: string}> = {
  drafted:      {bg: '#F3F4F6', text: '#6B7280', border: '#D1D5DB'},
  sent:         {bg: '#EFF6FF', text: '#2563EB', border: '#BFDBFE'},
  acknowledged: {bg: '#FFFBEB', text: '#D97706', border: '#FCD34D'},
  disputed:     {bg: '#FEF2F2', text: '#DC2626', border: '#FECACA'},
  withdrawn:    {bg: '#F3F4F6', text: '#9CA3AF', border: '#E5E7EB'},
  completed:    {bg: '#F0FDF4', text: '#16A34A', border: '#BBF7D0'},
};
