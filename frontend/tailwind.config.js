/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{vue,js,ts}'],
  theme: {
    extend: {
      colors: {
        primary: {
          50:  '#F5F0DC',
          100: '#EDE5C0',
          200: '#D9CE91',
          300: '#B8AC5A',
          400: '#7A8C3A',
          500: '#5C6B2E',
          600: '#4A5625',
          700: '#3A441C',
          800: '#2C3315',
          DEFAULT: '#5C6B2E',
          dark: '#1E2D10',
          light: '#F5F0DC',
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
