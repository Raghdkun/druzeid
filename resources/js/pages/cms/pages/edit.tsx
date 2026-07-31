import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowRight, Plus, Save } from 'lucide-react';
import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPicker } from '@/components/cms/media-picker';
import type { EditorSection } from '@/components/cms/section-editor';
import { SECTION_LABELS, SectionEditor } from '@/components/cms/section-editor';
import type { SectionReferences } from '@/components/cms/section-forms';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as pages from '@/routes/cms/pages';
import type { BreadcrumbItem } from '@/types';

interface PageDetail {
    id: number;
    slug: string;
    template: string;
    accent: string | null;
    status: string;
    heroMediaId: number | null;
    sort: number;
    title: Record<string, string>;
    excerpt: Record<string, string>;
    seoTitle: Record<string, string>;
    seoDescription: Record<string, string>;
    sections: EditorSection[];
}

interface Props {
    page: PageDetail | null;
    sectionTypes: string[];
    references: SectionReferences;
    templates: string[];
    accents: string[];
    locales: LocaleMeta[];
    mediaLibrary: LibraryImage[];
}

// Flat/map fields only — these serialize cleanly through Inertia's useForm.
// The dynamic `sections` array is held in local state and merged at submit,
// because arbitrary section payloads can't satisfy Inertia's form-data type.
interface PageMetaForm {
    slug: string;
    template: string;
    accent: string;
    status: string;
    heroMediaId: number | null;
    sort: number;
    title: Record<string, string>;
    excerpt: Record<string, string>;
    seoTitle: Record<string, string>;
    seoDescription: Record<string, string>;
}

export default function PageEdit({ page, sectionTypes, references, templates, accents, locales, mediaLibrary }: Props) {
    const isNew = page === null;

    const form = useForm<PageMetaForm>({
        slug: page?.slug ?? '',
        template: page?.template ?? 'default',
        accent: page?.accent ?? 'blue',
        status: page?.status ?? 'draft',
        heroMediaId: page?.heroMediaId ?? null,
        sort: page?.sort ?? 0,
        title: page?.title ?? {},
        excerpt: page?.excerpt ?? {},
        seoTitle: page?.seoTitle ?? {},
        seoDescription: page?.seoDescription ?? {},
    });

    const [sections, setSections] = useState<EditorSection[]>(page?.sections ?? []);
    const [addType, setAddType] = useState(sectionTypes[0]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'لوحة التحكم', href: '/cms' },
        { title: 'الصفحات', href: '/cms/pages' },
        { title: isNew ? 'صفحة جديدة' : (form.data.title[locales[0].code] ?? 'تحرير'), href: '#' },
    ];

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, sections }));

        if (isNew) {
            form.post(pages.store().url);
        } else {
            form.put(pages.update(page.id).url, { preserveScroll: true });
        }
    };

    const updateSection = (index: number, next: EditorSection) => {
        setSections((current) => current.map((section, i) => (i === index ? next : section)));
    };

    const moveSection = (index: number, dir: -1 | 1) => {
        const target = index + dir;

        if (target < 0 || target >= sections.length) {
return;
}

        setSections((current) => {
            const next = [...current];
            [next[index], next[target]] = [next[target], next[index]];

            return next;
        });
    };

    const addSection = () => {
        setSections((current) => [...current, { type: addType, payload: {}, isVisible: true }]);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={isNew ? 'صفحة جديدة' : 'تحرير صفحة'} />

            <form onSubmit={submit} className="mx-auto max-w-5xl space-y-6 p-4 sm:p-6">
                <div className="flex items-center justify-between gap-4">
                    <Button variant="ghost" size="sm" asChild>
                        <Link href={pages.index().url}>
                            <ArrowRight className="size-4 rtl:-scale-x-100" />
                            الصفحات
                        </Link>
                    </Button>
                    <div className="flex items-center gap-2">
                        {!isNew && (
                            <Button variant="outline" size="sm" asChild>
                                <a href={`/${form.data.slug}`} target="_blank" rel="noreferrer">
                                    معاينة
                                </a>
                            </Button>
                        )}
                        <Button type="submit" disabled={form.processing}>
                            <Save className="size-4" />
                            {form.processing ? 'جارٍ الحفظ…' : 'حفظ'}
                        </Button>
                    </div>
                </div>

                {/* Meta */}
                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">معلومات الصفحة</h2>

                    <TranslatableField
                        label="العنوان"
                        value={form.data.title}
                        locales={locales}
                        onChange={(v) => form.setData('title', v)}
                        required
                    />
                    {form.errors['title.ar'] && (
                        <p className="text-sm text-destructive">{form.errors['title.ar']}</p>
                    )}

                    <TranslatableField
                        label="المقتطف"
                        value={form.data.excerpt}
                        locales={locales}
                        onChange={(v) => form.setData('excerpt', v)}
                        multiline
                    />

                    <MediaPicker
                        label="الصورة الرئيسية (Hero)"
                        value={form.data.heroMediaId}
                        onChange={(id) => form.setData('heroMediaId', id)}
                        library={mediaLibrary}
                    />

                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div className="space-y-1.5">
                            <Label htmlFor="slug">المسار (Slug)</Label>
                            <Input
                                id="slug"
                                dir="ltr"
                                value={form.data.slug}
                                onChange={(e) => form.setData('slug', e.target.value)}
                            />
                            {form.errors.slug && (
                                <p className="text-sm text-destructive">{form.errors.slug}</p>
                            )}
                        </div>
                        <Select
                            label="القالب"
                            value={form.data.template}
                            options={templates}
                            onChange={(v) => form.setData('template', v)}
                        />
                        <Select
                            label="اللون المميز"
                            value={form.data.accent ?? ''}
                            options={accents}
                            onChange={(v) => form.setData('accent', v)}
                        />
                        <Select
                            label="الحالة"
                            value={form.data.status}
                            options={['draft', 'published']}
                            labels={{ draft: 'مسودة', published: 'منشورة' }}
                            onChange={(v) => form.setData('status', v)}
                        />
                    </div>
                </section>

                {/* Sections */}
                <section className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h2 className="font-display text-lg font-bold">الأقسام</h2>
                        <div className="flex items-center gap-2">
                            <select
                                value={addType}
                                onChange={(e) => setAddType(e.target.value)}
                                className="h-9 rounded-md border border-input bg-background px-3 text-sm"
                            >
                                {sectionTypes.map((type) => (
                                    <option key={type} value={type}>
                                        {SECTION_LABELS[type] ?? type}
                                    </option>
                                ))}
                            </select>
                            <Button type="button" variant="outline" size="sm" onClick={addSection}>
                                <Plus className="size-4" />
                                إضافة قسم
                            </Button>
                        </div>
                    </div>

                    {sections.length === 0 ? (
                        <p className="rounded-lg border border-dashed border-border py-10 text-center text-sm text-muted-foreground">
                            لا توجد أقسام بعد. أضف قسماً للبدء.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {sections.map((section, index) => (
                                <SectionEditor
                                    key={section.id ?? `new-${index}`}
                                    section={section}
                                    index={index}
                                    total={sections.length}
                                    locales={locales}
                                    library={mediaLibrary}
                                    references={references}
                                    onChange={(next) => updateSection(index, next)}
                                    onMove={(dir) => moveSection(index, dir)}
                                    onRemove={() =>
                                        setSections((current) => current.filter((_, i) => i !== index))
                                    }
                                />
                            ))}
                        </div>
                    )}
                </section>

                {/* SEO */}
                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">تحسين محركات البحث</h2>
                    <TranslatableField
                        label="عنوان SEO"
                        value={form.data.seoTitle}
                        locales={locales}
                        onChange={(v) => form.setData('seoTitle', v)}
                    />
                    <TranslatableField
                        label="وصف SEO"
                        value={form.data.seoDescription}
                        locales={locales}
                        onChange={(v) => form.setData('seoDescription', v)}
                        multiline
                    />
                </section>

                {!isNew && (
                    <div className="flex justify-start border-t border-border pt-4">
                        <Button
                            type="button"
                            variant="ghost"
                            className="text-destructive hover:text-destructive"
                            onClick={() => {
                                if (confirm('حذف هذه الصفحة نهائياً؟')) {
                                    router.delete(pages.destroy(page.id).url);
                                }
                            }}
                        >
                            حذف الصفحة
                        </Button>
                    </div>
                )}
            </form>
        </AppLayout>
    );
}

function Select({
    label,
    value,
    options,
    labels,
    onChange,
}: {
    label: string;
    value: string;
    options: string[];
    labels?: Record<string, string>;
    onChange: (value: string) => void;
}) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            <select
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
                {options.map((option) => (
                    <option key={option} value={option}>
                        {labels?.[option] ?? option}
                    </option>
                ))}
            </select>
        </div>
    );
}
