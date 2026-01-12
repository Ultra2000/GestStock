import preset from './vendor/filament/filament/tailwind.config.preset'
import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    presets: [preset],
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './app/Filament/**/*.php',
        './resources/views/filament/**/*.blade.php',
        './vendor/filament/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', 'Figtree', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // FRECORP Brand Colors
                'frecorp': {
                    50: '#eef2ff',
                    100: '#e0e7ff',
                    200: '#c7d2fe',
                    300: '#a5b4fc',
                    400: '#818cf8',
                    500: '#6366f1',
                    600: '#4f46e5',
                    700: '#4338ca',
                    800: '#3730a3',
                    900: '#312e81',
                    950: '#1e1b4b',
                },
                'slate-dark': {
                    900: '#0f172a',
                    800: '#1e293b',
                    700: '#334155',
                },
            },
            backgroundImage: {
                'gradient-frecorp': 'linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%)',
            },
            boxShadow: {
                'glow': '0 4px 20px rgba(99, 102, 241, 0.4)',
                'glow-lg': '0 8px 40px rgba(99, 102, 241, 0.3)',
            },
        },
    },

    plugins: [forms],
};
