import {apiClient} from './client';
import {User} from '../types';

interface LoginPayload {
  email: string;
  password: string;
  device_name: string;
  platform: 'ios' | 'android';
}

interface LoginResponse {
  token: string;
  user: User;
}

export const authApi = {
  login: (payload: LoginPayload) =>
    apiClient.post<LoginResponse>('/auth/login', payload),

  logout: () => apiClient.post('/auth/logout'),

  me: () => apiClient.get<User>('/auth/me'),

  registerDevice: (payload: {
    platform: 'ios' | 'android';
    push_token: string;
    push_type: 'fcm' | 'apns' | 'voip';
    device_name?: string;
  }) => apiClient.post('/auth/device', payload),
};
