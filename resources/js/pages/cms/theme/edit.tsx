import { Head, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as theme from '@/routes/cms/theme';
import type { BreadcrumbItem } from '@/types';

interface FontOption {
    key: string;
    label: string;
}

interface FontChoice {
    display: string;
    body: string;
}

interface Props {
    theme: {
        colors: Record<string, string | null>;
        fonts: Record<string, FontChoice>;
    };
    defaults: Record<string, string>;
    fonts: FontOption[];
    locales: LocaleMeta[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'المظهر', href: '/cms/theme' },
];

const COLOR_FIELDS: Array<{ key: string; label: string }> = [
    { key: 'blue', label: 'اللون الأساسي (الأزرق)' },
    { key: 'green', label: 'الأخضر' },
    { key: 'red', label: 'الأحمر' },
    { key: 'yellow', label: 'الأصفر' },
    { key: 'ink', label: 'لون النص' },
    { key: 'surface', label: 'لون الخلفية' },
    { key: 'bar_dark', label: 'الشريط الداكن' },
];

export default function ThemeEdit({ theme: current, defaults, fonts, locales }: Props) {
    const form = useForm<{ colors: Record<string, string>; fonts: Record<string, FontChoice> }>({
        colors: Object.fromEntries(COLOR_FIELDS.map((f) => [f.key, current.colors[f.key] ?? defaults[f.key]])),
        fonts: current.fonts,
    });

    const familyOf = (key: string) => fonts.find((f) => f.key === key)?.label ?? key;

    const reset = () => form.setData('colors', Object.fromEntries(COLOR_FIELDS.map((f) => [f.key, defaults[f.key]])));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="المظهر" />
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(theme.update().url, { preserveScroll: true });
                }}
                className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6"
            >
                <div className="flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold">المظهر</h1>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="size-4" />
                        {form.processing ? 'جارٍ الحفظ…' : 'حفظ'}
                    </Button>
                </div>

                {/* Colors */}
                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <div className="flex items-center justify-between">
                        <h2 className="font-display text-lg font-bold">ألوان الموقع</h2>
                        <button type="button" onClick={reset} className="text-xs text-druze-blue hover:underline">
                            إعادة الألوان الافتراضية
                        </button>
                    </div>
                    <p className="text-sm text-muted-foreground">
                        تُطبَّق هذه الألوان على كامل الموقع (الأزرار، الروابط، العناوين، الخلفيات). تأكّد من وضوح
                        النص على الخلفية بعد التغيير.
                    </p>

                    <div className="grid gap-3 sm:grid-cols-2">
                        {COLOR_FIELDS.map((field) => (
                            <ColorField
                                key={field.key}
                                label={field.label}
                                value={form.data.colors[field.key]}
                                onChange={(v) => form.setData('colors', { ...form.data.colors, [field.key]: v })}
                            />
                        ))}
                    </div>

                    <ThemePreview colors={form.data.colors} />
                </section>

                {/* Typography */}
                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <div>
                        <h2 className="font-display text-lg font-bold">الخطوط لكل لغة</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            اختر خط العناوين وخط النص لكل لغة. الخطوط محمّلة ومستضافة محلياً.
                        </p>
                    </div>

                    <div className="space-y-3">
                        {locales.map((locale) => {
                            const choice = form.data.fonts[locale.code] ?? { display: 'cairo', body: 'ibm-plex-sans-arabic' };

                            return (
                                <div key={locale.code} className="grid items-end gap-3 rounded-lg border border-border p-3 sm:grid-cols-3">
                                    <div className="text-sm font-semibold">
                                        {locale.name}
                                        {locale.default && <span className="ms-1 text-xs text-muted-foreground">(افتراضي)</span>}
                                    </div>
                                    <FontSelect
                                        label="خط العناوين"
                                        value={choice.display}
                                        fonts={fonts}
                                        onChange={(v) => form.setData('fonts', { ...form.data.fonts, [locale.code]: { ...choice, display: v } })}
                                    />
                                    <FontSelect
                                        label="خط النص"
                                        value={choice.body}
                                        fonts={fonts}
                                        onChange={(v) => form.setData('fonts', { ...form.data.fonts, [locale.code]: { ...choice, body: v } })}
                                    />
                                    <p className="text-lg sm:col-span-3" style={{ fontFamily: `'${familyOf(choice.display)}'` }} dir={locale.dir}>
                                        {locale.dir === 'rtl' ? 'الهوية الدرزية — بنو معروف' : 'Druze Identity — Bani Maʿruf'}
                                    </p>
                                </div>
                            );
                        })}
                    </div>
                </section>
            </form>
        </AppLayout>
    );
}

function ColorField({ label, value, onChange }: { label: string; value: string; onChange: (v: string) => void }) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            <div className="flex items-center gap-2">
                <input
                    type="color"
                    value={value}
                    onChange={(e) => onChange(e.target.value)}
                    className="size-9 shrink-0 cursor-pointer rounded-md border border-input bg-background"
                    aria-label={label}
                />
                <Input dir="ltr" value={value} onChange={(e) => onChange(e.target.value)} className="font-mono uppercase" />
            </div>
        </div>
    );
}

function FontSelect({ label, value, fonts, onChange }: { label: string; value: string; fonts: FontOption[]; onChange: (v: string) => void }) {
    return (
        <div className="space-y-1.5">
            <Label>{label}</Label>
            <select
                value={value}
                onChange={(e) => onChange(e.target.value)}
                className="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
            >
                {fonts.map((f) => (
                    <option key={f.key} value={f.key}>{f.label}</option>
                ))}
            </select>
        </div>
    );
}

function ThemePreview({ colors }: { colors: Record<string, string> }) {
    return (
        <div className="rounded-lg border border-border p-4" style={{ backgroundColor: colors.surface, color: colors.ink }}>
            <p className="text-xs font-semibold uppercase tracking-wide" style={{ color: colors.blue }}>
                معاينة
            </p>
            <h3 className="mt-1 font-display text-xl font-bold" style={{ color: colors.blue }}>
                عنوان تجريبي للموقع
            </h3>
            <p className="mt-2 text-sm" style={{ color: colors.ink }}>
                نص فقرة تجريبي لتوضيح كيف تبدو الألوان معاً على الخلفية المختارة.
            </p>
            <div className="mt-3 flex flex-wrap gap-2">
                <span className="rounded-full px-4 py-1.5 text-sm font-semibold text-white" style={{ backgroundColor: colors.blue }}>
                    زر أساسي
                </span>
                <span className="rounded-full px-3 py-1 text-sm font-semibold text-white" style={{ backgroundColor: colors.green }}>أخضر</span>
                <span className="rounded-full px-3 py-1 text-sm font-semibold text-white" style={{ backgroundColor: colors.red }}>أحمر</span>
                <span className="rounded-full px-3 py-1 text-sm font-semibold" style={{ backgroundColor: colors.yellow, color: colors.ink }}>أصفر</span>
            </div>
        </div>
    );
}
