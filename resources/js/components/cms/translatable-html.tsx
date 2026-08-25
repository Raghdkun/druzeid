import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { RichTextEditor } from '@/components/cms/rich-text-editor';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

type LocaleMap = Record<string, string>;

/**
 * A rich-text field with a per-locale tab strip — the HTML counterpart of
 * TranslatableField. Each locale has its own editor content; the dot on each
 * tab shows whether that language has been written yet, making translation
 * gaps visible in place. The editor direction follows the active locale.
 */
export function TranslatableHtml({
    label,
    value,
    locales,
    onChange,
    hint,
    library,
}: {
    label: string;
    value: LocaleMap;
    locales: LocaleMeta[];
    onChange: (next: LocaleMap) => void;
    hint?: string;
    library?: LibraryImage[];
}) {
    const [active, setActive] = useState(locales.find((l) => l.default)?.code ?? locales[0].code);
    const activeLocale = locales.find((l) => l.code === active) ?? locales[0];

    const setHtml = (html: string) => {
        const next = { ...value };

        // Treat an empty editor ("<p></p>" or blank) as no content.
        if (html === '' || html === '<p></p>') {
            delete next[active];
        } else {
            next[active] = html;
        }

        onChange(next);
    };

    const hasContent = (code: string) => {
        const html = value[code];

        return Boolean(html && html !== '<p></p>');
    };

    return (
        <div className="space-y-1.5">
            <div className="flex flex-wrap items-center justify-between gap-2">
                {label ? <Label>{label}</Label> : <span />}

                <div className="flex flex-wrap items-center gap-0.5 rounded-md bg-muted p-0.5">
                    {locales.map((locale) => (
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
                                    hasContent(locale.code) ? 'bg-druze-green' : 'bg-muted-foreground/40',
                                )}
                            />
                            {locale.code}
                            {locale.default && <span className="text-[9px] opacity-60">★</span>}
                        </button>
                    ))}
                </div>
            </div>

            {/* Remount per locale so the editor initialises with that locale's
                content and direction rather than syncing mid-life. */}
            <RichTextEditor
                key={active}
                value={value[active] ?? ''}
                dir={activeLocale.dir}
                onChange={setHtml}
                library={library}
            />

            {hint && <p className="text-xs text-muted-foreground">{hint}</p>}
        </div>
    );
}
