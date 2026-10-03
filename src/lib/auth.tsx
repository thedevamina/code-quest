import { createContext, useContext, useEffect, useState, type ReactNode } from 'react';
import { api, type ApiUser } from '@/services/api';

type Profile = { id: number; user_id: number; full_name: string; learning_goal: string };
type User = { id: string; email?: string; role?: string; user_metadata?: Record<string, unknown> };

type AuthContextValue = {
  session: { user: User } | null;
  user: User | null;
  profile: Profile | null;
  loading: boolean;
  signUp: (email: string, password: string, fullName: string) => Promise<{ error: string | null }>;
  signIn: (email: string, password: string) => Promise<{ error: string | null }>;
  signOut: () => Promise<void>;
  updateProfile: (fullName: string, learningGoal: string) => Promise<{ error: string | null }>;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

export function AuthProvider({ children }: { children: ReactNode }) {
  const [session, setSession] = useState<{ user: User } | null>(null);
  const [profile, setProfile] = useState<Profile | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.me().then((user) => { setUser(user); }).catch(() => { setLoading(false); });
  }, []);

  const setUser = (user: ApiUser): void => { setSession({ user: { id: user.id, email: user.email, role: user.role } }); setProfile(user.profile); setLoading(false); };

  const signUp = async (email: string, password: string, fullName: string) => {
    try { setUser(await api.register(fullName, email, password)); } catch (error) { return { error: error instanceof Error ? error.message : 'Registration failed.' }; }
    return { error: null };
  };

  const signIn = async (email: string, password: string) => {
    try { setUser(await api.login(email, password)); } catch (error) { return { error: error instanceof Error ? error.message : 'Login failed.' }; }
    return { error: null };
  };

  const signOut = async () => {
    await api.logout();
    setProfile(null);
    setSession(null);
  };

  const updateProfile = async (fullName: string, learningGoal: string) => {
    if (!session?.user.id) return { error: 'You must be signed in.' };
    try { setProfile(await api.updateProfile(fullName, learningGoal)); } catch (error) { return { error: error instanceof Error ? error.message : 'Profile update failed.' }; }
    return { error: null };
  };

  return (
    <AuthContext.Provider value={{ session, user: session?.user ?? null, profile, loading, signUp, signIn, signOut, updateProfile }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const ctx = useContext(AuthContext);
  if (!ctx) throw new Error('useAuth must be used within AuthProvider');
  return ctx;
}
