import {apiClient} from './client';
import {AgentNumber} from '../types';

export interface RegisterNumberPayload {
  type: 'twilio_provisioned' | 'verified_caller_id';
  country_code: string;
  number?: string;
  area_code?: string;
}

export interface RegisterNumberResponse extends AgentNumber {
  validation_code?: string;
  message?: string;
}

export interface VerificationStatusResponse {
  verified: boolean;
  number: AgentNumber;
}

export const numbersApi = {
  list: () =>
    apiClient.get<AgentNumber[]>('/numbers'),

  register: (payload: RegisterNumberPayload) =>
    apiClient.post<RegisterNumberResponse>('/numbers', payload),

  checkVerification: (id: number) =>
    apiClient.get<VerificationStatusResponse>(`/numbers/${id}/verification`),

  activate: (id: number) =>
    apiClient.patch<AgentNumber>(`/numbers/${id}/activate`, {}),

  remove: (id: number) =>
    apiClient.delete<void>(`/numbers/${id}`),
};
