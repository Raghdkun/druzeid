import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type LocaleMap = Record<string, string>;

/**
 * One translatable field with a locale tab strip. Editing switches which
 * locale's value is bound; each tab shows a filled/empty dot so an editor sees
 * which languages still need attention. The input flips `dir` to match the
 * locale being edited, so Arabic types RTL and Latin LTR in the same control.
 */
export function TranslatableField({
    label,
    value,
    locales,
    onChange,
    multiline = false,
    required,
    hint,
}: {
    label: string;
    value: LocaleMap;
    locales: LocaleMeta[];
    onChange: (next: LocaleMap) => void;
    multiline?: boolean;
    required?: boolean;
    hint?: string;
}) {
    const [active, setActive] = useState(locales.find((l) => l.default)?.code ?? locales[0].code);
    const activeLocale = locales.find((l) => l.code === active) ?? locales[0];

    const setValue = (text: string) => {
        const next = { ...value };

        if (text === '') {
            delete next[active];
        } else {
            next[active] = text;
        }

        onChange(next);
    };

    return (
        <div className="space-y-1.5">
            <div className="flex items-center justify-between gap-2">
                <Label>
                    {label}
                    {required && <span className="ms-1 text-destructive">*</span>}
                </Label>

                <div className="flex items-center gap-0.5 rounded-md bg-muted p-0.5">
                    {locales.map((locale) => {
                        const filled = Boolean(value[locale.code]);

                        return (
                            <button
                                key={locale.code}
                                type="button"
                                onClick={() => setActive(locale.code)}
                                className={cn(
                                    'flex items-center gap-1.5 rounded px-2 py-1 text-xs font-medium transition-colors',
                                    active === locale.code
                                        ? 'bg-background text-foreground shadow-xs'
                                        : 'text-muted-foreground hover:text-foreground',
                                )}
                            >
                                <span
                                    aria-hidden
                                    className={cn(
                                        'size-1.5 rounded-full',
                                        filled ? 'bg-druze-green' : 'bg-muted-foreground/40',
                                    )}
                                />
                                {locale.code}
                                {locale.default && <span className="text-[9px] opacity-60">★</span>}
                            </button>
                        );
                    })}
                </div>
            </div>

            {multiline ? (
                <textarea
                    dir={activeLocale.dir}
                    rows={4}
                    value={value[active] ?? ''}
                    onChange={(e) => setValue(e.target.value)}
                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                />
            ) : (
                <Input
                    dir={activeLocale.dir}
                    value={value[active] ?? ''}
                    onChange={(e) => setValue(e.target.value)}
                />
            )}

            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </div>
    );
}
