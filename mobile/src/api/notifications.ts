import {apiClient} from './client';

export interface AppNotification {
  id: string;
  type: string;
  title: string;
  body: string;
  action_url: string | null;
  severity: string;
  read_at: string | null;
  created_at: string;
}

export const notificationsApi = {
  list: (page = 1) =>
    apiClient.get<{data: AppNotification[]; total: number}>('/notifications', {params: {page}}),

  unreadCount: () =>
    apiClient.get<{count: number}>('/notifications/unread-count'),

  markRead: (id: string) =>
    apiClient.patch(`/notifications/${id}/read`),

  markAllRead: () =>
    apiClient.patch('/notifications/read-all'),
};
