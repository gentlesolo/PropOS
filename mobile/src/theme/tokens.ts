export const themes = {
  dark: {
    // Surfaces
    surfacePage:    '#030712',
    surfaceCard:    '#090d16',
    surfaceRaised:  '#111827',
    surfaceSunken:  '#050811',
    surfaceInput:   '#111827',
    surfaceOverlay: 'rgba(2, 6, 23, 0.85)',

    // Text
    textPrimary:   '#FAFAFA',
    textSecondary: '#A1A1AA',
    textTertiary:  '#71717A',
    textDisabled:  '#3F3F46',
    textInverse:   '#09090B',
    textLink:      '#34D399',

    // Borders
    borderSubtle:  'rgba(255, 255, 255, 0.03)',
    borderDefault: 'rgba(255, 255, 255, 0.06)',
    borderStrong:  'rgba(255, 255, 255, 0.12)',

    // Brand (identical across modes)
    brandPrimary:   '#10B981',
    brandAccent:    '#F59E0B',
    brandPrimaryFg: '#FFFFFF',

    // States
    stateHoverBg:     'rgba(255, 255, 255, 0.06)',
    statePressedBg:   'rgba(255, 255, 255, 0.10)',
    stateSelectedBg:  '#022C22',
    stateSelectedText: '#34D399',

    // Semantic feedback
    successBg:     '#14532D',
    successText:   '#4ADE80',
    successBorder: 'rgba(74,222,128,0.20)',
    infoBg:        '#0C4A6E',
    infoText:      '#38BDF8',
    infoBorder:    'rgba(56,189,248,0.20)',
    warningBg:     '#78350F',
    warningText:   '#FBBF24',
    warningBorder: 'rgba(251,191,36,0.20)',
    dangerBg:      '#881337',
    dangerText:    '#FB7185',
    dangerBorder:  'rgba(251,113,133,0.20)',

    // Sentiment (calls)
    sentimentHot:     '#F43F5E',
    sentimentWarm:    '#F59E0B',
    sentimentCold:    '#38BDF8',
    sentimentNeutral: '#71717A',

    // Glass
    glassBg:     'rgba(15, 23, 42, 0.72)',
    glassBorder: 'rgba(255, 255, 255, 0.08)',

    // Shadows (RN shadow props)
    shadowSm:    { shadowColor: '#000', shadowOpacity: 0.4, shadowRadius: 3,  shadowOffset: { width: 0, height: 1 }, elevation: 2 },
    shadowMd:    { shadowColor: '#000', shadowOpacity: 0.5, shadowRadius: 8,  shadowOffset: { width: 0, height: 4 }, elevation: 4 },
    shadowBrand: { shadowColor: '#10B981', shadowOpacity: 0.16, shadowRadius: 12, shadowOffset: { width: 0, height: 4 }, elevation: 4 },

    // Skeleton
    skeletonBase:      '#27272A',
    skeletonHighlight: '#3F3F46',

    // Status bar / system
    statusBarStyle: 'light-content' as const,
    tabBarBg:       '#111827',
  },

  light: {
    // Surfaces
    surfacePage:    '#F8FAFC',
    surfaceCard:    '#FFFFFF',
    surfaceRaised:  '#FFFFFF',
    surfaceSunken:  '#F1F5F9',
    surfaceInput:   '#FFFFFF',
    surfaceOverlay: 'rgba(15, 23, 42, 0.50)',

    // Text
    textPrimary:   '#09090B',
    textSecondary: '#52525B',
    textTertiary:  '#A1A1AA',
    textDisabled:  '#D4D4D8',
    textInverse:   '#FFFFFF',
    textLink:      '#059669',

    // Borders
    borderSubtle:  'rgba(9, 9, 11, 0.05)',
    borderDefault: 'rgba(9, 9, 11, 0.10)',
    borderStrong:  'rgba(9, 9, 11, 0.20)',

    // Brand (identical across modes)
    brandPrimary:   '#10B981',
    brandAccent:    '#F59E0B',
    brandPrimaryFg: '#FFFFFF',

    // States
    stateHoverBg:     'rgba(0, 0, 0, 0.04)',
    statePressedBg:   'rgba(0, 0, 0, 0.08)',
    stateSelectedBg:  '#ECFDF5',
    stateSelectedText: '#047857',

    // Semantic feedback
    successBg:     '#F0FDF4',
    successText:   '#15803D',
    successBorder: '#BBF7D0',
    infoBg:        '#F0F9FF',
    infoText:      '#0369A1',
    infoBorder:    '#BAE6FD',
    warningBg:     '#FFFBEB',
    warningText:   '#B45309',
    warningBorder: '#FDE68A',
    dangerBg:      '#FFF1F2',
    dangerText:    '#BE123C',
    dangerBorder:  '#FECDD3',

    // Sentiment (calls) — slightly muted for light bg readability
    sentimentHot:     '#E11D48',
    sentimentWarm:    '#D97706',
    sentimentCold:    '#0284C7',
    sentimentNeutral: '#71717A',

    // Glass
    glassBg:     'rgba(255, 255, 255, 0.85)',
    glassBorder: 'rgba(0, 0, 0, 0.06)',

    // Shadows
    shadowSm:    { shadowColor: '#000', shadowOpacity: 0.06, shadowRadius: 3,  shadowOffset: { width: 0, height: 1 }, elevation: 2 },
    shadowMd:    { shadowColor: '#000', shadowOpacity: 0.08, shadowRadius: 8,  shadowOffset: { width: 0, height: 4 }, elevation: 4 },
    shadowBrand: { shadowColor: '#10B981', shadowOpacity: 0.18, shadowRadius: 12, shadowOffset: { width: 0, height: 4 }, elevation: 3 },

    // Skeleton
    skeletonBase:      '#E4E4E7',
    skeletonHighlight: '#F4F4F5',

    // Status bar / system
    statusBarStyle: 'dark-content' as const,
    tabBarBg:       '#FFFFFF',
  },
} satisfies Record<string, ThemeTokens>;

export type ThemeName = keyof typeof themes;

export interface ThemeTokens {
  // Surfaces
  surfacePage:    string;
  surfaceCard:    string;
  surfaceRaised:  string;
  surfaceSunken:  string;
  surfaceInput:   string;
  surfaceOverlay: string;
  // Text
  textPrimary:   string;
  textSecondary: string;
  textTertiary:  string;
  textDisabled:  string;
  textInverse:   string;
  textLink:      string;
  // Borders
  borderSubtle:  string;
  borderDefault: string;
  borderStrong:  string;
  // Brand
  brandPrimary:   string;
  brandAccent:    string;
  brandPrimaryFg: string;
  // States
  stateHoverBg:     string;
  statePressedBg:   string;
  stateSelectedBg:  string;
  stateSelectedText: string;
  // Semantic
  successBg:     string;
  successText:   string;
  successBorder: string;
  infoBg:        string;
  infoText:      string;
  infoBorder:    string;
  warningBg:     string;
  warningText:   string;
  warningBorder: string;
  dangerBg:      string;
  dangerText:    string;
  dangerBorder:  string;
  // Sentiment
  sentimentHot:     string;
  sentimentWarm:    string;
  sentimentCold:    string;
  sentimentNeutral: string;
  // Glass
  glassBg:     string;
  glassBorder: string;
  // Shadows
  shadowSm:    object;
  shadowMd:    object;
  shadowBrand: object;
  // Skeleton
  skeletonBase:      string;
  skeletonHighlight: string;
  // System
  statusBarStyle: 'light-content' | 'dark-content';
  tabBarBg:       string;
}
