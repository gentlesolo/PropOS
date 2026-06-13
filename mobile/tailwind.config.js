/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./App.tsx', './src/**/*.{ts,tsx}'],
  presets: [require('nativewind/preset')],
  // 'class' mode — NativeWind's useColorScheme().setColorScheme() drives this
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        // ── Brand palette ─────────────────────────────────────────────────
        brand: {
          50:  '#ecfdf5',
          100: '#d1fae5',
          200: '#a7f3d0',
          300: '#6ee7b7',
          400: '#34d399',
          500: '#10b981',
          600: '#059669',
          700: '#047857',
          800: '#065f46',
          900: '#064e3b',
          950: '#022c22',
        },

        // ── Surfaces (light default / -dark variant) ──────────────────────
        'surface-page':        '#F8FAFC',
        'surface-page-dark':   '#030712',
        'surface-card':        '#FFFFFF',
        'surface-card-dark':   '#090d16',
        'surface-raised':      '#FFFFFF',
        'surface-raised-dark': '#111827',
        'surface-sunken':      '#F1F5F9',
        'surface-sunken-dark': '#050811',
        'surface-input':       '#FFFFFF',
        'surface-input-dark':  '#111827',

        // Legacy aliases kept for backward compat (dark-mode values)
        surface: {
          DEFAULT: '#030712',
          page:    '#030712',
          card:    '#090d16',
          raised:  '#111827',
          input:   '#111827',
        },

        // ── Text ──────────────────────────────────────────────────────────
        'text-primary':          '#09090B',
        'text-primary-dark':     '#FAFAFA',
        'text-secondary':        '#52525B',
        'text-secondary-dark':   '#A1A1AA',
        'text-tertiary':         '#A1A1AA',
        'text-tertiary-dark':    '#71717A',
        'text-disabled':         '#D4D4D8',
        'text-disabled-dark':    '#3F3F46',
        'text-link':             '#059669',
        'text-link-dark':        '#34D399',

        // Legacy aliases (dark-mode values)
        text: {
          primary:   '#FAFAFA',
          secondary: '#A1A1AA',
          tertiary:  '#71717A',
        },

        // ── Borders ───────────────────────────────────────────────────────
        'border-subtle':       'rgba(9,9,11,0.05)',
        'border-subtle-dark':  'rgba(255,255,255,0.03)',
        'border-default':      'rgba(9,9,11,0.10)',
        'border-default-dark': 'rgba(255,255,255,0.06)',
        'border-strong':       'rgba(9,9,11,0.20)',
        'border-strong-dark':  'rgba(255,255,255,0.12)',

        // ── Semantic states ───────────────────────────────────────────────
        'state-selected-bg':       '#ECFDF5',
        'state-selected-bg-dark':  '#022C22',
        'state-selected-text':     '#047857',
        'state-selected-text-dark': '#34D399',

        // ── Semantic feedback ─────────────────────────────────────────────
        'success-bg':       '#F0FDF4',
        'success-bg-dark':  '#14532D',
        'success-text':     '#15803D',
        'success-text-dark': '#4ADE80',
        'info-bg':          '#F0F9FF',
        'info-bg-dark':     '#0C4A6E',
        'info-text':        '#0369A1',
        'info-text-dark':   '#38BDF8',
        'warning-bg':       '#FFFBEB',
        'warning-bg-dark':  '#78350F',
        'warning-text':     '#B45309',
        'warning-text-dark': '#FBBF24',
        'danger-bg':        '#FFF1F2',
        'danger-bg-dark':   '#881337',
        'danger-text':      '#BE123C',
        'danger-text-dark': '#FB7185',

        // ── Tab bar ───────────────────────────────────────────────────────
        'tab-bar':      '#FFFFFF',
        'tab-bar-dark': '#111827',

        // ── Legacy semantic tokens ────────────────────────────────────────
        accent: {
          DEFAULT: '#F59E0B',
          amber:   '#F59E0B',
        },
        danger: {
          DEFAULT: '#F43F5E',
        },
        success: {
          DEFAULT: '#22C55E',
        },
        info: {
          DEFAULT: '#0EA5E9',
        },
      },
    },
  },
  plugins: [],
};
