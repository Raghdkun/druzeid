import { Link } from '@inertiajs/react';

import type { NavItem } from '@/types/site';

/** External schemes that must use a plain <a> (Inertia's Link is same-app only). */
const isExternal = (href: string) => /^(https?:|mailto:|tel:)/i.test(href);

/**
 * Renders a menu item's link with the right element: a plain `<a>` for external
 * URLs or when the item opens in a new tab (Inertia's SPA `<Link>` can neither
 * visit off-site nor open a new tab), and an Inertia `<Link>` for internal
 * navigation.
 */
export function NavLink({
    item,
    className,
    onClick,
    children,
}: {
    item: NavItem;
    className?: string;
    onClick?: () => void;
    children?: React.ReactNode;
}) {
    const href = item.href ?? '#';
    const content = children ?? item.label;

    if (item.newTab || isExternal(href)) {
        return (
            <a
                href={href}
                target={item.newTab ? '_blank' : undefined}
                rel={item.newTab ? 'noreferrer noopener' : undefined}
                className={className}
                onClick={onClick}
            >
                {content}
            </a>
        );
    }

    return (
        <Link href={href} className={className} onClick={onClick}>
            {content}
        </Link>
    );
}
