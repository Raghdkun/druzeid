import { Link, usePage } from '@inertiajs/react';

import { DruzeStar } from '@/components/site/druze-star';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { SiteSettings } from '@/types/site';

/**
 * The organisation's emblem + name, linking home. Shown over the banner image
 * (hero / page header) so the identity sits within the image frame like the
 * old site, rather than in a separate white bar above it. `tone` picks white
 * (over a dark, scrimmed image) or ink (on a light title block).
 */
export function BrandLockup({
    tone = 'light',
    variant = 'full',
}: {
    tone?: 'light' | 'ink';
    /** 'full' = emblem + name; 'emblem' = the mark only (name is elsewhere). */
    variant?: 'full' | 'emblem';
}) {
    const { props } = usePage<{ settings?: SiteSettings }>();
    const { homeUrl, t } = useI18n();
    const settings = props.settings;
    const name = settings?.['site.short_name'] ?? t('site.logo_alt');
    const logo = settings?.['site.logo_url'] ?? null;
    const emblemSize = variant === 'emblem' ? 'h-16 w-16 sm:h-20 sm:w-20' : 'h-11 w-11';

    return (
        <Link
            href={homeUrl}
            aria-label={name}
            className={cn(
                'inline-flex items-center gap-3 font-display font-bold',
                tone === 'light' ? 'text-white drop-shadow-sm' : 'text-ink',
            )}
        >
            {logo ? (
                <img src={logo} alt="" className={cn('shrink-0 object-contain', emblemSize)} />
            ) : (
                <DruzeStar className={cn('shrink-0', emblemSize)} decorative />
            )}
            {variant === 'full' && <span className="text-lg leading-tight sm:text-xl">{name}</span>}
        </Link>
    );
}
