/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ['./App.tsx', './src/**/*.{ts,tsx}'],
  presets: [require('nativewind/preset')],
  theme: {
    extend: {
      colors: {
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
        surface: {
          DEFAULT: '#030712',
          page:    '#030712',
          card:    '#090d16',
          raised:  '#111827',
          input:   '#111827',
        },
        text: {
          primary:   '#FAFAFA',
          secondary: '#A1A1AA',
          tertiary:  '#71717A',
        },
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
