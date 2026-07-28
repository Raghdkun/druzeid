import { Head, Link } from '@inertiajs/react';

import { PageHeader } from '@/components/site/page-header';
import { Picture } from '@/components/site/picture';
import { SectionShell } from '@/components/site/section-shell';
import { useI18n } from '@/hooks/use-i18n';
import { SiteLayout } from '@/layouts/site-layout';
import { cn } from '@/lib/utils';
import type { NewsCard, PageMeta, SiteMenus, SiteSettings } from '@/types/site';

interface Paginated {
    data: NewsCard[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    articles: Paginated;
    meta: PageMeta;
    menus: SiteMenus;
    settings: SiteSettings;
    [key: string]: unknown;
}

/** أخبار الدروز — the public news index: a grid of article cards. */
export default function NewsIndex({ articles, meta, menus, settings }: Props) {
    const { t } = useI18n();

    return (
        <SiteLayout menus={menus} settings={settings}>
            <Head title={meta.title ?? t('news.title')} />

            <PageHeader title={t('news.title')} excerpt={null} image={null} accent="blue" />

            <SectionShell>
                {articles.data.length === 0 ? (
                    <p className="py-16 text-center text-ink-muted">{t('news.empty')}</p>
                ) : (
                    <div className="grid gap-x-6 gap-y-10 sm:grid-cols-2 lg:grid-cols-3">
                        {articles.data.map((article) => (
                            <ArticleCard key={article.id} article={article} readMore={t('news.read_article')} />
                        ))}
                    </div>
                )}

                <Pagination links={articles.links} />
            </SectionShell>
        </SiteLayout>
    );
}

function ArticleCard({ article, readMore }: { article: NewsCard; readMore: string }) {
    return (
        <Link href={article.href} className="group block">
            <article className="flex h-full flex-col">
                <div className="relative aspect-[3/2] overflow-hidden rounded-xl bg-surface-alt">
                    <Picture
                        image={article.image}
                        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                    />
                </div>
                {article.category && (
                    <span className="mt-4 text-xs font-semibold tracking-wide text-druze-blue">
                        {article.category}
                    </span>
                )}
                <h2 className="mt-1 font-display text-lg font-bold text-ink transition-colors group-hover:text-druze-blue sm:text-xl">
                    {article.title}
                </h2>
                {article.excerpt && (
                    <p className="mt-2 line-clamp-3 text-sm text-ink-muted">{article.excerpt}</p>
                )}
                <span className="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-druze-blue">
                    {readMore}
                </span>
            </article>
        </Link>
    );
}

function Pagination({ links }: { links: Paginated['links'] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <nav className="mt-12 flex flex-wrap items-center justify-center gap-1">
            {links.map((link, index) => (
                <Link
                    key={index}
                    href={link.url ?? '#'}
                    dangerouslySetInnerHTML={{ __html: link.label }}
                    className={cn(
                        'rounded-full px-3.5 py-1.5 text-sm',
                        link.active ? 'bg-druze-blue text-white' : 'text-ink-muted hover:bg-surface-alt',
                        !link.url && 'pointer-events-none opacity-40',
                    )}
                />
            ))}
        </nav>
    );
}
