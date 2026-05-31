import {apiClient} from './client';

export interface Viewing {
  id: number;
  contact_id: number;
  listing_id?: number;
  scheduled_at: string;
  status: 'scheduled' | 'confirmed' | 'completed' | 'no_show' | 'cancelled';
  duration_minutes?: number;
  notes?: string;
  check_in_at?: string;
  outcome?: 'interested' | 'not_interested' | 'offer_expected' | 'undecided';
  outcome_notes?: string;
  contact?: {
    id: number;
    first_name: string;
    last_name: string;
    phone?: string;
    avatar_path?: string;
  };
  listing?: {
    id: number;
    title: string;
    address: string;
    price?: number;
    bedrooms?: number;
    bathrooms?: number;
  };
}

export const viewingsApi = {
  today: () => apiClient.get<Viewing[]>('/viewings'),

  upcoming: () => apiClient.get<Viewing[]>('/viewings/upcoming'),

  get: (id: number) => apiClient.get<Viewing>(`/viewings/${id}`),

  checkIn: (id: number) => apiClient.post<Viewing>(`/viewings/${id}/check-in`),

  complete: (id: number, outcome: string, outcome_notes?: string) =>
    apiClient.post<Viewing>(`/viewings/${id}/complete`, {outcome, outcome_notes}),

  updateStatus: (id: number, status: string) =>
    apiClient.patch<Viewing>(`/viewings/${id}/status`, {status}),
};
