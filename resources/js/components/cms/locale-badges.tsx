import { cn } from '@/lib/utils';

export interface LocaleMeta {
    code: string;
    name: string;
    dir: 'rtl' | 'ltr';
    default: boolean;
}

/**
 * Per-locale completeness pills — the "translated / missing" indicator that
 * appears on page rows and cards. Green when a locale is fully populated,
 * amber when partial, muted when empty. Makes the fr/nl gaps visible at a
 * glance rather than hidden behind a fallback.
 */
export function CompletenessBadges({
    completeness,
    locales,
    className,
}: {
    completeness: Record<string, number>;
    locales: LocaleMeta[];
    className?: string;
}) {
    return (
        <div className={cn('flex items-center gap-1', className)}>
            {locales.map((locale) => {
                const percent = completeness[locale.code] ?? 0;

                return (
                    <span
                        key={locale.code}
                        title={`${locale.name}: ${percent}%`}
                        className={cn(
                            'inline-flex h-5 min-w-8 items-center justify-center rounded px-1 text-[11px] font-semibold uppercase',
                            percent === 100 &&
                                'bg-[color-mix(in_srgb,var(--color-druze-green)_15%,transparent)] text-druze-green',
                            percent > 0 &&
                                percent < 100 &&
                                'bg-[color-mix(in_srgb,var(--color-druze-yellow)_22%,transparent)] text-[color-mix(in_srgb,var(--color-druze-yellow)_70%,black)]',
                            percent === 0 && 'bg-muted text-muted-foreground',
                        )}
                    >
                        {locale.code}
                    </span>
                );
            })}
        </div>
    );
}
