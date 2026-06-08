import axios from 'axios';
import {createMMKV} from 'react-native-mmkv';

export const storage = createMMKV();

const BASE_URL = __DEV__
  ? 'http://192.168.1.169:8000/api/mobile'  // Android device → host machine over Wi-Fi
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
      storage.remove('auth_token');
      storage.remove('auth_user');
    }
    return Promise.reject(error);
  },
);
