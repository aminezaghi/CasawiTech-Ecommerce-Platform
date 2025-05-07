module.exports = {
  content: ["./**/*.{html,js,php}"],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        nzxt: {
          black: '#0d0e10',
          dark: '#16181a',
          purple: '#7315e5',
          blue: '#00aeff',
          gray: '#b4b9c2',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', 'sans-serif'],
      },
      transitionDuration: {
        250: '250ms',
      },
      boxShadow: {
        'nzxt': '0 4px 12px rgba(0, 0, 0, 0.25)',
        'nzxt-hover': '0 8px 24px rgba(0, 0, 0, 0.35)',
      },
      animation: {
        'fade-in': 'fadeIn 0.3s ease-in-out',
      },
      keyframes: {
        fadeIn: {
          '0%': { opacity: '0', transform: 'translateY(10px)' },
          '100%': { opacity: '1', transform: 'translateY(0)' },
        }
      }
    },
  },
  plugins: [],
} 