import {createMMKV} from 'react-native-mmkv';

const cache = createMMKV({id: 'propos_cache'});

const TTL = {
  contacts: 5 * 60 * 1000,   // 5 minutes
  tasks:    2 * 60 * 1000,   // 2 minutes
  viewings: 5 * 60 * 1000,   // 5 minutes
  brief:    30 * 60 * 1000,  // 30 minutes
};

interface CacheEntry<T> {
  data: T;
  cachedAt: number;
}

function set<T>(key: string, data: T): void {
  cache.set(key, JSON.stringify({data, cachedAt: Date.now()}));
}

function get<T>(key: string, ttl: number): T | null {
  const raw = cache.getString(key);
  if (!raw) return null;

  try {
    const entry: CacheEntry<T> = JSON.parse(raw);
    if (Date.now() - entry.cachedAt > ttl) return null;
    return entry.data;
  } catch {
    return null;
  }
}

function clear(key: string): void {
  cache.remove(key);
}

function clearAll(): void {
  cache.clearAll();
}

export const cacheService = {
  contacts: {
    set: <T>(data: T) => set('contacts_list', data),
    get: <T>() => get<T>('contacts_list', TTL.contacts),
    invalidate: () => clear('contacts_list'),
  },

  contact: {
    set: <T>(id: number, data: T) => set(`contact_${id}`, data),
    get: <T>(id: number) => get<T>(`contact_${id}`, TTL.contacts),
    invalidate: (id: number) => clear(`contact_${id}`),
  },

  tasks: {
    set: <T>(data: T) => set('tasks_today', data),
    get: <T>() => get<T>('tasks_today', TTL.tasks),
    invalidate: () => clear('tasks_today'),
  },

  viewings: {
    set: <T>(data: T) => set('viewings_today', data),
    get: <T>() => get<T>('viewings_today', TTL.viewings),
    invalidate: () => clear('viewings_today'),
  },

  brief: {
    set: <T>(data: T) => set('daily_brief', data),
    get: <T>() => get<T>('daily_brief', TTL.brief),
    invalidate: () => clear('daily_brief'),
  },

  clearAll,
};
