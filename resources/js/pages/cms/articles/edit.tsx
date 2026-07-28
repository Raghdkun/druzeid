import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Save, Trash2 } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPicker } from '@/components/cms/media-picker';
import { TranslatableField } from '@/components/cms/translatable-field';
import { TranslatableHtml } from '@/components/cms/translatable-html';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as articles from '@/routes/cms/articles';
import type { BreadcrumbItem } from '@/types';

interface ArticleData {
    id: number | null;
    slug: string;
    title: Record<string, string>;
    excerpt: Record<string, string>;
    body: Record<string, string>;
    categoryId: number | null;
    featuredMediaId: number | null;
    status: string;
    publishedAt: string | null;
    sourceName: string | null;
    sourceUrl: string | null;
    isFeatured: boolean;
}

interface Category {
    id: number;
    name: string;
}

interface Props {
    article: ArticleData;
    categories: Category[];
    mediaLibrary: LibraryImage[];
    locales: LocaleMeta[];
    statuses: string[];
}

const STATUS_LABEL: Record<string, string> = { draft: 'مسودة', published: 'منشور' };

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الأخبار', href: '/cms/articles' },
    { title: 'تحرير', href: '#' },
];

export default function ArticleEdit({ article, categories, mediaLibrary, locales, statuses }: Props) {
    const isNew = article.id === null;
    const form = useForm({
        slug: article.slug,
        title: article.title ?? {},
        excerpt: article.excerpt ?? {},
        body: article.body ?? {},
        categoryId: article.categoryId,
        featuredMediaId: article.featuredMediaId,
        status: article.status,
        publishedAt: article.publishedAt,
        sourceName: article.sourceName ?? '',
        sourceUrl: article.sourceUrl ?? '',
        isFeatured: article.isFeatured,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isNew) {
            form.post(articles.store().url);
        } else {
            form.put(articles.update(article.id as number).url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isNew ? 'خبر جديد' : 'تحرير خبر'} />

            <form onSubmit={submit} className="mx-auto max-w-4xl space-y-6 p-4 sm:p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={articles.index().url}>
                            <ArrowRight className="size-4" /> الأخبار
                        </Link>
                    </Button>
                    <div className="flex items-center gap-2">
                        {!isNew && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="text-destructive hover:text-destructive"
                                onClick={() => {
                                    if (confirm('حذف هذا الخبر؟')) {
                                        router.delete(articles.destroy(article.id as number).url);
                                    }
                                }}
                            >
                                <Trash2 className="size-4" /> حذف
                            </Button>
                        )}
                        <Button type="submit" disabled={form.processing}>
                            <Save className="size-4" /> حفظ
                        </Button>
                    </div>
                </div>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <TranslatableField label="العنوان" value={form.data.title} locales={locales} onChange={(v) => form.setData('title', v)} required />
                    {form.errors.title && <p className="text-sm text-destructive">{form.errors.title}</p>}
                    <div className="space-y-1.5">
                        <Label htmlFor="slug">المسار (slug)</Label>
                        <Input id="slug" dir="ltr" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                        {form.errors.slug && <p className="text-sm text-destructive">{form.errors.slug}</p>}
                    </div>
                    <TranslatableField label="مقتطف" value={form.data.excerpt} locales={locales} onChange={(v) => form.setData('excerpt', v)} multiline />
                </section>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <TranslatableHtml label="نص الخبر" value={form.data.body} locales={locales} onChange={(v) => form.setData('body', v)} />
                </section>

                <section className="grid gap-4 rounded-xl border border-border bg-card p-5 sm:grid-cols-2">
                    <MediaPicker label="الصورة البارزة" value={form.data.featuredMediaId} onChange={(id) => form.setData('featuredMediaId', id)} library={mediaLibrary} />

                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <Label>التصنيف</Label>
                            <select
                                value={form.data.categoryId ?? ''}
                                onChange={(e) => form.setData('categoryId', e.target.value ? Number(e.target.value) : null)}
                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">— بدون —</option>
                                {categories.map((c) => (
                                    <option key={c.id} value={c.id}>{c.name}</option>
                                ))}
                            </select>
                        </div>

                        <div className="grid grid-cols-2 gap-3">
                            <div className="space-y-1.5">
                                <Label>الحالة</Label>
                                <select
                                    value={form.data.status}
                                    onChange={(e) => form.setData('status', e.target.value)}
                                    className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    {statuses.map((s) => <option key={s} value={s}>{STATUS_LABEL[s] ?? s}</option>)}
                                </select>
                            </div>
                            <div className="space-y-1.5">
                                <Label htmlFor="publishedAt">تاريخ النشر</Label>
                                <Input
                                    id="publishedAt"
                                    type="datetime-local"
                                    dir="ltr"
                                    value={form.data.publishedAt ?? ''}
                                    onChange={(e) => form.setData('publishedAt', e.target.value || null)}
                                />
                            </div>
                        </div>

                        <label className="flex items-center gap-2 text-sm">
                            <input type="checkbox" className="size-4" checked={form.data.isFeatured} onChange={(e) => form.setData('isFeatured', e.target.checked)} />
                            خبر مميّز (يظهر في شريط الأخبار بالرئيسية)
                        </label>
                    </div>
                </section>

                <section className="grid gap-4 rounded-xl border border-border bg-card p-5 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <Label htmlFor="sourceName">اسم المصدر</Label>
                        <Input id="sourceName" value={form.data.sourceName} onChange={(e) => form.setData('sourceName', e.target.value)} />
                    </div>
                    <div className="space-y-1.5">
                        <Label htmlFor="sourceUrl">رابط المصدر</Label>
                        <Input id="sourceUrl" dir="ltr" value={form.data.sourceUrl} onChange={(e) => form.setData('sourceUrl', e.target.value)} />
                        {form.errors.sourceUrl && <p className="text-sm text-destructive">{form.errors.sourceUrl}</p>}
                    </div>
                </section>
            </form>
        </AppLayout>
    );
}
