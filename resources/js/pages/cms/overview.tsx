import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, FileText, Images, Languages, Newspaper } from 'lucide-react';

import AppLayout from '@/layouts/app-layout';
import * as articles from '@/routes/cms/articles';
import * as media from '@/routes/cms/media';
import * as pages from '@/routes/cms/pages';
import type { BreadcrumbItem } from '@/types';

interface Overview {
    stats: {
        pages: number;
        published: number;
        drafts: number;
        media: number;
        altReviewQueue: number;
        articles: number;
        articlesPublished: number;
    };
    translationCoverage: Array<{ locale: string; name: string; percent: number }>;
    recentPages: Array<{
        id: number;
        slug: string;
        title: string;
        status: string;
        updatedAt: string | null;
        completeness: Record<string, number>;
    }>;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'لوحة التحكم', href: '/cms' }];

export default function CmsOverview({ stats, translationCoverage, recentPages }: Overview) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="لوحة التحكم" />

            <div className="space-y-6 p-4 sm:p-6">
                {/* Stat tiles */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatTile
                        icon={FileText}
                        label="الصفحات"
                        value={stats.pages}
                        sub={`${stats.published} منشورة · ${stats.drafts} مسودة`}
                        href={pages.index().url}
                    />
                    <StatTile
                        icon={Newspaper}
                        label="الأخبار"
                        value={stats.articles}
                        sub={`${stats.articlesPublished} منشور`}
                        href={articles.index().url}
                    />
                    <StatTile icon={Images} label="الوسائط" value={stats.media} href={media.index().url} />
                    <StatTile
                        icon={AlertTriangle}
                        label="بانتظار مراجعة النص البديل"
                        value={stats.altReviewQueue}
                        tone={stats.altReviewQueue > 0 ? 'warn' : 'default'}
                        href={`${media.index().url}?filter=review`}
                    />
                    <StatTile
                        icon={Languages}
                        label="اللغات"
                        value={translationCoverage.length}
                        sub="عربي · إنجليزي · فرنسي · هولندي"
                    />
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Translation coverage */}
                    <section className="rounded-xl border border-border bg-card p-5">
                        <h2 className="mb-4 font-display text-lg font-bold">تغطية الترجمة</h2>
                        <div className="space-y-3">
                            {translationCoverage.map((row) => (
                                <div key={row.locale}>
                                    <div className="mb-1 flex items-center justify-between text-sm">
                                        <span className="font-medium">{row.name}</span>
                                        <span className="text-muted-foreground">{row.percent}%</span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-druze-blue transition-all"
                                            style={{ width: `${row.percent}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                        <p className="mt-4 text-xs text-muted-foreground">
                            النسبة تقيس الصفحات التي تحتوي على عنوان مترجم في كل لغة.
                        </p>
                    </section>

                    {/* Recent pages */}
                    <section className="rounded-xl border border-border bg-card p-5">
                        <h2 className="mb-4 font-display text-lg font-bold">أحدث الصفحات</h2>
                        <ul className="divide-y divide-border">
                            {recentPages.map((page) => (
                                <li key={page.id}>
                                    <Link
                                        href={pages.edit(page.id).url}
                                        className="flex items-center justify-between gap-3 py-2.5 hover:text-druze-blue"
                                    >
                                        <span className="truncate font-medium">{page.title}</span>
                                        <span
                                            className={
                                                page.status === 'published'
                                                    ? 'shrink-0 rounded-full bg-[color-mix(in_srgb,var(--color-druze-green)_15%,transparent)] px-2 py-0.5 text-xs text-druze-green'
                                                    : 'shrink-0 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground'
                                            }
                                        >
                                            {page.status === 'published' ? 'منشورة' : 'مسودة'}
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}

function StatTile({
    icon: Icon,
    label,
    value,
    sub,
    href,
    tone = 'default',
}: {
    icon: typeof FileText;
    label: string;
    value: number;
    sub?: string;
    href?: string;
    tone?: 'default' | 'warn';
}) {
    const body = (
        <div className="flex items-start justify-between rounded-xl border border-border bg-card p-5 transition-colors hover:border-druze-blue/40">
            <div>
                <p className="text-sm text-muted-foreground">{label}</p>
                <p className="mt-1 font-display text-3xl font-bold">{value}</p>
                {sub && <p className="mt-1 text-xs text-muted-foreground">{sub}</p>}
            </div>
            <span
                className={
                    tone === 'warn' && value > 0
                        ? 'rounded-lg bg-[color-mix(in_srgb,var(--color-druze-yellow)_20%,transparent)] p-2 text-[color-mix(in_srgb,var(--color-druze-yellow)_70%,black)]'
                        : 'rounded-lg bg-muted p-2 text-muted-foreground'
                }
            >
                <Icon className="size-5" />
            </span>
        </div>
    );

    return href ? <Link href={href}>{body}</Link> : body;
}
