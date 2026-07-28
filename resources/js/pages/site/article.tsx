import { Head } from '@inertiajs/react';

import { Picture } from '@/components/site/picture';
import { ProseHtml } from '@/components/site/prose-html';
import { useI18n } from '@/hooks/use-i18n';
import { SiteLayout } from '@/layouts/site-layout';
import type { ImageProps, PageMeta, SiteMenus, SiteSettings } from '@/types/site';

interface ArticleData {
    id: number;
    slug: string;
    title: string;
    excerpt: string | null;
    body: string;
    image: ImageProps | null;
    category: string | null;
    publishedAt: string | null;
    publishedLabel: string | null;
    sourceName: string | null;
    sourceUrl: string | null;
}

interface Props {
    article: ArticleData;
    meta: PageMeta;
    menus: SiteMenus;
    settings: SiteSettings;
    [key: string]: unknown;
}

/** أخبار الدروز — a single news article. */
export default function Article({ article, meta, menus, settings }: Props) {
    const { t } = useI18n();

    return (
        <SiteLayout menus={menus} settings={settings}>
            <Head title={meta.title ?? article.title} />

            {/* Banner: the featured image with the headline over a dark scrim,
                or a plain title block when there is no image. */}
            {article.image ? (
                <header className="relative isolate overflow-hidden bg-bar-dark">
                    <Picture
                        image={{ ...article.image, alt: '' }}
                        priority
                        sizes="100vw"
                        className="absolute inset-0 -z-10 h-full w-full object-cover opacity-45"
                    />
                    <div className="mx-auto max-w-3xl px-4 py-24 sm:px-6">
                        <ArticleMeta category={article.category} date={article.publishedLabel} tone="light" />
                        <h1 className="mt-3 font-display text-3xl font-bold text-white drop-shadow-sm sm:text-4xl lg:text-5xl">
                            {article.title}
                        </h1>
                    </div>
                </header>
            ) : (
                <header className="border-b border-hairline bg-white">
                    <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
                        <ArticleMeta category={article.category} date={article.publishedLabel} tone="dark" />
                        <h1 className="mt-3 font-display text-3xl font-bold text-druze-blue sm:text-4xl">
                            {article.title}
                        </h1>
                    </div>
                </header>
            )}

            <div className="mx-auto max-w-3xl px-4 py-14 sm:px-6">
                {/* Tinted reading surface, a few shades off the page ground, so
                    the written article reads as its own panel. */}
                <div className="rounded-2xl bg-druze-blue-tint/50 p-6 sm:p-10">
                    <ProseHtml html={article.body} />
                </div>

                {article.sourceName && (
                    <p className="mt-8 text-sm text-ink-muted">
                        {t('news.source')}:{' '}
                        {article.sourceUrl ? (
                            <a
                                href={article.sourceUrl}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="font-medium text-druze-blue underline underline-offset-2"
                            >
                                {article.sourceName}
                            </a>
                        ) : (
                            <span className="font-medium">{article.sourceName}</span>
                        )}
                    </p>
                )}
            </div>
        </SiteLayout>
    );
}

function ArticleMeta({
    category,
    date,
    tone,
}: {
    category: string | null;
    date: string | null;
    tone: 'light' | 'dark';
}) {
    if (!category && !date) {
        return null;
    }

    return (
        <div
            className={
                tone === 'light'
                    ? 'flex flex-wrap items-center gap-x-3 text-sm text-white/85'
                    : 'flex flex-wrap items-center gap-x-3 text-sm text-ink-muted'
            }
        >
            {category && <span className="font-semibold">{category}</span>}
            {category && date && <span aria-hidden>·</span>}
            {date && <time>{date}</time>}
        </div>
    );
}
