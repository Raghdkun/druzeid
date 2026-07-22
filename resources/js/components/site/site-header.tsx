import { Link } from '@inertiajs/react';

import { DruzeStar } from '@/components/site/druze-star';
import { LanguageSwitcher } from '@/components/site/language-switcher';
import { MobileNav } from '@/components/site/mobile-nav';
import { PrimaryNav } from '@/components/site/primary-nav';
import { useI18n } from '@/hooks/use-i18n';
import type { NavItem, SiteSettings } from '@/types/site';

/**
 * The site header, top to bottom, mirroring un.org's restraint:
 *   1. a thin utility strip with the language row (first in the DOM)
 *   2. a white masthead — emblem + wordmark + tagline, hairline underneath
 *   3. a full-width dark primary-nav bar (desktop) / burger (mobile)
 */
export function SiteHeader({
    menu,
    settings,
}: {
    menu: NavItem[];
    settings: SiteSettings;
}) {
    const { t, homeUrl } = useI18n();

    return (
        <header className="relative z-30">
            {/* Utility strip — language row */}
            <div className="border-b border-hairline bg-surface-alt">
                <div className="mx-auto flex max-w-6xl items-center justify-end px-4 sm:px-6 lg:px-8">
                    <LanguageSwitcher className="py-1.5" />
                </div>
            </div>

            {/* Masthead */}
            <div className="border-b border-hairline bg-white">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                    <Link href={homeUrl} className="flex items-center gap-3">
                        {settings['site.logo_url'] ? (
                            <img
                                src={settings['site.logo_url']}
                                alt=""
                                className="h-12 w-12 shrink-0 object-contain"
                            />
                        ) : (
                            <DruzeStar className="h-12 w-12 shrink-0" decorative />
                        )}
                        <span className="flex flex-col">
                            <span className="font-display text-lg font-bold leading-tight text-ink sm:text-xl">
                                {settings['site.short_name'] ?? t('site.logo_alt')}
                            </span>
                            {settings['site.tagline'] && (
                                <span className="hidden text-xs text-ink-muted sm:block">
                                    {settings['site.tagline']}
                                </span>
                            )}
                        </span>
                    </Link>

                    <MobileNav items={menu} />
                </div>
            </div>

            {/* Primary nav bar */}
            <div className="bg-bar-dark">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <PrimaryNav items={menu} />
                </div>
            </div>
        </header>
    );
}
