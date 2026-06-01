import {useAuthStore} from '../src/store/authStore';

// Mock MMKV so the store works in Node
jest.mock('react-native-mmkv', () => ({
  MMKV: class {
    private _store: Record<string, string> = {};
    set(key: string, val: string) { this._store[key] = val; }
    getString(key: string) { return this._store[key] ?? undefined; }
    delete(key: string) { delete this._store[key]; }
    clearAll() { this._store = {}; }
  },
}));

describe('authStore', () => {
  beforeEach(() => useAuthStore.getState().clearAuth());

  it('starts unauthenticated', () => {
    const {isAuthenticated, token, user} = useAuthStore.getState();
    expect(isAuthenticated).toBe(false);
    expect(token).toBeNull();
    expect(user).toBeNull();
  });

  it('setAuth marks authenticated and stores user', () => {
    const fakeUser = {
      id: 1, first_name: 'Jane', last_name: 'Doe',
      email: 'jane@test.com', agency_id: 1,
    } as any;

    useAuthStore.getState().setAuth('tok_abc', fakeUser);

    const {isAuthenticated, token, user} = useAuthStore.getState();
    expect(isAuthenticated).toBe(true);
    expect(token).toBe('tok_abc');
    expect(user?.first_name).toBe('Jane');
  });

  it('clearAuth resets state', () => {
    const fakeUser = {id: 1, first_name: 'Jane', last_name: 'Doe', email: 'j@t.com', agency_id: 1} as any;
    useAuthStore.getState().setAuth('tok_abc', fakeUser);
    useAuthStore.getState().clearAuth();

    const {isAuthenticated, token, user} = useAuthStore.getState();
    expect(isAuthenticated).toBe(false);
    expect(token).toBeNull();
    expect(user).toBeNull();
  });
});
