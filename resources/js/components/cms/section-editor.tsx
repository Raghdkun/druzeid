import { ChevronDown, ChevronUp, Code2, Eye, EyeOff, GripVertical, Trash2 } from 'lucide-react';
import { useState } from 'react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { SectionForm, STRUCTURED_TYPES } from '@/components/cms/section-forms';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

/**
 * JSON-serializable value. Section payloads are arbitrary JSON edited as raw
 * text and validated server-side, so the front end only needs a shape loose
 * enough to hold them and tight enough to serialize. Typed as a record of
 * unknown to keep Inertia's form-data mapped type from recursing without
 * bound (a recursive alias trips TS2589).
 */
export type SectionPayload = Record<string, unknown>;

export interface EditorSection {
    id?: number;
    type: string;
    payload: SectionPayload;
    isVisible: boolean;
}

const SECTION_LABELS: Record<string, string> = {
    hero: 'واجهة (Hero)',
    rich_text: 'نص منسّق',
    card_grid: 'شبكة بطاقات',
    stat_band: 'شريط إحصائيات',
    cta: 'دعوة لإجراء',
    gallery: 'معرض صور',
    video: 'فيديو',
    accordion: 'أكورديون',
    contact: 'تواصل',
    under_construction: 'قيد الإنشاء',
};

/**
 * One section card in the page builder. Handles the chrome — reorder, show/
 * hide, delete, type label — and delegates the payload form to a
 * type-specific editor. Kept deliberately simple: raw JSON editing for the
 * long tail of payload shapes, with structured controls for the common ones.
 */
export function SectionEditor({
    section,
    index,
    total,
    locales,
    library,
    onChange,
    onMove,
    onRemove,
}: {
    section: EditorSection;
    index: number;
    total: number;
    locales: LocaleMeta[];
    library: LibraryImage[];
    onChange: (next: EditorSection) => void;
    onMove: (dir: -1 | 1) => void;
    onRemove: () => void;
}) {
    const hasStructuredForm = STRUCTURED_TYPES.includes(section.type);
    const [showJson, setShowJson] = useState(!hasStructuredForm);

    return (
        <div
            className={cn(
                'rounded-lg border border-border bg-card',
                !section.isVisible && 'opacity-60',
            )}
        >
            <header className="flex items-center gap-2 border-b border-border px-3 py-2">
                <GripVertical className="size-4 text-muted-foreground" aria-hidden />
                <span className="font-display text-sm font-semibold">
                    {SECTION_LABELS[section.type] ?? section.type}
                </span>
                <span className="text-xs text-muted-foreground">#{index + 1}</span>

                <div className="ms-auto flex items-center gap-0.5">
                    {hasStructuredForm && (
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => setShowJson((v) => !v)}
                            aria-label="تحرير JSON"
                            aria-pressed={showJson}
                            className={showJson ? 'text-druze-blue' : ''}
                        >
                            <Code2 className="size-4" />
                        </Button>
                    )}
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={() => onChange({ ...section, isVisible: !section.isVisible })}
                        aria-label={section.isVisible ? 'إخفاء' : 'إظهار'}
                    >
                        {section.isVisible ? <Eye className="size-4" /> : <EyeOff className="size-4" />}
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        disabled={index === 0}
                        onClick={() => onMove(-1)}
                        aria-label="نقل لأعلى"
                    >
                        <ChevronUp className="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        disabled={index === total - 1}
                        onClick={() => onMove(1)}
                        aria-label="نقل لأسفل"
                    >
                        <ChevronDown className="size-4" />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={onRemove}
                        aria-label="حذف القسم"
                        className="text-destructive hover:text-destructive"
                    >
                        <Trash2 className="size-4" />
                    </Button>
                </div>
            </header>

            <div className="p-3">
                {hasStructuredForm && !showJson ? (
                    <SectionForm
                        type={section.type}
                        payload={section.payload}
                        locales={locales}
                        library={library}
                        onChange={(payload) => onChange({ ...section, payload })}
                    />
                ) : (
                    <JsonEditor
                        payload={section.payload}
                        onChange={(payload) => onChange({ ...section, payload })}
                    />
                )}
            </div>
        </div>
    );
}

/**
 * Raw JSON escape hatch — the default for section types without a structured
 * form (stat_band, cta), and available via the toggle for advanced edits to
 * any section. Keyed on the section identity so switching sections resets it.
 */
function JsonEditor({
    payload,
    onChange,
}: {
    payload: SectionPayload;
    onChange: (payload: SectionPayload) => void;
}) {
    return (
        <div className="space-y-1.5">
            <label className="text-xs font-medium text-muted-foreground">محتوى القسم (JSON)</label>
            <textarea
                dir="ltr"
                rows={8}
                defaultValue={JSON.stringify(payload, null, 2)}
                onBlur={(event) => {
                    try {
                        const parsed = JSON.parse(event.target.value) as SectionPayload;
                        onChange(parsed);
                        event.target.setCustomValidity('');
                    } catch {
                        event.target.setCustomValidity('JSON غير صالح');
                        event.target.reportValidity();
                    }
                }}
                className="w-full rounded-md border border-input bg-background px-3 py-2 font-mono text-xs shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                spellCheck={false}
            />
        </div>
    );
}

export { SECTION_LABELS };
