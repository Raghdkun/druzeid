import { Head, Link } from '@inertiajs/react';

import { useI18n } from '@/hooks/use-i18n';
import { SiteLayout } from '@/layouts/site-layout';
import { home } from '@/lib/site-routes';
import type { SiteMenus, SiteSettings } from '@/types/site';

interface Props {
    status: number;
    menus: SiteMenus;
    settings: SiteSettings;
    [key: string]: unknown;
}

/**
 * Branded error page rendered inside the public shell, so a 404/403/500 keeps
 * the header, footer and navigation rather than dropping to a bare Laravel
 * error screen. Copy comes from the translation files, so it is localized like
 * the rest of the site.
 */
export default function SiteError({ status, menus, settings }: Props) {
    const { t } = useI18n();

    const titles: Record<number, string> = {
        404: t('common.page_not_found'),
        403: t('common.error'),
        500: t('common.error'),
        503: t('common.error'),
    };

    const bodies: Record<number, string> = {
        404: t('common.page_not_found_body'),
    };

    const title = titles[status] ?? t('common.error');
    const body = bodies[status] ?? '';

    return (
        <SiteLayout menus={menus} settings={settings}>
            <Head title={title} />

            <div className="mx-auto flex max-w-2xl flex-col items-center px-4 py-24 text-center sm:py-32">
                <p className="font-display text-6xl font-black text-druze-blue sm:text-8xl">
                    {status}
                </p>
                <h1 className="mt-6 font-display text-2xl font-bold text-ink sm:text-3xl">{title}</h1>
                {body && <p className="mt-4 max-w-md text-ink-muted">{body}</p>}

                <Link
                    href={home()}
                    className="mt-10 inline-flex items-center gap-2 rounded-md border-2 border-druze-blue px-6 py-2.5 font-semibold text-druze-blue transition-colors hover:bg-druze-blue hover:text-white"
                >
                    {t('common.back_home')}
                </Link>
            </div>
        </SiteLayout>
    );
}
