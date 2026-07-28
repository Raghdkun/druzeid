import { Head, Link, router } from '@inertiajs/react';
import { Pencil, Plus, Star, Trash2 } from 'lucide-react';

import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import * as articles from '@/routes/cms/articles';
import type { BreadcrumbItem } from '@/types';

interface ArticleRow {
    id: number;
    title: string;
    status: string;
    isFeatured: boolean;
    category: string | null;
    publishedAt: string | null;
}

interface Paginated {
    data: ArticleRow[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الأخبار', href: '/cms/articles' },
];

export default function ArticlesIndex({ articles: rows }: { articles: Paginated }) {
    const remove = (article: ArticleRow) => {
        if (confirm(`حذف الخبر «${article.title}»؟`)) {
            router.delete(articles.destroy(article.id).url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="الأخبار" />

            <div className="space-y-4 p-4 sm:p-6">
                <div className="flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold">الأخبار</h1>
                    <Button asChild>
                        <Link href={articles.create().url}>
                            <Plus className="size-4" /> خبر جديد
                        </Link>
                    </Button>
                </div>

                <div className="overflow-x-auto rounded-xl border border-border bg-card">
                    <table className="w-full min-w-[36rem] text-sm">
                        <thead className="border-b border-border bg-muted/40">
                            <tr>
                                <Th>العنوان</Th>
                                <Th>التصنيف</Th>
                                <Th>الحالة</Th>
                                <Th>النشر</Th>
                                <Th className="text-end">إجراءات</Th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-border">
                            {rows.data.map((article) => (
                                <tr key={article.id} className="hover:bg-muted/30">
                                    <td className="px-4 py-3">
                                        <Link
                                            href={articles.edit(article.id).url}
                                            className="inline-flex items-center gap-1.5 font-medium hover:text-druze-blue"
                                        >
                                            {article.isFeatured && (
                                                <Star className="size-3.5 fill-druze-yellow text-druze-yellow" aria-label="مميّز" />
                                            )}
                                            {article.title}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">{article.category ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <StatusPill status={article.status} />
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground" dir="ltr">
                                        {article.publishedAt ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <Button variant="ghost" size="icon" asChild>
                                                <Link href={articles.edit(article.id).url} aria-label="تحرير">
                                                    <Pencil className="size-4" />
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => remove(article)}
                                                aria-label="حذف"
                                                className="text-destructive hover:text-destructive"
                                            >
                                                <Trash2 className="size-4" />
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                            {rows.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-16 text-center text-muted-foreground">
                                        لا توجد أخبار بعد.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {rows.links.length > 3 && (
                    <nav className="flex flex-wrap items-center justify-center gap-1">
                        {rows.links.map((link, index) => (
                            <Link
                                key={index}
                                href={link.url ?? '#'}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                                className={cn(
                                    'rounded px-3 py-1.5 text-sm',
                                    link.active ? 'bg-druze-blue text-white' : 'text-muted-foreground hover:bg-muted',
                                    !link.url && 'pointer-events-none opacity-40',
                                )}
                            />
                        ))}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}

function Th({ children, className }: { children: React.ReactNode; className?: string }) {
    return (
        <th className={cn('px-4 py-2.5 text-start text-xs font-semibold text-muted-foreground', className)}>
            {children}
        </th>
    );
}

function StatusPill({ status }: { status: string }) {
    const published = status === 'published';

    return (
        <span
            className={
                published
                    ? 'rounded-full bg-[color-mix(in_srgb,var(--color-druze-green)_15%,transparent)] px-2 py-0.5 text-xs font-medium text-druze-green'
                    : 'rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground'
            }
        >
            {published ? 'منشور' : 'مسودة'}
        </span>
    );
}
