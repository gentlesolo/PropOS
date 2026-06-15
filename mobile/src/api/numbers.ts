import {apiClient} from './client';
import {AgentNumber} from '../types';

export interface RegisterNumberPayload {
  type: 'verified_caller_id';
  country_code: string;
  number: string;
}

export interface RegisterNumberResponse {
  id: number;
  display_number: string;
  number_type: 'verified_caller_id';
  country_code: string;
  verified: boolean;
  active: boolean;
  sms_sent: boolean;
  message: string;
}

export const numbersApi = {
  list: () =>
    apiClient.get<AgentNumber[]>('/numbers'),

  register: (payload: RegisterNumberPayload) =>
    apiClient.post<RegisterNumberResponse>('/numbers', payload),

  confirm: (id: number, code: string) =>
    apiClient.post<AgentNumber>(`/numbers/${id}/confirm`, {code}),

  resendOtp: (id: number) =>
    apiClient.post<{message: string; sms_sent: boolean}>(`/numbers/${id}/resend`, {}),

  activate: (id: number) =>
    apiClient.patch<AgentNumber>(`/numbers/${id}/activate`, {}),

  remove: (id: number) =>
    apiClient.delete<void>(`/numbers/${id}`),
};
