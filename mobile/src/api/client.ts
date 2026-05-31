import axios from 'axios';
import {MMKV} from 'react-native-mmkv';

export const storage = new MMKV();

const BASE_URL = __DEV__
  ? 'http://10.0.2.2:8000/api/mobile'  // Android emulator → host machine
  : 'https://your-propos-domain.com/api/mobile';

export const apiClient = axios.create({
  baseURL: BASE_URL,
  timeout: 15000,
  headers: {'Content-Type': 'application/json', Accept: 'application/json'},
});

apiClient.interceptors.request.use(config => {
  const token = storage.getString('auth_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

apiClient.interceptors.response.use(
  res => res,
  async error => {
    if (error.response?.status === 401) {
      storage.delete('auth_token');
      storage.delete('auth_user');
    }
    return Promise.reject(error);
  },
);
