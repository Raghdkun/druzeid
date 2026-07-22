import { createInertiaApp, router } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

/**
 * Keep <html lang> and <html dir> in step with the locale of the page being
 * shown.
 *
 * Blade sets both on the initial document, but Inertia only swaps the page
 * component — it never touches <html>. Crossing locales client-side (an
 * Arabic link followed from an English page, browser back/forward, the
 * language switcher) would otherwise leave the document stuck in the previous
 * language's direction, rendering RTL content left-to-right. Reading it from
 * the shared props means the server always decides the direction.
 */
function syncDocumentLanguage(page: { props: Record<string, unknown> }): void {
    const { htmlLang, dir, locale } = page.props as {
        htmlLang?: string;
        dir?: string;
        locale?: string;
    };
    const root = document.documentElement;

    if (dir && root.getAttribute('dir') !== dir) {
        root.setAttribute('dir', dir);
    }

    const lang = htmlLang ?? locale;

    if (lang && root.getAttribute('lang') !== lang) {
        root.setAttribute('lang', lang);
    }
}

// `success` fires as soon as the new page arrives (before the swap paints);
// `navigate` also covers history back/forward, where no request is made.
router.on('success', (event) => syncDocumentLanguage(event.detail.page));
router.on('navigate', (event) => syncDocumentLanguage(event.detail.page));

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            // Public site pages bring their own SiteLayout.
            case name.startsWith('site/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            // CMS pages self-wrap <AppLayout> so they can pass per-page
            // breadcrumbs; applying it here too would nest the whole sidebar
            // shell inside itself (doubled header/sidebar).
            default:
                return null;
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
                <Toaster />
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
