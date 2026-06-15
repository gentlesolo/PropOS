import {apiClient} from './client';
import {Call, CallSummary, PaginatedResponse} from '../types';

export interface LiveKitTokenResponse {
  server_url: string;
  identity: string;
  agent_number: string | null;
  number_type: string | null;
  verified: boolean;
}

export interface StoreCallResponse {
  call_id: number;
  room_name: string;
  token: string;
  server_url: string;
}

export const callsApi = {
  getToken: () =>
    apiClient.post<LiveKitTokenResponse>('/calls/token'),

  store: (payload: {
    contact_id?: number;
    remote_number: string;
  }) => apiClient.post<StoreCallResponse>('/calls', payload),

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
