import { Head, useForm } from '@inertiajs/react';
import { Plus, Save, Trash2 } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
import type { LibraryImage } from '@/components/cms/media-picker';
import { MediaPicker } from '@/components/cms/media-picker';
import { TranslatableField } from '@/components/cms/translatable-field';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import * as settingsRoute from '@/routes/cms/settings';
import type { BreadcrumbItem } from '@/types';

interface Phone {
    label: Record<string, string>;
    value: string;
}
interface Social {
    platform: string;
    url: string;
}

interface SettingsData {
    siteShortName: Record<string, string>;
    siteTagline: Record<string, string>;
    siteSeoDescription: Record<string, string>;
    footerNote: Record<string, string>;
    contactEmail: string;
    contactPhones: Phone[];
    contactAddress: string | null;
    socialLinks: Social[];
    iconMediaId: number | null;
    logoMediaId: number | null;
    ogImageMediaId: number | null;
    themeColor: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الإعدادات', href: '/cms/settings' },
];

export default function SettingsEdit({
    settings,
    locales,
    mediaLibrary,
    iconPreview,
}: {
    settings: SettingsData;
    locales: LocaleMeta[];
    mediaLibrary: LibraryImage[];
    iconPreview: string | null;
}) {
    const form = useForm<SettingsData>({
        siteShortName: settings.siteShortName ?? {},
        siteTagline: settings.siteTagline ?? {},
        siteSeoDescription: settings.siteSeoDescription ?? {},
        footerNote: settings.footerNote ?? {},
        contactEmail: settings.contactEmail ?? '',
        contactPhones: settings.contactPhones ?? [],
        contactAddress: settings.contactAddress ?? '',
        socialLinks: settings.socialLinks ?? [],
        iconMediaId: settings.iconMediaId ?? null,
        logoMediaId: settings.logoMediaId ?? null,
        ogImageMediaId: settings.ogImageMediaId ?? null,
        themeColor: settings.themeColor ?? '#0057B8',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="الإعدادات" />
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    form.put(settingsRoute.update().url, { preserveScroll: true });
                }}
                className="mx-auto max-w-3xl space-y-6 p-4 sm:p-6"
            >
                <div className="flex items-center justify-between">
                    <h1 className="font-display text-2xl font-bold">إعدادات الموقع</h1>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="size-4" />
                        حفظ
                    </Button>
                </div>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">الهوية</h2>
                    <TranslatableField
                        label="اسم الموقع المختصر"
                        value={form.data.siteShortName}
                        locales={locales}
                        onChange={(v) => form.setData('siteShortName', v)}
                    />
                    <TranslatableField
                        label="الشعار النصي"
                        value={form.data.siteTagline}
                        locales={locales}
                        onChange={(v) => form.setData('siteTagline', v)}
                        multiline
                    />
                    <TranslatableField
                        label="نص التذييل"
                        value={form.data.footerNote}
                        locales={locales}
                        onChange={(v) => form.setData('footerNote', v)}
                        multiline
                    />
                </section>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">الهوية البصرية</h2>
                    <p className="text-sm text-muted-foreground">
                        أيقونة التطبيق تُستخدم لأيقونة المتصفح (Favicon) وأيقونة الشاشة الرئيسية على
                        الهواتف. اختر صورة مربّعة بدقة 512×512 على الأقل.
                    </p>

                    <div className="grid gap-5 sm:grid-cols-3">
                        <div className="space-y-2">
                            <MediaPicker
                                label="أيقونة التطبيق"
                                value={form.data.iconMediaId}
                                onChange={(id) => form.setData('iconMediaId', id)}
                                library={mediaLibrary}
                            />
                            {iconPreview && (
                                <div className="flex items-center gap-2 text-xs text-muted-foreground">
                                    <img
                                        src={iconPreview}
                                        alt=""
                                        className="size-8 rounded border border-border"
                                    />
                                    الأيقونة الحالية
                                </div>
                            )}
                            {form.errors.iconMediaId && (
                                <p className="text-sm text-destructive">{form.errors.iconMediaId}</p>
                            )}
                        </div>

                        <MediaPicker
                            label="شعار الترويسة (اختياري)"
                            value={form.data.logoMediaId}
                            onChange={(id) => form.setData('logoMediaId', id)}
                            library={mediaLibrary}
                        />

                        <MediaPicker
                            label="صورة المشاركة الافتراضية"
                            value={form.data.ogImageMediaId}
                            onChange={(id) => form.setData('ogImageMediaId', id)}
                            library={mediaLibrary}
                        />
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="themeColor">لون الواجهة (Theme color)</Label>
                        <div className="flex items-center gap-2">
                            <input
                                id="themeColor"
                                type="color"
                                value={form.data.themeColor}
                                onChange={(e) => form.setData('themeColor', e.target.value)}
                                className="h-9 w-14 cursor-pointer rounded-md border border-input bg-background p-1"
                            />
                            <Input
                                dir="ltr"
                                value={form.data.themeColor}
                                onChange={(e) => form.setData('themeColor', e.target.value)}
                                className="max-w-32 font-mono"
                                aria-label="لون الواجهة بصيغة hex"
                            />
                        </div>
                        <p className="text-xs text-muted-foreground">
                            يلوّن شريط المتصفح على الهواتف.
                        </p>
                        {form.errors.themeColor && (
                            <p className="text-sm text-destructive">{form.errors.themeColor}</p>
                        )}
                    </div>

                    <TranslatableField
                        label="وصف الموقع الافتراضي (SEO)"
                        value={form.data.siteSeoDescription}
                        locales={locales}
                        onChange={(v) => form.setData('siteSeoDescription', v)}
                        multiline
                        hint="يُستخدم في نتائج البحث ومعاينات المشاركة للصفحات التي ليس لها وصف خاص."
                    />
                </section>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">التواصل</h2>
                    <div className="space-y-1.5">
                        <Label htmlFor="email">البريد الإلكتروني</Label>
                        <Input
                            id="email"
                            dir="ltr"
                            value={form.data.contactEmail}
                            onChange={(e) => form.setData('contactEmail', e.target.value)}
                        />
                    </div>

                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <Label>أرقام الهاتف</Label>
                            <Button
                                type="button"
                                variant="ghost"
                                size="sm"
                                onClick={() =>
                                    form.setData('contactPhones', [
                                        ...form.data.contactPhones,
                                        { label: {}, value: '' },
                                    ])
                                }
                            >
                                <Plus className="size-4" /> إضافة
                            </Button>
                        </div>
                        {form.data.contactPhones.map((phone, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <Input
                                    dir="ltr"
                                    placeholder="الرقم"
                                    value={phone.value}
                                    onChange={(e) => {
                                        const phones = [...form.data.contactPhones];
                                        phones[i] = { ...phones[i], value: e.target.value };
                                        form.setData('contactPhones', phones);
                                    }}
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    className="text-destructive"
                                    onClick={() =>
                                        form.setData(
                                            'contactPhones',
                                            form.data.contactPhones.filter((_, j) => j !== i),
                                        )
                                    }
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <div className="flex items-center justify-between">
                        <h2 className="font-display text-lg font-bold">روابط التواصل الاجتماعي</h2>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={() =>
                                form.setData('socialLinks', [
                                    ...form.data.socialLinks,
                                    { platform: '', url: '' },
                                ])
                            }
                        >
                            <Plus className="size-4" /> إضافة
                        </Button>
                    </div>
                    {form.data.socialLinks.length === 0 && (
                        <p className="text-sm text-muted-foreground">لا توجد روابط بعد.</p>
                    )}
                    {form.data.socialLinks.map((link, i) => (
                        <div key={i} className="flex items-center gap-2">
                            <Input
                                placeholder="المنصة (facebook…)"
                                dir="ltr"
                                value={link.platform}
                                onChange={(e) => {
                                    const links = [...form.data.socialLinks];
                                    links[i] = { ...links[i], platform: e.target.value };
                                    form.setData('socialLinks', links);
                                }}
                                className="max-w-40"
                            />
                            <Input
                                placeholder="https://…"
                                dir="ltr"
                                value={link.url}
                                onChange={(e) => {
                                    const links = [...form.data.socialLinks];
                                    links[i] = { ...links[i], url: e.target.value };
                                    form.setData('socialLinks', links);
                                }}
                            />
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                className="text-destructive"
                                onClick={() =>
                                    form.setData(
                                        'socialLinks',
                                        form.data.socialLinks.filter((_, j) => j !== i),
                                    )
                                }
                            >
                                <Trash2 className="size-4" />
                            </Button>
                        </div>
                    ))}
                </section>
            </form>
        </AppLayout>
    );
}
