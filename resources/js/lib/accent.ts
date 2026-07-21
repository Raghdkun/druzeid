import type { Accent } from '@/types/site';

/**
 * Maps a Druze-star accent to its Tailwind classes.
 *
 * Static class strings (not interpolation) so Tailwind's compiler keeps them.
 * `rule`/`text` are the institutional uses — a structural edge and, sparingly,
 * a label colour. There is deliberately no "fill" variant: like un.org, the
 * accent is punctuation, never a background wash.
 */

interface AccentClasses {
    /** Left/inline-start structural edge (3–4px). */
    edge: string;
    /** Thin rule / underline background. */
    rule: string;
    /** Accent text — reserved for eyebrows and small labels. */
    text: string;
    /** Faint tinted surface for "coming soon" panels only. */
    tint: string;
}

const MAP: Record<Exclude<Accent, null>, AccentClasses> = {
    blue: {
        edge: 'border-druze-blue',
        rule: 'bg-druze-blue',
        text: 'text-druze-blue',
        tint: 'bg-druze-blue-tint',
    },
    green: {
        edge: 'border-druze-green',
        rule: 'bg-druze-green',
        text: 'text-druze-green',
        tint: 'bg-[color-mix(in_srgb,var(--color-druze-green)_8%,white)]',
    },
    red: {
        edge: 'border-druze-red',
        rule: 'bg-druze-red',
        text: 'text-druze-red',
        tint: 'bg-[color-mix(in_srgb,var(--color-druze-red)_7%,white)]',
    },
    yellow: {
        edge: 'border-druze-yellow',
        rule: 'bg-druze-yellow',
        text: 'text-[color-mix(in_srgb,var(--color-druze-yellow)_75%,black)]',
        tint: 'bg-[color-mix(in_srgb,var(--color-druze-yellow)_12%,white)]',
    },
    white: {
        edge: 'border-hairline',
        rule: 'bg-hairline',
        text: 'text-ink',
        tint: 'bg-surface-alt',
    },
};

export function accentClasses(accent: Accent): AccentClasses {
    return MAP[accent ?? 'blue'] ?? MAP.blue;
}
