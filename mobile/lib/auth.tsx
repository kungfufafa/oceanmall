import { api, setAuthToken, type User } from '@/lib/api';
import { deleteSecureItem, getSecureItem, setSecureItem } from '@/lib/storage';
import { createContext, useContext, useEffect, useMemo, useState, type ReactNode } from 'react';
import { ActivityIndicator, View } from 'react-native';

const TOKEN_KEY = 'oceanmall.token';

type AuthContextValue = {
  ready: boolean;
  user: User | null;
  login: (email: string, password: string) => Promise<void>;
  register: (payload: {
    first_name: string;
    last_name: string;
    email: string;
    password: string;
    password_confirmation: string;
    phone_number?: string;
  }) => Promise<void>;
  logout: () => Promise<void>;
  refresh: () => Promise<void>;
};

const AuthContext = createContext<AuthContextValue | null>(null);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [ready, setReady] = useState(false);
  const [user, setUser] = useState<User | null>(null);

  async function applyToken(token: string | null) {
    setAuthToken(token);
    if (token) {
      await setSecureItem(TOKEN_KEY, token);
    } else {
      await deleteSecureItem(TOKEN_KEY);
    }
  }

  async function hydrateUser() {
    const me = await api<{ data: User }>('/auth/me');
    setUser(me.data);
  }

  useEffect(() => {
    (async () => {
      try {
        const stored = await getSecureItem(TOKEN_KEY);
        if (stored) {
          setAuthToken(stored);
          await hydrateUser();
        }
      } catch {
        await applyToken(null);
        setUser(null);
      } finally {
        setReady(true);
      }
    })();
  }, []);

  const value = useMemo<AuthContextValue>(
    () => ({
      ready,
      user,
      async login(email, password) {
        const res = await api<{ token: string; data: User }>('/auth/login', {
          method: 'POST',
          body: JSON.stringify({ email, password }),
        });
        await applyToken(res.token);
        setUser(res.data);
      },
      async register(payload) {
        const res = await api<{ token: string; data: User }>('/auth/register', {
          method: 'POST',
          body: JSON.stringify(payload),
        });
        await applyToken(res.token);
        setUser(res.data);
      },
      async logout() {
        try {
          await api('/auth/logout', { method: 'POST' });
        } catch {
          // ignore network errors on logout
        }
        await applyToken(null);
        setUser(null);
      },
      async refresh() {
        await hydrateUser();
      },
    }),
    [ready, user]
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function AuthGate({ children }: { children: ReactNode }) {
  const { ready } = useAuth();

  if (!ready) {
    return (
      <View className="flex-1 items-center justify-center bg-background">
        <ActivityIndicator />
      </View>
    );
  }

  return children;
}

export function useAuth(): AuthContextValue {
  const ctx = useContext(AuthContext);
  if (!ctx) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return ctx;
}
