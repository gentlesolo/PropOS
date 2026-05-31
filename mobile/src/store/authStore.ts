import {create} from 'zustand';
import {storage} from '../api/client';
import {User} from '../types';

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  setAuth: (token: string, user: User) => void;
  clearAuth: () => void;
  hydrate: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  user: null,
  isAuthenticated: false,

  setAuth: (token, user) => {
    storage.set('auth_token', token);
    storage.set('auth_user', JSON.stringify(user));
    set({token, user, isAuthenticated: true});
  },

  clearAuth: () => {
    storage.delete('auth_token');
    storage.delete('auth_user');
    set({token: null, user: null, isAuthenticated: false});
  },

  hydrate: () => {
    const token = storage.getString('auth_token');
    const userJson = storage.getString('auth_user');
    if (token && userJson) {
      try {
        const user: User = JSON.parse(userJson);
        set({token, user, isAuthenticated: true});
      } catch {
        storage.delete('auth_token');
        storage.delete('auth_user');
      }
    }
  },
}));
