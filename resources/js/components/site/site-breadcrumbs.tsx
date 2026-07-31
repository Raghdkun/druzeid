import { Link, usePage } from '@inertiajs/react';
import { ChevronLeft } from 'lucide-react';
import { Fragment } from 'react';

import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { useI18n } from '@/hooks/use-i18n';
import type { NavItem, PageData, SiteMenus } from '@/types/site';

interface Crumb {
    title: string;
    href?: string;
}

interface BreadcrumbProps {
    page?: PageData;
    article?: { title: string };
    menus?: SiteMenus;
    newsIndexUrl?: string;
    [key: string]: unknown;
}

/** Path-only comparison so a menu href matches the current URL regardless of query/trailing slash. */
function samePath(a: string, b: string): boolean {
    const norm = (s: string) => s.split(/[?#]/)[0].replace(/\/+$/, '') || '/';

    return norm(a) === norm(b);
}

/** Depth-first walk of the menu tree collecting the ancestor chain to `path`. */
function findTrail(nodes: NavItem[], path: string, ancestors: NavItem[] = []): NavItem[] | null {
    for (const node of nodes) {
        const chain = [...ancestors, node];

        if (node.href && samePath(node.href, path)) {
            return chain;
        }

        if (node.children.length > 0) {
            const found = findTrail(node.children, path, chain);

            if (found) {
                return found;
            }
        }
    }

    return null;
}

/**
 * Public breadcrumb trail. Built entirely client-side from the shared primary
 * menu tree (for CMS pages) or the fixed news hierarchy (for articles / the
 * news index). Renders nothing on the home page. RTL-aware separator and a
 * localized label; the last crumb is the non-link current page.
 */
export function SiteBreadcrumbs() {
    const { t, homeUrl } = useI18n();
    const { component, url, props } = usePage<BreadcrumbProps>();

    const crumbs = buildCrumbs(component, url, props, {
        home: t('nav.home'),
        news: t('news.title'),
    }, homeUrl);

    if (crumbs.length < 2) {
        return null;
    }

    return (
        <div className="border-b border-hairline bg-surface-alt/60">
            <div className="mx-auto max-w-6xl px-4 py-3 sm:px-6 lg:px-8">
                <Breadcrumb aria-label={t('a11y.breadcrumb')}>
                    <BreadcrumbList>
                        {crumbs.map((crumb, i) => {
                            const last = i === crumbs.length - 1;

                            return (
                                <Fragment key={i}>
                                    <BreadcrumbItem>
                                        {last || !crumb.href ? (
                                            <BreadcrumbPage className="text-ink">{crumb.title}</BreadcrumbPage>
                                        ) : (
                                            <BreadcrumbLink asChild className="text-ink-muted hover:text-druze-blue">
                                                <Link href={crumb.href}>{crumb.title}</Link>
                                            </BreadcrumbLink>
                                        )}
                                    </BreadcrumbItem>
                                    {!last && (
                                        <BreadcrumbSeparator className="text-ink-muted">
                                            <ChevronLeft className="size-3.5 rtl:hidden" aria-hidden />
                                            <ChevronLeft className="hidden size-3.5 rotate-180 rtl:block" aria-hidden />
                                        </BreadcrumbSeparator>
                                    )}
                                </Fragment>
                            );
                        })}
                    </BreadcrumbList>
                </Breadcrumb>
            </div>
        </div>
    );
}

function buildCrumbs(
    component: string,
    url: string,
    props: BreadcrumbProps,
    labels: { home: string; news: string },
    homeUrl: string,
): Crumb[] {
    const home: Crumb = { title: labels.home, href: homeUrl };

    if (component === 'site/article' && props.article) {
        return [home, { title: labels.news, href: props.newsIndexUrl ?? '/news' }, { title: props.article.title }];
    }

    if (component === 'site/news-index') {
        return [home, { title: labels.news }];
    }

    if (component === 'site/page' && props.page) {
        if (props.page.template === 'home') {
            return [];
        }

        const trail = props.menus ? findTrail(props.menus.primary, url) : null;

        if (trail && trail.length > 0) {
            const middle = trail.slice(0, -1).map((node) => ({ title: node.label, href: node.href ?? undefined }));

            return [home, ...middle, { title: props.page.title }];
        }

        return [home, { title: props.page.title }];
    }

    return [];
}
