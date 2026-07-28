import { Head, Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Play, Upload, X } from 'lucide-react';
import { useRef, useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import * as media from '@/routes/cms/media';
import type { BreadcrumbItem } from '@/types';

interface MediaItem {
    id: number;
    url: string;
    kind: 'image' | 'video';
    poster: string | null;
    duration: number | null;
    filename: string;
    width: number | null;
    height: number | null;
    mime: string | null;
    alt: Record<string, string>;
    caption: Record<string, string>;
    credit: string | null;
    isDecorative: boolean;
    needsAltReview: boolean;
    focalX: number;
    focalY: number;
    usedOnPages: Array<{ id: number; slug: string; title: string | null }>;
}

interface Paginated {
    data: MediaItem[];
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الوسائط', href: '/cms/media' },
];

export default function MediaIndex({
    media: items,
    filter,
    reviewCount,
    videoCount,
    locales,
}: {
    media: Paginated;
    filter: string;
    reviewCount: number;
    videoCount: number;
    locales: LocaleMeta[];
}) {
    const [selected, setSelected] = useState<MediaItem | null>(null);
    const fileInput = useRef<HTMLInputElement>(null);
    const upload = useForm<{ file: File | null }>({ file: null });

    const onFile = (event: React.ChangeEvent<HTMLInputElement>) => {
        const file = event.target.files?.[0];

        if (!file) {
return;
}

        upload.setData('file', file);
        upload.post(media.store().url, {
            preserveScroll: true,
            forceFormData: true,
            onFinish: () => {
                upload.reset();

                if (fileInput.current) {
fileInput.current.value = '';
}
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="الوسائط" />

            <div className="space-y-4 p-4 sm:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="font-display text-2xl font-bold">مكتبة الوسائط</h1>
                        {reviewCount > 0 && (
                            <p className="mt-1 flex items-center gap-1.5 text-sm text-[color-mix(in_srgb,var(--color-druze-yellow)_70%,black)]">
                                <AlertTriangle className="size-4" />
                                {reviewCount} صورة بحاجة إلى نص بديل
                            </p>
                        )}
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <FilterTab href={media.index().url} active={filter === 'all'}>
                            الكل
                        </FilterTab>
                        <FilterTab
                            href={`${media.index().url}?filter=review`}
                            active={filter === 'review'}
                        >
                            بحاجة لمراجعة ({reviewCount})
                        </FilterTab>
                        {videoCount > 0 && (
                            <FilterTab
                                href={`${media.index().url}?filter=video`}
                                active={filter === 'video'}
                            >
                                الفيديوهات ({videoCount})
                            </FilterTab>
                        )}

                        <input
                            ref={fileInput}
                            type="file"
                            accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm"
                            className="hidden"
                            onChange={onFile}
                        />
                        <Button onClick={() => fileInput.current?.click()} disabled={upload.processing}>
                            <Upload className="size-4" />
                            {upload.processing ? 'جارٍ الرفع…' : 'رفع وسائط'}
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    {items.data.map((item) => (
                        <button
                            key={item.id}
                            type="button"
                            onClick={() => setSelected(item)}
                            className="group relative aspect-square overflow-hidden rounded-lg border border-border bg-muted text-start"
                        >
                            <MediaThumb item={item} altLocale={locales[0].code} />
                            {item.needsAltReview && (
                                <span
                                    className="absolute top-1.5 end-1.5 rounded bg-[color-mix(in_srgb,var(--color-druze-yellow)_85%,black)] p-1 text-black"
                                    title="بحاجة إلى نص بديل"
                                >
                                    <AlertTriangle className="size-3.5" />
                                </span>
                            )}
                        </button>
                    ))}
                </div>

                {items.data.length === 0 && (
                    <p className="py-16 text-center text-sm text-muted-foreground">لا توجد وسائط.</p>
                )}

                <Pagination links={items.links} />
            </div>

            {selected && (
                <MediaDrawer
                    item={selected}
                    locales={locales}
                    onClose={() => setSelected(null)}
                />
            )}
        </AppLayout>
    );
}

/** Library-grid thumbnail: the poster (with a play badge + duration) for video. */
function MediaThumb({ item, altLocale }: { item: MediaItem; altLocale: string }) {
    if (item.kind === 'video') {
        return (
            <>
                {item.poster ? (
                    <img src={item.poster} alt="" loading="lazy" className="absolute inset-0 h-full w-full object-cover" />
                ) : (
                    <span className="absolute inset-0 flex items-center justify-center bg-black/80 text-white/70">
                        <Play className="size-6" aria-hidden />
                    </span>
                )}
                <span className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span className="flex size-9 items-center justify-center rounded-full bg-black/55 text-white">
                        <Play className="size-4 translate-x-px fill-current" aria-hidden />
                    </span>
                </span>
                {item.duration ? (
                    <span className="absolute bottom-1.5 start-1.5 rounded bg-black/70 px-1 py-0.5 font-mono text-[10px] tabular-nums text-white">
                        {formatDuration(item.duration)}
                    </span>
                ) : null}
            </>
        );
    }

    return (
        <img
            src={item.url}
            alt={item.alt[altLocale] ?? ''}
            loading="lazy"
            className="absolute inset-0 h-full w-full object-cover"
            style={{ objectPosition: `${item.focalX * 100}% ${item.focalY * 100}%` }}
        />
    );
}

function formatDuration(seconds: number): string {
    const total = Math.max(0, Math.round(seconds));

    return `${Math.floor(total / 60)}:${(total % 60).toString().padStart(2, '0')}`;
}

function MediaDrawer({
    item,
    locales,
    onClose,
}: {
    item: MediaItem;
    locales: LocaleMeta[];
    onClose: () => void;
}) {
    const form = useForm({
        alt: item.alt,
        caption: item.caption,
        credit: item.credit ?? '',
        isDecorative: item.isDecorative,
        focalX: item.focalX,
        focalY: item.focalY,
    });

    const save = () => {
        form.put(media.update(item.id).url, {
            preserveScroll: true,
            onSuccess: onClose,
        });
    };

    return (
        <div className="fixed inset-0 z-50 flex" role="dialog" aria-modal="true">
            <div className="flex-1 bg-black/40" onClick={onClose} aria-hidden />
            <div className="flex w-full max-w-md flex-col overflow-y-auto border-s border-border bg-background shadow-xl">
                <header className="flex items-center justify-between border-b border-border p-4">
                    <h2 className="font-display font-bold">تفاصيل الصورة</h2>
                    <Button variant="ghost" size="icon" onClick={onClose} aria-label="إغلاق">
                        <X className="size-4" />
                    </Button>
                </header>

                <div className="space-y-4 p-4">
                    <div className="overflow-hidden rounded-lg border border-border bg-muted">
                        {item.kind === 'video' ? (
                            <video
                                src={item.url}
                                poster={item.poster ?? undefined}
                                controls
                                preload="metadata"
                                className="max-h-64 w-full bg-black object-contain"
                            />
                        ) : (
                            <img src={item.url} alt="" className="max-h-64 w-full object-contain" />
                        )}
                    </div>
                    <p className="text-xs text-muted-foreground" dir="ltr">
                        {item.filename}
                        {item.width && item.height ? ` · ${item.width}×${item.height}` : ''}
                    </p>

                    {item.kind === 'image' && (
                        <label className="flex items-center gap-2 text-sm">
                            <input
                                type="checkbox"
                                checked={form.data.isDecorative}
                                onChange={(e) => form.setData('isDecorative', e.target.checked)}
                                className="size-4"
                            />
                            صورة زخرفية (تُخفى عن قارئات الشاشة)
                        </label>
                    )}

                    {item.kind === 'image' && !form.data.isDecorative && (
                        <TranslatableField
                            label="النص البديل"
                            value={form.data.alt}
                            locales={locales}
                            onChange={(v) => form.setData('alt', v)}
                            required
                            hint="صف ما تُظهره الصورة. مطلوب بالعربية قبل الحفظ."
                        />
                    )}

                    <TranslatableField
                        label={item.kind === 'video' ? 'وصف الفيديو' : 'التعليق'}
                        value={form.data.caption}
                        locales={locales}
                        onChange={(v) => form.setData('caption', v)}
                        multiline
                    />

                    <div className="space-y-1.5">
                        <Label htmlFor="credit">حقوق الصورة</Label>
                        <input
                            id="credit"
                            value={form.data.credit}
                            onChange={(e) => form.setData('credit', e.target.value)}
                            className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                        />
                    </div>

                    {item.usedOnPages.length > 0 && (
                        <div className="rounded-lg bg-muted/50 p-3 text-sm">
                            <p className="mb-1 font-medium">مُستخدمة في {item.usedOnPages.length} صفحة:</p>
                            <ul className="space-y-0.5 text-muted-foreground">
                                {item.usedOnPages.map((page) => (
                                    <li key={page.id}>{page.title ?? page.slug}</li>
                                ))}
                            </ul>
                        </div>
                    )}
                </div>

                <footer className="mt-auto flex items-center justify-between gap-2 border-t border-border p-4">
                    <Button
                        variant="ghost"
                        className="text-destructive hover:text-destructive"
                        onClick={() => {
                            if (confirm('حذف هذه الصورة؟')) {
                                router.delete(media.destroy(item.id).url, { onSuccess: onClose });
                            }
                        }}
                    >
                        حذف
                    </Button>
                    <Button onClick={save} disabled={form.processing}>
                        {form.processing ? 'جارٍ الحفظ…' : 'حفظ'}
                    </Button>
                </footer>
            </div>
        </div>
    );
}

function FilterTab({
    href,
    active,
    children,
}: {
    href: string;
    active: boolean;
    children: React.ReactNode;
}) {
    return (
        <Link
            href={href}
            className={cn(
                'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                active ? 'bg-druze-blue text-white' : 'text-muted-foreground hover:bg-muted',
            )}
        >
            {children}
        </Link>
    );
}

function Pagination({ links }: { links: Paginated['links'] }) {
    if (links.length <= 3) {
return null;
}

    return (
        <nav className="flex flex-wrap items-center justify-center gap-1">
            {links.map((link, index) => (
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
    );
}
