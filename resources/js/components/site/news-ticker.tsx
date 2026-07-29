import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft, Newspaper } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useI18n } from '@/hooks/use-i18n';

interface Headline {
    id: number;
    title: string;
    href: string;
}

/**
 * A slim news ticker under the navbar: a labelled band that rotates the latest
 * headlines as text lines (no images), each linking to its article. Auto-
 * advances, pauses on hover, and holds still under prefers-reduced-motion.
 * Reads the headlines from the shared chrome props, so it appears site-wide.
 */
export function NewsTicker() {
    const { props } = usePage<{ latestNews?: Headline[]; newsIndexUrl?: string }>();
    const { t } = useI18n();
    const items = props.latestNews ?? [];
    const [active, setActive] = useState(0);
    const [paused, setPaused] = useState(false);

    useEffect(() => {
        if (items.length < 2 || paused) {
            return;
        }

        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }

        const timer = setInterval(() => setActive((c) => (c + 1) % items.length), 4500);

        return () => clearInterval(timer);
    }, [items.length, paused]);

    if (items.length === 0) {
        return null;
    }

    const current = items[active % items.length];

    return (
        <div className="border-b border-hairline bg-druze-blue-tint/60">
            <div
                className="mx-auto flex h-11 max-w-6xl items-center gap-3 px-4 sm:px-6 lg:px-8"
                onMouseEnter={() => setPaused(true)}
                onMouseLeave={() => setPaused(false)}
            >
                <span className="flex shrink-0 items-center gap-1.5 rounded-full bg-druze-blue px-3 py-1 text-xs font-semibold text-white">
                    <Newspaper className="size-3.5" aria-hidden />
                    {t('news.title')}
                </span>

                <div className="relative min-w-0 flex-1 overflow-hidden" aria-live="polite" aria-atomic="true">
                    <Link
                        key={current.id}
                        href={current.href}
                        className="ticker-item block truncate text-sm font-medium text-ink hover:text-druze-blue"
                    >
                        {current.title}
                    </Link>
                </div>

                <Link
                    href={props.newsIndexUrl ?? '/news'}
                    className="flex shrink-0 items-center gap-0.5 text-xs font-semibold text-druze-blue hover:underline"
                >
                    {t('news.all')}
                    <ChevronLeft className="size-3.5 rtl:hidden" aria-hidden />
                    <ChevronLeft className="hidden size-3.5 rotate-180 rtl:block" aria-hidden />
                </Link>
            </div>
        </div>
    );
}
