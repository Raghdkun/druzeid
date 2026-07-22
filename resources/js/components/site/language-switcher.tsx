import { Link } from '@inertiajs/react';

import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

/**
 * The language row — the first thing in the header, mirroring un.org's
 * placement. All four languages sit at equal weight and size, with no flags
 * (flags conflate language with nationality; the UN deliberately avoids them,
 * and so do we). Each links to the equivalent URL in that language.
 */
export function LanguageSwitcher({ className }: { className?: string }) {
    const { locales, t } = useI18n();

    return (
        <nav aria-label={t('lang.switcher_label')} className={className}>
            <ul className="flex flex-wrap items-center gap-x-1 gap-y-1">
                {locales.map((locale) => (
                    <li key={locale.code}>
                        {/* An Inertia visit, so switching language keeps the SPA
                            alive; <html lang>/<html dir> are re-synced from the
                            new page's shared props (see app.tsx). */}
                        <Link
                            href={locale.url}
                            hrefLang={locale.code}
                            lang={locale.code}
                            dir={locale.dir}
                            aria-current={locale.active ? 'true' : undefined}
                            className={cn(
                                'inline-block rounded px-2.5 py-1 text-sm transition-colors',
                                locale.active
                                    ? 'font-semibold text-ink'
                                    : 'text-ink-muted hover:text-druze-blue',
                            )}
                        >
                            {locale.name}
                        </Link>
                    </li>
                ))}
            </ul>
        </nav>
    );
}
