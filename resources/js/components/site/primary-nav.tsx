import { usePage } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';

import { NavLink } from '@/components/site/nav-link';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types/site';

/**
 * Desktop primary navigation — the full-width dark bar. Top-level items are
 * equal presence; a parent with children opens a dropdown on hover and on
 * click/focus (keyboard-navigable, closes on Escape and on outside click).
 */
export function PrimaryNav({ items }: { items: NavItem[] }) {
    const { t } = useI18n();

    return (
        <nav aria-label={t('nav.primary_label')} className="hidden lg:block">
            <ul className="flex items-stretch">
                {items.map((item) => (
                    <NavTopItem key={item.id} item={item} />
                ))}
            </ul>
        </nav>
    );
}

function NavTopItem({ item }: { item: NavItem }) {
    const { url } = usePage();
    const [open, setOpen] = useState(false);
    const ref = useRef<HTMLLIElement>(null);
    const hasChildren = item.children.length > 0;

    const active =
        (item.href && url.startsWith(item.href)) ||
        item.children.some((child) => child.href && url.startsWith(child.href));

    useEffect(() => {
        if (!open) {
            return;
        }

        const onClick = (event: MouseEvent) => {
            if (ref.current && !ref.current.contains(event.target as Node)) {
                setOpen(false);
            }
        };
        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('mousedown', onClick);
        document.addEventListener('keydown', onKey);

        return () => {
            document.removeEventListener('mousedown', onClick);
            document.removeEventListener('keydown', onKey);
        };
    }, [open]);

    const itemClasses = cn(
        'flex h-full items-center gap-1 px-5 py-4 text-[15px] font-semibold transition-colors',
        // The menu turns the organisation's blue on hover and when its dropdown
        // is open — the "international blue" highlight the client asked for.
        active || open
            ? 'bg-druze-blue text-white'
            : 'text-white/90 hover:bg-druze-blue hover:text-white',
    );

    if (!hasChildren) {
        return (
            <li>
                <NavLink item={item} className={itemClasses} />
            </li>
        );
    }

    return (
        <li
            ref={ref}
            className="relative"
            onMouseEnter={() => setOpen(true)}
            onMouseLeave={() => setOpen(false)}
        >
            <button
                type="button"
                aria-expanded={open}
                aria-haspopup="true"
                className={itemClasses}
                onClick={() => setOpen((value) => !value)}
            >
                {item.label}
                <Chevron open={open} />
            </button>

            {open && (
                <ul className="absolute top-full z-40 min-w-60 border-t-2 border-druze-blue bg-white py-2 shadow-lg start-0">
                    {item.children.map((child) => (
                        <li key={child.id}>
                            <NavLink
                                item={child}
                                className="block px-5 py-2.5 text-sm text-ink-soft transition-colors hover:bg-surface-alt hover:text-druze-blue"
                                onClick={() => setOpen(false)}
                            />
                        </li>
                    ))}
                </ul>
            )}
        </li>
    );
}

function Chevron({ open }: { open: boolean }) {
    return (
        <svg
            width="14"
            height="14"
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden
            className={cn('transition-transform', open && 'rotate-180')}
        >
            <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" />
        </svg>
    );
}
