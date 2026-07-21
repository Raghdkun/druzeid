import { useLayoutEffect } from 'react';
import type { PropsWithChildren } from 'react';

import { SiteFooter } from '@/components/site/site-footer';
import { SiteHeader } from '@/components/site/site-header';
import { useI18n } from '@/hooks/use-i18n';
import type { SiteMenus, SiteSettings } from '@/types/site';

/**
 * The public site shell. Every public page carries `menus` and `settings`
 * from SiteChrome; this layout reads them and frames the page with the
 * header, a single semantic <main>, and the footer.
 *
 * The public site is light-only by design — an institutional white canvas,
 * like un.org, which has no dark mode. Dark mode is a CMS-only affordance, so
 * while a public page is mounted we clear the `.dark` class the starter kit
 * applies to <html> (restoring it on unmount when navigating into the CMS).
 * Done in a layout effect so it runs before paint.
 */
export function SiteLayout({
    menus,
    settings,
    children,
}: PropsWithChildren<{ menus: SiteMenus; settings: SiteSettings }>) {
    const { t } = useI18n();

    useLayoutEffect(() => {
        const root = document.documentElement;
        const wasDark = root.classList.contains('dark');
        root.classList.remove('dark');

        return () => {
            if (wasDark) {
                root.classList.add('dark');
            }
        };
    }, []);

    return (
        <div className="flex min-h-screen flex-col bg-background">
            <a href="#main-content" className="skip-link">
                {t('skip_to_content')}
            </a>

            <SiteHeader menu={menus.primary} settings={settings} />

            <main id="main-content" className="flex-1">
                {children}
            </main>

            <SiteFooter
                sections={menus.footerSections}
                organization={menus.footerOrganization}
                settings={settings}
            />
        </div>
    );
}
