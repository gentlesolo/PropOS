import React, {createContext, useContext, useEffect, useState} from 'react';
import {Appearance, StatusBar} from 'react-native';
import {useColorScheme as useNWColorScheme} from 'nativewind';
import {storage} from '../api/client';
import {themes, ThemeName, ThemeTokens} from './tokens';

export type ThemePreference = 'light' | 'dark' | 'system';

interface ThemeContextValue {
  preference: ThemePreference;
  resolvedTheme: ThemeName;
  tokens: ThemeTokens;
  setPreference: (pref: ThemePreference) => void;
}

const ThemeContext = createContext<ThemeContextValue | undefined>(undefined);

const STORAGE_KEY = 'propos-theme-preference';

export function ThemeProvider({children}: {children: React.ReactNode}) {
  const {setColorScheme} = useNWColorScheme();

  // Default to 'dark' for PropOS — preserves current behavior for existing users
  const [preference, setPreferenceState] = useState<ThemePreference>(() => {
    return (storage.getString(STORAGE_KEY) as ThemePreference) ?? 'dark';
  });

  const [systemScheme, setSystemScheme] = useState(
    Appearance.getColorScheme() ?? 'dark',
  );

  useEffect(() => {
    const sub = Appearance.addChangeListener(({colorScheme}) => {
      setSystemScheme(colorScheme ?? 'dark');
    });
    return () => sub.remove();
  }, []);

  const resolvedTheme: ThemeName =
    preference === 'system'
      ? systemScheme === 'light'
        ? 'light'
        : 'dark'
      : preference;

  useEffect(() => {
    setColorScheme(resolvedTheme);
    StatusBar.setBarStyle(themes[resolvedTheme].statusBarStyle, true);
  }, [resolvedTheme, setColorScheme]);

  const setPreference = (pref: ThemePreference) => {
    setPreferenceState(pref);
    storage.set(STORAGE_KEY, pref);
  };

  return (
    <ThemeContext.Provider
      value={{
        preference,
        resolvedTheme,
        tokens: themes[resolvedTheme],
        setPreference,
      }}>
      {children}
    </ThemeContext.Provider>
  );
}

export function useTheme() {
  const ctx = useContext(ThemeContext);
  if (!ctx) throw new Error('useTheme must be used within ThemeProvider');
  return ctx;
}
