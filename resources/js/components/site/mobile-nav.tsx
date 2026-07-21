import { Link } from '@inertiajs/react';
import { useState } from 'react';

import { LanguageSwitcher } from '@/components/site/language-switcher';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Sheet, SheetContent, SheetTitle, SheetTrigger } from '@/components/ui/sheet';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types/site';

/**
 * Mobile navigation — an off-canvas drawer. In RTL it slides in from the
 * inline-start edge (the physical right), which the kit's Sheet handles via
 * the `side` prop; Radix Dialog underneath gives a real focus trap, Escape to
 * close, and scroll lock, all of which un.org's push-down accordion lacked.
 */
export function MobileNav({ items }: { items: NavItem[] }) {
    const { t, isRtl } = useI18n();
    const [open, setOpen] = useState(false);

    return (
        <Sheet open={open} onOpenChange={setOpen}>
            <SheetTrigger
                className="inline-flex size-11 items-center justify-center rounded-md text-ink hover:bg-surface-alt lg:hidden"
                aria-label={t('nav.open_menu')}
            >
                <BurgerIcon />
            </SheetTrigger>

            <SheetContent
                side={isRtl ? 'right' : 'left'}
                className="w-[86%] max-w-sm overflow-y-auto p-0"
            >
                <SheetTitle className="sr-only">{t('nav.primary_label')}</SheetTitle>

                <nav aria-label={t('nav.primary_label')} className="flex flex-col py-4">
                    {items.map((item) =>
                        item.children.length > 0 ? (
                            <MobileGroup key={item.id} item={item} onNavigate={() => setOpen(false)} />
                        ) : (
                            <Link
                                key={item.id}
                                href={item.href ?? '#'}
                                onClick={() => setOpen(false)}
                                className="border-b border-hairline px-6 py-4 font-display text-lg font-semibold text-ink"
                            >
                                {item.label}
                            </Link>
                        ),
                    )}
                </nav>

                <div className="border-t border-hairline px-6 py-5">
                    <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-eyebrow">
                        {t('lang.label')}
                    </p>
                    <LanguageSwitcher />
                </div>
            </SheetContent>
        </Sheet>
    );
}

function MobileGroup({ item, onNavigate }: { item: NavItem; onNavigate: () => void }) {
    const [open, setOpen] = useState(false);

    return (
        <Collapsible open={open} onOpenChange={setOpen} className="border-b border-hairline">
            <CollapsibleTrigger className="group flex w-full items-center justify-between px-6 py-4 text-start font-display text-lg font-semibold text-ink">
                {item.label}
                <svg
                    width="18"
                    height="18"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className={cn('transition-transform', open && 'rotate-180')}
                >
                    <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </CollapsibleTrigger>
            <CollapsibleContent className="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                <ul className="bg-surface-alt pb-2">
                    {item.children.map((child) => (
                        <li key={child.id}>
                            <Link
                                href={child.href ?? '#'}
                                onClick={onNavigate}
                                className="block px-8 py-3 text-ink-soft"
                            >
                                {child.label}
                            </Link>
                        </li>
                    ))}
                </ul>
            </CollapsibleContent>
        </Collapsible>
    );
}

function BurgerIcon() {
    return (
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden>
            <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
        </svg>
    );
}
