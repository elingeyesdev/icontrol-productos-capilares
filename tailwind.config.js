import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    // Usamos la estrategia de clase para el modo oscuro (igual que tu <html data-theme="dark">
    // original), controlada manualmente por theme.js con localStorage — no depende del SO.
    darkMode: ['selector', '[data-theme="dark"]'],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Nunito', ...defaultTheme.fontFamily.sans],
                display: ['"Playfair Display"', 'serif'],
            },
            colors: {
                // Paleta "Bloom Beauty" trasladada de tu style.css original.
                // Cada token cambia solo con [data-theme="dark"] via CSS vars,
                // así puedes usar clases como bg-brand-bg, text-brand-text, etc.
                brand: {
                    bg: 'var(--clr-bg)',
                    navbar: 'var(--clr-navbar)',
                    sidebar: 'var(--clr-sidebar)',
                    card: 'var(--clr-card)',
                    text: 'var(--clr-text)',
                    sub: 'var(--clr-sub)',
                    accent: 'var(--clr-accent)',
                    accent2: 'var(--clr-accent2)',
                    hover: 'var(--clr-hover)',
                    active: 'var(--clr-active)',
                    white: 'var(--clr-white)',
                },
                pastel: {
                    pink: 'var(--pink-bg)',
                    purple: 'var(--purple-bg)',
                    mint: 'var(--mint-bg)',
                    peach: 'var(--peach-bg)',
                    yellow: 'var(--yellow-bg)',
                },
            },
            boxShadow: {
                navbar: '0 2px 14px rgba(140, 80, 160, .15)',
                card: '0 4px 16px rgba(0, 0, 0, .08)',
            },
            borderRadius: {
                pill: '20px',
            },
        },
    },

    plugins: [forms],
};