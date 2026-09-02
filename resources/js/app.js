import './bootstrap';
import './agent-scanner';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

const themeStorageKey = 'aadl-theme';

const currentTheme = () => (
    document.documentElement.classList.contains('dark') ? 'dark' : 'light'
);

const updateThemeToggles = () => {
    const isDark = currentTheme() === 'dark';

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const label = isDark ? 'Activer le mode clair' : 'Activer le mode sombre';

        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);
        button.querySelector('[data-theme-toggle-sun]')?.classList.toggle('hidden', !isDark);
        button.querySelector('[data-theme-toggle-moon]')?.classList.toggle('hidden', isDark);
    });
};

document.addEventListener('DOMContentLoaded', () => {
    updateThemeToggles();

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const nextTheme = currentTheme() === 'dark' ? 'light' : 'dark';

            document.documentElement.classList.toggle('dark', nextTheme === 'dark');
            document.documentElement.dataset.theme = nextTheme;
            localStorage.setItem(themeStorageKey, nextTheme);
            updateThemeToggles();
        });
    });
});

Alpine.start();
