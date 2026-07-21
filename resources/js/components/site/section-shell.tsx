import type { PropsWithChildren } from 'react';

import { cn } from '@/lib/utils';

/**
 * Vertical rhythm and two-tone banding for a page section.
 *
 * Bands alternate white / #F4F4F4 and never go beyond two tones — the
 * restraint that reads as institutional. `tone` is set by the page renderer
 * from the section's index so the alternation is automatic.
 */
export function SectionShell({
    children,
    tone = 'default',
    id,
    className,
}: PropsWithChildren<{
    tone?: 'default' | 'alt' | 'tint';
    id?: string;
    className?: string;
}>) {
    return (
        <section
            id={id}
            className={cn(
                'scroll-mt-28 py-14 sm:py-20',
                tone === 'alt' && 'bg-surface-alt',
                tone === 'tint' && 'bg-druze-blue-tint',
                className,
            )}
        >
            <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">{children}</div>
        </section>
    );
}

/**
 * The UN section heading: display type with a short centred blue rule. Pass
 * `align="start"` for interior pages, where the rule sits at the inline start.
 */
export function SectionHeading({
    children,
    eyebrow,
    align = 'center',
}: PropsWithChildren<{ eyebrow?: string; align?: 'center' | 'start' }>) {
    return (
        <header className={cn('mb-10', align === 'center' ? 'text-center' : 'text-start')}>
            {eyebrow && <p className="eyebrow">{eyebrow}</p>}
            <h2 className="font-display text-3xl font-bold text-ink sm:text-4xl">{children}</h2>
            <span
                aria-hidden
                className={cn(
                    'mt-4 block h-[3px] w-16 bg-druze-blue',
                    align === 'center' ? 'mx-auto' : 'me-auto',
                )}
            />
        </header>
    );
}
