import {apiClient} from './client';
import {Task} from '../types';

export const tasksApi = {
  list: () => apiClient.get<Task[]>('/tasks'),

  store: (payload: {
    title: string;
    contact_id?: number;
    due_at?: string;
    call_id?: number;
  }) => apiClient.post<Task>('/tasks', payload),

  update: (id: number, payload: {status?: string; due_at?: string; title?: string}) =>
    apiClient.patch<Task>(`/tasks/${id}`, payload),

  delete: (id: number) => apiClient.delete<{message: string}>(`/tasks/${id}`),
};
