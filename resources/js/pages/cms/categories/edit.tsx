import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Save, Trash2 } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPicker } from '@/components/cms/media-picker';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as categories from '@/routes/cms/categories';
import type { BreadcrumbItem } from '@/types';

interface CategoryData {
    id: number | null;
    slug: string;
    name: Record<string, string>;
    description: Record<string, string>;
    accent: string | null;
    sort: number;
    imageMediaId: number | null;
}

interface Props {
    category: CategoryData;
    mediaLibrary: LibraryImage[];
    locales: LocaleMeta[];
    accents: string[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'التصنيفات', href: '/cms/categories' },
    { title: 'تحرير', href: '#' },
];

export default function CategoryEdit({ category, mediaLibrary, locales, accents }: Props) {
    const isNew = category.id === null;
    const form = useForm({
        slug: category.slug,
        name: category.name ?? {},
        description: category.description ?? {},
        accent: category.accent ?? '',
        sort: category.sort ?? 0,
        imageMediaId: category.imageMediaId,
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();

        if (isNew) {
            form.post(categories.store().url);
        } else {
            form.put(categories.update(category.id as number).url, { preserveScroll: true });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isNew ? 'تصنيف جديد' : 'تحرير تصنيف'} />

            <form onSubmit={submit} className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={categories.index().url}>
                            <ArrowRight className="size-4" /> التصنيفات
                        </Link>
                    </Button>
                    <div className="flex items-center gap-2">
                        {!isNew && (
                            <Button
                                type="button"
                                variant="ghost"
                                className="text-destructive hover:text-destructive"
                                onClick={() => {
                                    if (confirm('حذف هذا التصنيف؟')) {
                                        router.delete(categories.destroy(category.id as number).url);
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
                    <TranslatableField label="الاسم" value={form.data.name} locales={locales} onChange={(v) => form.setData('name', v)} required />
                    {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}

                    <div className="space-y-1.5">
                        <Label htmlFor="slug">المسار (slug)</Label>
                        <Input id="slug" dir="ltr" value={form.data.slug} onChange={(e) => form.setData('slug', e.target.value)} />
                        {form.errors.slug && <p className="text-sm text-destructive">{form.errors.slug}</p>}
                    </div>

                    <TranslatableField label="الوصف" value={form.data.description} locales={locales} onChange={(v) => form.setData('description', v)} multiline />
                </section>

                <section className="grid gap-4 rounded-xl border border-border bg-card p-5 sm:grid-cols-2">
                    <div className="space-y-1.5">
                        <MediaPicker label="صورة التصنيف (تظهر في السلايدر)" value={form.data.imageMediaId} onChange={(id) => form.setData('imageMediaId', id)} library={mediaLibrary} />
                    </div>
                    <div className="space-y-4">
                        <div className="space-y-1.5">
                            <Label>اللون المميز</Label>
                            <select
                                value={form.data.accent}
                                onChange={(e) => form.setData('accent', e.target.value)}
                                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option value="">— بدون —</option>
                                {accents.map((a) => <option key={a} value={a}>{a}</option>)}
                            </select>
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="sort">الترتيب</Label>
                            <Input id="sort" type="number" min={0} dir="ltr" value={form.data.sort} onChange={(e) => form.setData('sort', Number(e.target.value))} />
                        </div>
                    </div>
                </section>
            </form>
        </AppLayout>
    );
}
