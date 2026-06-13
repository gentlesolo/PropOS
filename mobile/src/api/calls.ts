import {apiClient} from './client';
import {Call, CallSummary, PaginatedResponse} from '../types';

export const callsApi = {
  getToken: () =>
    apiClient.post<{token: string; identity: string; agent_number: string | null}>(
      '/calls/token',
    ),

  store: (payload: {
    contact_id?: number;
    remote_number: string;
    provider_call_sid?: string;
  }) => apiClient.post<Call>('/calls', payload),

  list: (params?: {direction?: string; sentiment?: string; page?: number}) =>
    apiClient.get<PaginatedResponse<Call>>('/calls', {params}),

  get: (id: number) =>
    apiClient.get<Call>(`/calls/${id}`),

  updateStatus: (id: number, status: string, duration_seconds?: number) =>
    apiClient.patch<Call>(`/calls/${id}/status`, {status, duration_seconds}),

  confirmSummary: (
    id: number,
    payload: {
      summary_text: string;
      action_items?: string[];
      suggested_next_step?: string;
    },
  ) => apiClient.patch<CallSummary>(`/calls/${id}/summary`, payload),
};
