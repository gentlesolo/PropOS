import {apiClient} from './client';
import {Call, Contact, PaginatedResponse} from '../types';

export const contactsApi = {
  list: (params?: {search?: string; mine?: boolean; page?: number}) =>
    apiClient.get<PaginatedResponse<Contact>>('/contacts', {params}),

  get: (id: number) =>
    apiClient.get<{contact: Contact; recent_calls: Call[]}>(`/contacts/${id}`),

  create: (contact: Partial<Contact>) =>
    apiClient.post<Contact>('/contacts', contact),

  addNote: (id: number, note: string) =>
    apiClient.post(`/contacts/${id}/notes`, {note}),

  calls: (id: number, page = 1) =>
    apiClient.get<PaginatedResponse<Call>>(`/contacts/${id}/calls`, {
      params: {page},
    }),
};
