import { Link } from '@inertiajs/react';

import { Picture } from '@/components/site/picture';
import { useI18n } from '@/hooks/use-i18n';
import { accentClasses } from '@/lib/accent';
import { cn } from '@/lib/utils';
import type { Card } from '@/types/site';

/**
 * A chrome-free photo card — the single most important thing borrowed from
 * un.org. No border, no shadow, no radius, no container fill: the image sits
 * flush on the page, with an accent rule above the eyebrow doing the framing.
 *
 * The whole card is one link. Hover lifts nothing — it deepens the headline
 * and extends the rule, staying within the institutional restraint.
 */
export function SectionCard({ card }: { card: Card }) {
    const { t } = useI18n();
    const accent = accentClasses(card.accent);

    return (
        <Link href={card.href} className="group block focus-visible:outline-none">
            <article className="flex h-full flex-col">
                <div className="relative aspect-[3/2] overflow-hidden bg-surface-alt">
                    <Picture
                        image={card.image}
                        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    />
                    {card.underConstruction && (
                        <span className="absolute bottom-0 start-0 bg-bar-dark/85 px-3 py-1 text-xs font-semibold text-white">
                            {t('status.coming_soon')}
                        </span>
                    )}
                </div>

                {/* Accent rule + eyebrow */}
                <div className="mt-4 flex items-center gap-2">
                    <span aria-hidden className={cn('h-[3px] w-8', accent.rule)} />
                </div>

                <h3 className="mt-2 font-display text-lg font-bold text-ink transition-colors group-hover:text-druze-blue sm:text-xl">
                    {card.title}
                </h3>

                {card.excerpt && (
                    <p className="mt-2 line-clamp-3 text-sm text-ink-muted">{card.excerpt}</p>
                )}

                <span
                    className={cn(
                        'mt-3 inline-flex items-center gap-1 text-sm font-semibold',
                        accent.text,
                    )}
                >
                    {t('common.read_more')}
                    <ChevronEnd />
                </span>
            </article>
        </Link>
    );
}

/** A chevron that points toward the inline end — mirrors automatically in RTL. */
function ChevronEnd() {
    return (
        <svg
            width="16"
            height="16"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className="transition-transform group-hover:translate-x-0.5 rtl:-scale-x-100"
        >
            <path d="M9 6l6 6-6 6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}
