import { usePage } from '@inertiajs/react';
import { useCallback, useMemo } from 'react';

import type { Direction, LocaleCode, LocaleOption } from '@/types/i18n';

type Replacements = Record<string, string | number>;

export interface I18n {
    /**
     * Translate a key. Unknown keys return the key itself, which makes a
     * missing string obvious in the UI rather than rendering as blank.
     *
     *   t('nav.contact')
     *   t('footer.rights', { year: 2026 })   // ":year" placeholders
     */
    t: (key: string, replacements?: Replacements) => string;
    locale: LocaleCode;
    dir: Direction;
    isRtl: boolean;
    locales: LocaleOption[];
    /**
     * Home URL in the active locale. Always link here instead of the bare
     * `home` route, which is Arabic-only and drops the visitor's language.
     */
    homeUrl: string;
}

export function useI18n(): I18n {
    const { locale, dir, locales, translations, homeUrl } = usePage().props;

    const t = useCallback(
        (key: string, replacements?: Replacements) => {
            let value = translations[key] ?? key;

            if (replacements) {
                for (const [token, replacement] of Object.entries(replacements)) {
                    value = value.replace(`:${token}`, String(replacement));
                }
            }

            return value;
        },
        [translations],
    );

    return useMemo(
        () => ({ t, locale, dir, isRtl: dir === 'rtl', locales, homeUrl }),
        [t, locale, dir, locales, homeUrl],
    );
}
