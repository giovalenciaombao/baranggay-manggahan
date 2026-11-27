/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#F5F7F0',
          100: '#E6EDDB',
          200: '#CDDAB9',
          300: '#A9C18D',
          400: '#7DA160',
          500: '#386044',
          600: '#31563A',
          700: '#26472E',
          800: '#1D3824',
          DEFAULT: '#386044',
          dark: '#172D1E',
          light: '#F9F6E9',
        }
      },
      fontFamily: {
        sans: ['Plus Jakarta Sans', 'sans-serif'],
        display: ['Fraunces', 'serif'],
      },
      screens: {
        xs: '375px',
      }
    }
  },
  plugins: []
}
