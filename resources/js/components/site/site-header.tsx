import { Link } from '@inertiajs/react';

import { DruzeStar } from '@/components/site/druze-star';
import { LanguageSwitcher } from '@/components/site/language-switcher';
import { MobileNav } from '@/components/site/mobile-nav';
import { NewsTicker } from '@/components/site/news-ticker';
import { PrimaryNav } from '@/components/site/primary-nav';
import { useI18n } from '@/hooks/use-i18n';
import type { NavItem, SiteSettings } from '@/types/site';

/**
 * The site header:
 *   1. a thin utility strip with the language row (first in the DOM)
 *   2. a full-width dark bar with the clickable logo (home link, every
 *      breakpoint) plus the primary nav (desktop) / burger (mobile)
 *   3. the news ticker
 *
 * The logo in the bar is the reliable "back to home" affordance; the banner
 * also carries the larger identity via <BrandLockup>.
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

            {/* Primary nav bar (dark) */}
            <div className="bg-bar-dark">
                <div className="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
                    {/* Logo — the persistent home link, on every breakpoint. */}
                    <Link href={homeUrl} className="flex shrink-0 items-center gap-2 py-2.5" aria-label={t('nav.home')}>
                        {settings['site.logo_url'] ? (
                            <img src={settings['site.logo_url']} alt="" className="size-9 shrink-0 object-contain" />
                        ) : (
                            <DruzeStar className="size-9 shrink-0" decorative />
                        )}
                        <span className="font-display text-sm font-bold text-white sm:text-base">
                            {settings['site.short_name'] ?? t('site.logo_alt')}
                        </span>
                    </Link>

                    <PrimaryNav items={menu} />
                    <MobileNav items={menu} />
                </div>
            </div>

            {/* News ticker — latest headlines as text lines, directly under
                the nav bar. */}
            <NewsTicker />
        </header>
    );
}
