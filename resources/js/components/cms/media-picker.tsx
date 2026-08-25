import { Check, ImageOff, Play, Plus, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

export interface LibraryImage {
    id: number;
    url: string;
    kind: 'image' | 'video';
    poster?: string | null;
    alt: string;
    width: number | null;
    height: number | null;
    needsAltReview: boolean;
}

type MediaKind = 'image' | 'video';

/**
 * A library item's thumbnail: the poster (with a play badge) for video. The
 * image is absolutely positioned to fill its (aspect-ratio) parent exactly, so
 * it always cover-crops cleanly regardless of the grid/flex context.
 */
function Thumb({ item }: { item: LibraryImage }) {
    const src = item.kind === 'video' ? item.poster : item.url;

    return (
        <>
            {src ? (
                <img
                    src={src}
                    alt={item.alt}
                    loading="lazy"
                    className="absolute inset-0 h-full w-full object-cover"
                />
            ) : (
                <span className="absolute inset-0 flex items-center justify-center text-muted-foreground">
                    <Play className="size-5" aria-hidden />
                </span>
            )}
            {item.kind === 'video' && src && (
                <span className="pointer-events-none absolute inset-0 flex items-center justify-center">
                    <span className="flex size-7 items-center justify-center rounded-full bg-black/55 text-white">
                        <Play className="size-3.5 translate-x-px fill-current" aria-hidden />
                    </span>
                </span>
            )}
        </>
    );
}

/**
 * Picks a single image (or clears it) from the media library. Shows the
 * current selection as a thumbnail; the picker modal is a searchable grid.
 * Used for hero images, card images, section backgrounds — anywhere a payload
 * holds one `*_media_id`.
 */
export function MediaPicker({
    value,
    onChange,
    library,
    label,
    accept = 'image',
}: {
    value: number | null;
    onChange: (id: number | null) => void;
    library: LibraryImage[];
    label?: string;
    accept?: MediaKind;
}) {
    const [open, setOpen] = useState(false);
    const selected = library.find((image) => image.id === value) ?? null;
    const cta = accept === 'video' ? 'اختر فيديو' : 'اختر صورة';

    return (
        <div className="space-y-1.5">
            {label && <span className="text-sm font-medium">{label}</span>}

            {selected ? (
                <div className="relative w-40">
                    <span className="relative block aspect-[3/2] overflow-hidden rounded-md border border-border bg-muted">
                        <Thumb item={selected} />
                    </span>
                    <button
                        type="button"
                        onClick={() => onChange(null)}
                        className="absolute -end-2 -top-2 rounded-full bg-destructive p-1 text-white shadow"
                        aria-label="إزالة"
                    >
                        <X className="size-3.5" />
                    </button>
                    <button
                        type="button"
                        onClick={() => setOpen(true)}
                        className="mt-1 text-xs text-druze-blue hover:underline"
                    >
                        تغيير
                    </button>
                </div>
            ) : (
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="flex aspect-[3/2] w-40 flex-col items-center justify-center gap-1 rounded-md border border-dashed border-border text-sm text-muted-foreground hover:border-druze-blue hover:text-druze-blue"
                >
                    {accept === 'video' ? <Play className="size-5" /> : <ImageOff className="size-5" />}
                    {cta}
                </button>
            )}

            {open && (
                <MediaPickerModal
                    library={library}
                    accept={accept}
                    selectedId={value}
                    onSelect={(id) => {
                        onChange(id);
                        setOpen(false);
                    }}
                    onClose={() => setOpen(false)}
                />
            )}
        </div>
    );
}

/**
 * Picks an ordered set of images for galleries and hero rotations — a payload
 * holding a `media_ids` array.
 */
export function MultiMediaPicker({
    value,
    onChange,
    library,
    label,
    accept = 'image',
}: {
    value: number[];
    onChange: (ids: number[]) => void;
    library: LibraryImage[];
    label?: string;
    accept?: MediaKind;
}) {
    const [open, setOpen] = useState(false);
    const selected = value
        .map((id) => library.find((image) => image.id === id))
        .filter((image): image is LibraryImage => image !== undefined);

    const remove = (id: number) => onChange(value.filter((v) => v !== id));

    return (
        <div className="space-y-2">
            {label && <span className="text-sm font-medium">{label}</span>}

            <div className="flex flex-wrap gap-2">
                {selected.map((image) => (
                    <div key={image.id} className="relative">
                        <span className="relative block size-20 overflow-hidden rounded-md border border-border bg-muted">
                            <Thumb item={image} />
                        </span>
                        <button
                            type="button"
                            onClick={() => remove(image.id)}
                            className="absolute -end-1.5 -top-1.5 rounded-full bg-destructive p-0.5 text-white shadow"
                            aria-label="إزالة"
                        >
                            <X className="size-3" />
                        </button>
                    </div>
                ))}
                <button
                    type="button"
                    onClick={() => setOpen(true)}
                    className="flex size-20 items-center justify-center rounded-md border border-dashed border-border text-muted-foreground hover:border-druze-blue hover:text-druze-blue"
                    aria-label="إضافة"
                >
                    <Plus className="size-5" />
                </button>
            </div>

            {open && (
                <MediaPickerModal
                    library={library}
                    accept={accept}
                    selectedIds={value}
                    onSelect={(id) => onChange(value.includes(id) ? value : [...value, id])}
                    onClose={() => setOpen(false)}
                    multi
                />
            )}
        </div>
    );
}

export function MediaPickerModal({
    library,
    accept,
    selectedId,
    selectedIds,
    onSelect,
    onClose,
    multi,
}: {
    library: LibraryImage[];
    accept: MediaKind;
    selectedId?: number | null;
    selectedIds?: number[];
    onSelect: (id: number) => void;
    onClose: () => void;
    multi?: boolean;
}) {
    const [query, setQuery] = useState('');
    const inKind = library.filter((item) => item.kind === accept);
    const filtered = query
        ? inKind.filter((image) => image.alt.toLowerCase().includes(query.toLowerCase()))
        : inKind;

    const isSelected = (id: number) =>
        multi ? (selectedIds ?? []).includes(id) : selectedId === id;

    return (
        <div className="fixed inset-0 z-50 flex items-end justify-center sm:items-center sm:p-4" role="dialog" aria-modal="true">
            <div className="absolute inset-0 bg-black/50" onClick={onClose} aria-hidden />
            {/* A near-full-height sheet on mobile; a centred dialog on larger
                screens. Flex column so the grid scrolls within a fixed frame. */}
            <div className="relative flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-t-2xl border border-border bg-background shadow-xl sm:max-h-[85vh] sm:rounded-2xl">
                <header className="flex flex-wrap items-center gap-3 border-b border-border p-4">
                    <h2 className="font-display font-bold">اختر من مكتبة الوسائط</h2>
                    <Button variant="ghost" size="icon" onClick={onClose} aria-label="إغلاق" className="ms-auto sm:order-last">
                        <X className="size-4" />
                    </Button>
                    <Input
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="بحث بالنص البديل…"
                        className="order-last w-full sm:order-none sm:ms-auto sm:w-auto sm:max-w-xs"
                    />
                </header>

                <div className="grid min-h-0 flex-1 auto-rows-min grid-cols-3 gap-3 overflow-y-auto p-4 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6">
                    {filtered.map((image) => {
                        const selected = isSelected(image.id);

                        return (
                            <button
                                key={image.id}
                                type="button"
                                onClick={() => onSelect(image.id)}
                                aria-pressed={selected}
                                className={cn(
                                    'group relative aspect-square overflow-hidden rounded-lg border-2 bg-muted transition-colors',
                                    selected
                                        ? 'border-druze-blue ring-2 ring-druze-blue/30'
                                        : 'border-transparent hover:border-druze-blue/50',
                                )}
                            >
                                <Thumb item={image} />
                                {selected && (
                                    <span className="absolute inset-0 flex items-center justify-center bg-druze-blue/25">
                                        <Check className="size-6 text-white drop-shadow" aria-hidden />
                                    </span>
                                )}
                                {image.needsAltReview && (
                                    <span
                                        className="absolute top-1.5 end-1.5 size-2.5 rounded-full bg-[color-mix(in_srgb,var(--color-druze-yellow)_85%,black)] ring-2 ring-black/20"
                                        title="بحاجة إلى نص بديل"
                                    />
                                )}
                            </button>
                        );
                    })}
                    {filtered.length === 0 && (
                        <p className="col-span-full py-16 text-center text-sm text-muted-foreground">
                            لا توجد نتائج.
                        </p>
                    )}
                </div>

                <footer className="flex items-center justify-between gap-2 border-t border-border p-3">
                    <span className="text-xs text-muted-foreground">{filtered.length} عنصر</span>
                    <Button onClick={onClose}>{multi ? 'تم' : 'إغلاق'}</Button>
                </footer>
            </div>
        </div>
    );
}
