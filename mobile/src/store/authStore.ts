import {create} from 'zustand';
import {storage} from '../api/client';
import {User} from '../types';

interface AuthState {
  token: string | null;
  user: User | null;
  isAuthenticated: boolean;
  hasSeenOnboarding: boolean;
  isLocked: boolean;
  setAuth: (token: string, user: User) => void;
  clearAuth: () => void;
  setHasSeenOnboarding: (val: boolean) => void;
  setLocked: (val: boolean) => void;
  hydrate: () => void;
}

export const useAuthStore = create<AuthState>((set) => ({
  token: null,
  user: null,
  isAuthenticated: false,
  hasSeenOnboarding: false,
  isLocked: false,

  setAuth: (token, user) => {
    storage.set('auth_token', token);
    storage.set('auth_user', JSON.stringify(user));
    set({token, user, isAuthenticated: true});
  },

  clearAuth: () => {
    storage.remove('auth_token');
    storage.remove('auth_user');
    set({token: null, user: null, isAuthenticated: false, isLocked: false});
  },

  setHasSeenOnboarding: (val) => {
    storage.set('has_seen_onboarding', val);
    set({hasSeenOnboarding: val});
  },

  setLocked: (val) => {
    set({isLocked: val});
  },

  hydrate: () => {
    const token = storage.getString('auth_token');
    const userJson = storage.getString('auth_user');
    const hasSeenOnboarding = storage.getBoolean('has_seen_onboarding') ?? false;

    if (token && userJson) {
      try {
        const user: User = JSON.parse(userJson);
        set({token, user, isAuthenticated: true, hasSeenOnboarding});
      } catch {
        storage.remove('auth_token');
        storage.remove('auth_user');
        set({token: null, user: null, isAuthenticated: false, hasSeenOnboarding});
      }
    } else {
      set({hasSeenOnboarding});
    }
  },
}));
