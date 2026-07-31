import { ChevronDown, ChevronUp, Plus, Trash2 } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPicker, MultiMediaPicker } from '@/components/cms/media-picker';
import { TranslatableField } from '@/components/cms/translatable-field';
import { TranslatableHtml } from '@/components/cms/translatable-html';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type LocaleMap = Record<string, string>;
type Payload = Record<string, unknown>;

export interface Reference {
    id: number;
    name: string;
}

/** Content a dynamic hero slide can point at, provided by the page editor. */
export interface SectionReferences {
    articles: Reference[];
    categories: Reference[];
    pages: Reference[];
}

export interface SectionFormProps {
    payload: Payload;
    onChange: (payload: Payload) => void;
    locales: LocaleMeta[];
    library: LibraryImage[];
    references?: SectionReferences;
}

/** Section types that have a structured editor; others fall back to raw JSON. */
export const STRUCTURED_TYPES = [
    'hero',
    'hero_slider',
    'rich_text',
    'card_grid',
    'gallery',
    'video',
    'contact',
    'under_construction',
    'accordion',
];

/** Dispatch a section payload to its structured form. */
export function SectionForm({ type, ...props }: SectionFormProps & { type: string }) {
    switch (type) {
        case 'hero':
            return <HeroForm {...props} />;
        case 'hero_slider':
            return <SliderForm {...props} />;
        case 'rich_text':
            return <RichTextForm {...props} />;
        case 'card_grid':
            return <CardGridForm {...props} />;
        case 'gallery':
            return <GalleryForm {...props} />;
        case 'video':
            return <VideoForm {...props} />;
        case 'contact':
            return <ContactForm {...props} />;
        case 'under_construction':
            return <UnderConstructionForm {...props} />;
        case 'accordion':
            return <AccordionForm {...props} />;
        default:
            return null;
    }
}

// -- helpers ----------------------------------------------------------------

const asMap = (v: unknown): LocaleMap => (v && typeof v === 'object' && !Array.isArray(v) ? (v as LocaleMap) : {});
const asIds = (v: unknown): number[] => (Array.isArray(v) ? v.filter((x): x is number => typeof x === 'number') : []);

// -- forms ------------------------------------------------------------------

function HeroForm({ payload, onChange, locales, library }: SectionFormProps) {
    const actions = Array.isArray(payload.actions) ? (payload.actions as Array<Record<string, unknown>>) : [];

    return (
        <div className="space-y-4">
            <TranslatableField label="العنوان" value={asMap(payload.title)} locales={locales} onChange={(title) => onChange({ ...payload, title })} />
            <TranslatableField label="العنوان الفرعي" value={asMap(payload.subtitle)} locales={locales} onChange={(subtitle) => onChange({ ...payload, subtitle })} multiline />
            <MultiMediaPicker label="صور الخلفية (تتبدّل)" value={asIds(payload.media_ids)} onChange={(media_ids) => onChange({ ...payload, media_ids })} library={library} />

            <div className="space-y-2">
                <Label>الأزرار</Label>
                {actions.map((action, i) => (
                    <div key={i} className="grid gap-2 rounded border border-border p-2 sm:grid-cols-2">
                        <TranslatableField label="النص" value={asMap(action.label)} locales={locales} onChange={(label) => onChange({ ...payload, actions: actions.map((a, j) => (j === i ? { ...a, label } : a)) })} />
                        <div className="space-y-1.5">
                            <Label>الوجهة (رابط أو #مرساة)</Label>
                            <Input dir="ltr" value={(action.href as string) ?? (action.slug as string) ?? ''} onChange={(e) => onChange({ ...payload, actions: actions.map((a, j) => (j === i ? { ...a, href: e.target.value, slug: undefined } : a)) })} />
                        </div>
                        <button type="button" className="text-xs text-destructive hover:underline sm:col-span-2" onClick={() => onChange({ ...payload, actions: actions.filter((_, j) => j !== i) })}>حذف الزر</button>
                    </div>
                ))}
                <Button type="button" variant="ghost" size="sm" onClick={() => onChange({ ...payload, actions: [...actions, { label: {}, href: '' }] })}>
                    <Plus className="size-3.5" /> إضافة زر
                </Button>
            </div>
        </div>
    );
}

const SLIDE_KINDS: Array<{ value: string; label: string }> = [
    { value: 'manual', label: 'صورة وعنوان مخصّص' },
    { value: 'article', label: 'خبر (ديناميكي)' },
    { value: 'category', label: 'تصنيف (ديناميكي)' },
    { value: 'page', label: 'صفحة (ديناميكي)' },
];

function refListFor(kind: string, references?: SectionReferences): Reference[] {
    if (!references) {
        return [];
    }

    return kind === 'article' ? references.articles : kind === 'category' ? references.categories : kind === 'page' ? references.pages : [];
}

/** Up to two optional call-to-action buttons — shared by every slide kind. */
function ActionsEditor({
    actions,
    locales,
    onChange,
}: {
    actions: Array<Record<string, unknown>>;
    locales: LocaleMeta[];
    onChange: (actions: Array<Record<string, unknown>>) => void;
}) {
    return (
        <div className="space-y-2">
            <Label>الأزرار (حتى زرّين، اختياري)</Label>
            {actions.map((action, i) => (
                <div key={i} className="grid gap-2 rounded border border-border p-2 sm:grid-cols-2">
                    <TranslatableField label="النص" value={asMap(action.label)} locales={locales} onChange={(label) => onChange(actions.map((a, j) => (j === i ? { ...a, label } : a)))} />
                    <div className="space-y-1.5">
                        <Label>الوجهة (رابط أو #مرساة)</Label>
                        <Input dir="ltr" value={(action.href as string) ?? ''} onChange={(e) => onChange(actions.map((a, j) => (j === i ? { ...a, href: e.target.value } : a)))} />
                    </div>
                    <button type="button" className="text-xs text-destructive hover:underline sm:col-span-2" onClick={() => onChange(actions.filter((_, j) => j !== i))}>حذف الزر</button>
                </div>
            ))}
            {actions.length < 2 && (
                <Button type="button" variant="ghost" size="sm" onClick={() => onChange([...actions, { label: {}, href: '' }])}>
                    <Plus className="size-3.5" /> إضافة زر
                </Button>
            )}
        </div>
    );
}

function SlideEditor({
    slide,
    index,
    total,
    locales,
    library,
    references,
    onChange,
    onRemove,
    onMove,
}: {
    slide: Record<string, unknown>;
    index: number;
    total: number;
    locales: LocaleMeta[];
    library: LibraryImage[];
    references?: SectionReferences;
    onChange: (patch: Record<string, unknown>) => void;
    onRemove: () => void;
    onMove: (dir: -1 | 1) => void;
}) {
    const kind = (slide.kind as string) ?? 'manual';
    const actions = Array.isArray(slide.actions) ? (slide.actions as Array<Record<string, unknown>>) : [];
    const refList = refListFor(kind, references);

    return (
        <div className="space-y-3 rounded-lg border border-border p-3">
            <div className="flex items-center gap-2">
                <span className="text-xs font-semibold text-muted-foreground">الشريحة #{index + 1}</span>
                <div className="ms-auto flex items-center gap-1">
                    <button type="button" disabled={index === 0} onClick={() => onMove(-1)} aria-label="نقل لأعلى" className="text-muted-foreground disabled:opacity-30"><ChevronUp className="size-4" /></button>
                    <button type="button" disabled={index === total - 1} onClick={() => onMove(1)} aria-label="نقل لأسفل" className="text-muted-foreground disabled:opacity-30"><ChevronDown className="size-4" /></button>
                    <button type="button" onClick={onRemove} aria-label="حذف الشريحة" className="text-destructive"><Trash2 className="size-4" /></button>
                </div>
            </div>

            <div className="space-y-1.5">
                <Label>نوع الشريحة</Label>
                <select value={kind} onChange={(e) => onChange({ kind: e.target.value })} className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                    {SLIDE_KINDS.map((k) => <option key={k.value} value={k.value}>{k.label}</option>)}
                </select>
            </div>

            {kind === 'manual' ? (
                <div className="space-y-3">
                    <MediaPicker label="الصورة" value={(slide.media_id as number) ?? null} onChange={(media_id) => onChange({ media_id })} library={library} />
                    <TranslatableField label="العنوان" value={asMap(slide.title)} locales={locales} onChange={(title) => onChange({ title })} />
                    <TranslatableField label="الوصف" value={asMap(slide.subtitle)} locales={locales} onChange={(subtitle) => onChange({ subtitle })} multiline />
                </div>
            ) : (
                <div className="space-y-1.5">
                    <Label>المحتوى المرتبط</Label>
                    <select value={(slide.ref_id as number) ?? ''} onChange={(e) => onChange({ ref_id: e.target.value ? Number(e.target.value) : null })} className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                        <option value="">— اختر —</option>
                        {refList.map((r) => <option key={r.id} value={r.id}>{r.name}</option>)}
                    </select>
                    <p className="text-xs text-muted-foreground">يُسحب العنوان والوصف والصورة تلقائياً من المحتوى المختار.</p>
                </div>
            )}

            <ActionsEditor actions={actions} locales={locales} onChange={(next) => onChange({ actions: next })} />
        </div>
    );
}

function SliderForm({ payload, onChange, locales, library, references }: SectionFormProps) {
    const slides = Array.isArray(payload.slides) ? (payload.slides as Array<Record<string, unknown>>) : [];

    const moveSlide = (i: number, dir: -1 | 1) => {
        const target = i + dir;

        if (target < 0 || target >= slides.length) {
            return;
        }

        const next = [...slides];
        [next[i], next[target]] = [next[target], next[i]];
        onChange({ ...payload, slides: next });
    };

    return (
        <div className="space-y-3">
            <Label>الشرائح</Label>
            {slides.map((slide, i) => (
                <SlideEditor
                    key={i}
                    slide={slide}
                    index={i}
                    total={slides.length}
                    locales={locales}
                    library={library}
                    references={references}
                    onChange={(patch) => onChange({ ...payload, slides: slides.map((s, j) => (j === i ? { ...s, ...patch } : s)) })}
                    onRemove={() => onChange({ ...payload, slides: slides.filter((_, j) => j !== i) })}
                    onMove={(dir) => moveSlide(i, dir)}
                />
            ))}
            <Button type="button" variant="outline" size="sm" onClick={() => onChange({ ...payload, slides: [...slides, { kind: 'manual', title: {}, subtitle: {}, media_id: null, actions: [] }] })}>
                <Plus className="size-3.5" /> إضافة شريحة
            </Button>
        </div>
    );
}

function RichTextForm({ payload, onChange, locales }: SectionFormProps) {
    return (
        <div className="space-y-3">
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" className="size-4" checked={payload.variant === 'feature'} onChange={(e) => onChange({ ...payload, variant: e.target.checked ? 'feature' : undefined })} />
                عرض كمقال مميز (وسط الصفحة)
            </label>
            <TranslatableHtml label="المحتوى" value={asMap(payload.html)} locales={locales} onChange={(html) => onChange({ ...payload, html })} />
        </div>
    );
}

function CardGridForm({ payload, onChange, locales, library }: SectionFormProps) {
    const cards = Array.isArray(payload.cards) ? (payload.cards as Array<Record<string, unknown>>) : [];
    const setCard = (i: number, patch: Record<string, unknown>) => onChange({ ...payload, cards: cards.map((c, j) => (j === i ? { ...c, ...patch } : c)) });

    return (
        <div className="space-y-4">
            <TranslatableField label="العنوان" value={asMap(payload.heading)} locales={locales} onChange={(heading) => onChange({ ...payload, heading })} />
            <TranslatableField label="مقدمة" value={asMap(payload.intro)} locales={locales} onChange={(intro) => onChange({ ...payload, intro })} multiline />
            <div className="space-y-1.5">
                <Label>مرساة (Anchor)</Label>
                <Input dir="ltr" value={(payload.anchor as string) ?? ''} onChange={(e) => onChange({ ...payload, anchor: e.target.value })} />
            </div>

            <div className="space-y-3">
                <Label>البطاقات</Label>
                {cards.map((card, i) => (
                    <div key={i} className="space-y-2 rounded border border-border p-3">
                        <div className="flex gap-3">
                            <MediaPicker value={(card.media_id as number) ?? null} onChange={(media_id) => setCard(i, { media_id })} library={library} />
                            <div className="flex-1 space-y-2">
                                <TranslatableField label="عنوان البطاقة" value={asMap(card.title)} locales={locales} onChange={(title) => setCard(i, { title })} />
                                <div className="grid grid-cols-2 gap-2">
                                    <div className="space-y-1.5">
                                        <Label>المسار (slug)</Label>
                                        <Input dir="ltr" value={(card.slug as string) ?? ''} onChange={(e) => setCard(i, { slug: e.target.value })} />
                                    </div>
                                    <div className="space-y-1.5">
                                        <Label>اللون</Label>
                                        <select value={(card.accent as string) ?? 'blue'} onChange={(e) => setCard(i, { accent: e.target.value })} className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm">
                                            {['blue', 'green', 'red', 'yellow', 'white'].map((a) => <option key={a} value={a}>{a}</option>)}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <TranslatableField label="مقتطف" value={asMap(card.excerpt)} locales={locales} onChange={(excerpt) => setCard(i, { excerpt })} multiline />
                        <button type="button" className="text-xs text-destructive hover:underline" onClick={() => onChange({ ...payload, cards: cards.filter((_, j) => j !== i) })}>حذف البطاقة</button>
                    </div>
                ))}
                <Button type="button" variant="ghost" size="sm" onClick={() => onChange({ ...payload, cards: [...cards, { title: {}, excerpt: {}, accent: 'blue' }] })}>
                    <Plus className="size-3.5" /> إضافة بطاقة
                </Button>
            </div>
        </div>
    );
}

function GalleryForm({ payload, onChange, library }: SectionFormProps) {
    return <MultiMediaPicker label="صور المعرض" value={asIds(payload.media_ids)} onChange={(media_ids) => onChange({ ...payload, media_ids })} library={library} />;
}

function VideoForm({ payload, onChange, locales, library }: SectionFormProps) {
    return (
        <div className="space-y-4">
            <TranslatableField label="العنوان" value={asMap(payload.heading)} locales={locales} onChange={(heading) => onChange({ ...payload, heading })} />
            <MediaPicker label="الفيديو" accept="video" value={(payload.media_id as number) ?? null} onChange={(media_id) => onChange({ ...payload, media_id })} library={library} />
            <TranslatableField label="تعليق" value={asMap(payload.caption)} locales={locales} onChange={(caption) => onChange({ ...payload, caption })} multiline />
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" className="size-4" checked={payload.autoplay === true} onChange={(e) => onChange({ ...payload, autoplay: e.target.checked })} />
                تشغيل تلقائي صامت ومتكرر (يُعطَّل عند تفضيل تقليل الحركة)
            </label>
        </div>
    );
}

function ContactForm({ payload, onChange, locales }: SectionFormProps) {
    const phones = Array.isArray(payload.phones) ? (payload.phones as string[]) : [];

    return (
        <div className="space-y-4">
            <div className="space-y-1.5">
                <Label>البريد الإلكتروني</Label>
                <Input dir="ltr" value={(payload.email as string) ?? ''} onChange={(e) => onChange({ ...payload, email: e.target.value })} />
            </div>
            <div className="space-y-2">
                <Label>أرقام الهاتف</Label>
                {phones.map((phone, i) => (
                    <div key={i} className="flex gap-2">
                        <Input dir="ltr" value={phone} onChange={(e) => onChange({ ...payload, phones: phones.map((p, j) => (j === i ? e.target.value : p)) })} />
                        <button type="button" className="text-destructive" aria-label="حذف" onClick={() => onChange({ ...payload, phones: phones.filter((_, j) => j !== i) })}><Trash2 className="size-4" /></button>
                    </div>
                ))}
                <Button type="button" variant="ghost" size="sm" onClick={() => onChange({ ...payload, phones: [...phones, ''] })}><Plus className="size-3.5" /> إضافة رقم</Button>
            </div>
            <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" className="size-4" checked={payload.showForm !== false} onChange={(e) => onChange({ ...payload, showForm: e.target.checked })} />
                إظهار نموذج التواصل
            </label>
            <TranslatableHtml label="نص تمهيدي" value={asMap(payload.html)} locales={locales} onChange={(html) => onChange({ ...payload, html })} />
        </div>
    );
}

function UnderConstructionForm({ payload, onChange, locales, library }: SectionFormProps) {
    return (
        <div className="space-y-4">
            <div className="space-y-1.5">
                <Label>النطاق (Domain)</Label>
                <Input dir="ltr" placeholder="www.example.com" value={(payload.domain as string) ?? ''} onChange={(e) => onChange({ ...payload, domain: e.target.value })} />
            </div>
            <MediaPicker label="صورة" value={(payload.media_id as number) ?? null} onChange={(media_id) => onChange({ ...payload, media_id })} library={library} />
            <TranslatableHtml label="الوصف" value={asMap(payload.html)} locales={locales} onChange={(html) => onChange({ ...payload, html })} />
        </div>
    );
}

function AccordionForm({ payload, onChange, locales }: SectionFormProps) {
    const items = Array.isArray(payload.items) ? (payload.items as Array<Record<string, unknown>>) : [];
    const setItem = (i: number, patch: Record<string, unknown>) => onChange({ ...payload, items: items.map((it, j) => (j === i ? { ...it, ...patch } : it)) });

    return (
        <div className="space-y-4">
            <TranslatableField label="العنوان" value={asMap(payload.heading)} locales={locales} onChange={(heading) => onChange({ ...payload, heading })} />
            <div className="space-y-3">
                <Label>العناصر</Label>
                {items.map((item, i) => (
                    <div key={i} className="space-y-2 rounded border border-border p-3">
                        <TranslatableField label="عنوان العنصر" value={asMap(item.title)} locales={locales} onChange={(title) => setItem(i, { title })} />
                        <TranslatableHtml label="المحتوى" value={asMap(item.html)} locales={locales} onChange={(html) => setItem(i, { html })} />
                        <button type="button" className="text-xs text-destructive hover:underline" onClick={() => onChange({ ...payload, items: items.filter((_, j) => j !== i) })}>حذف العنصر</button>
                    </div>
                ))}
                <Button type="button" variant="ghost" size="sm" onClick={() => onChange({ ...payload, items: [...items, { title: {}, blocks: [] }] })}>
                    <Plus className="size-3.5" /> إضافة عنصر
                </Button>
            </div>
        </div>
    );
}
