import {apiClient} from './client';

export interface InboxThread {
  contact_id: number;
  contact: {
    id: number;
    first_name: string;
    last_name: string;
    avatar_path?: string;
  };
  last_message: {
    id: number;
    body: string;
    channel: 'whatsapp' | 'sms' | 'email';
    direction: 'inbound' | 'outbound';
    status: string;
    sent_at: string;
  };
}

export interface Message {
  id: number;
  body: string;
  channel: 'whatsapp' | 'sms' | 'email';
  direction: 'inbound' | 'outbound';
  status: string;
  created_at: string;
}

export const messagingApi = {
  inbox: (search?: string) =>
    apiClient.get<InboxThread[]>('/messages', {params: {search}}),

  thread: (contactId: number) =>
    apiClient.get<{contact: object; messages: Message[]}>(`/messages/${contactId}`),

  send: (contactId: number, body: string, channel: 'whatsapp' | 'sms' | 'email') =>
    apiClient.post(`/messages/${contactId}`, {body, channel}),
};
