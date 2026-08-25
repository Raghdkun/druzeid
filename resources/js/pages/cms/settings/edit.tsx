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
    contactWhatsapp: string;
    contactPhones: Phone[];
    contactAddress: string | null;
    socialLinks: Social[];
    iconMediaId: number | null;
    logoMediaId: number | null;
    ogImageMediaId: number | null;
    themeColor: string;
    matomoEnabled: boolean;
    matomoUrl: string;
    matomoSiteId: string | number;
    matomoContainerId: string;
    /** Whether a token is stored — the value itself is never sent here. */
    matomoTokenSet: boolean;
    /** Locale codes shown in the public language switcher. */
    enabledLocales: string[];
}

/**
 * The form omits the read-only `matomoTokenSet` and adds the write-only token
 * controls (a blank token keeps the stored one; the clear flag removes it).
 */
type SettingsForm = Omit<SettingsData, 'matomoTokenSet'> & {
    matomoToken: string;
    matomoTokenClear: boolean;
};

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
    const form = useForm<SettingsForm>({
        siteShortName: settings.siteShortName ?? {},
        siteTagline: settings.siteTagline ?? {},
        siteSeoDescription: settings.siteSeoDescription ?? {},
        footerNote: settings.footerNote ?? {},
        contactEmail: settings.contactEmail ?? '',
        contactWhatsapp: settings.contactWhatsapp ?? '',
        contactPhones: settings.contactPhones ?? [],
        contactAddress: settings.contactAddress ?? '',
        socialLinks: settings.socialLinks ?? [],
        iconMediaId: settings.iconMediaId ?? null,
        logoMediaId: settings.logoMediaId ?? null,
        ogImageMediaId: settings.ogImageMediaId ?? null,
        themeColor: settings.themeColor ?? '#0057B8',
        matomoEnabled: settings.matomoEnabled ?? false,
        matomoUrl: settings.matomoUrl ?? '',
        matomoSiteId: settings.matomoSiteId ?? '',
        matomoContainerId: settings.matomoContainerId ?? '',
        matomoToken: '',
        matomoTokenClear: false,
        enabledLocales: settings.enabledLocales ?? locales.map((l) => l.code),
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
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="email">البريد الإلكتروني</Label>
                            <Input
                                id="email"
                                dir="ltr"
                                value={form.data.contactEmail}
                                onChange={(e) => form.setData('contactEmail', e.target.value)}
                            />
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="whatsapp">واتساب (بصيغة دولية)</Label>
                            <Input
                                id="whatsapp"
                                dir="ltr"
                                placeholder="+9639xxxxxxxx"
                                value={form.data.contactWhatsapp}
                                onChange={(e) => form.setData('contactWhatsapp', e.target.value)}
                            />
                            {form.errors.contactWhatsapp && (
                                <p className="text-sm text-destructive">{form.errors.contactWhatsapp}</p>
                            )}
                        </div>
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

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <div>
                        <h2 className="font-display text-lg font-bold">اللغات الظاهرة</h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            اختر اللغات التي تظهر في مبدّل اللغة أعلى الموقع. اللغات المخفية تبقى صفحاتها
                            تعمل عبر الروابط المباشرة، ويمكن تجهيز ترجمتها قبل إظهارها.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        {locales.map((locale) => {
                            const checked = locale.default || form.data.enabledLocales.includes(locale.code);

                            return (
                                <label
                                    key={locale.code}
                                    className={`flex cursor-pointer items-center gap-2 rounded-lg border px-3 py-2 text-sm ${checked ? 'border-druze-blue bg-druze-blue/5' : 'border-border'} ${locale.default ? 'cursor-not-allowed opacity-70' : ''}`}
                                >
                                    <input
                                        type="checkbox"
                                        className="size-4"
                                        checked={checked}
                                        disabled={locale.default}
                                        onChange={(e) => {
                                            const next = e.target.checked
                                                ? [...form.data.enabledLocales, locale.code]
                                                : form.data.enabledLocales.filter((c) => c !== locale.code);
                                            form.setData('enabledLocales', Array.from(new Set(next)));
                                        }}
                                    />
                                    {locale.name}
                                    {locale.default && <span className="text-xs text-muted-foreground">(افتراضي)</span>}
                                </label>
                            );
                        })}
                    </div>
                </section>

                <section className="space-y-4 rounded-xl border border-border bg-card p-5">
                    <h2 className="font-display text-lg font-bold">التحليلات (Matomo)</h2>
                    <p className="text-sm text-muted-foreground">
                        اربط الموقع بحساب Matomo لتتبّع الزيارات وعرضها في لوحة «التحليلات». يُخزَّن
                        رمز الوصول مشفّراً ويُستخدم من الخادم فقط.
                    </p>

                    <label className="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            className="size-4"
                            checked={form.data.matomoEnabled}
                            onChange={(e) => form.setData('matomoEnabled', e.target.checked)}
                        />
                        تفعيل تتبّع الزوّار على الموقع العام
                    </label>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-1.5">
                            <Label htmlFor="matomoUrl">عنوان Matomo</Label>
                            <Input
                                id="matomoUrl"
                                dir="ltr"
                                placeholder="https://matomo.druze.id/matomo/"
                                value={form.data.matomoUrl}
                                onChange={(e) => form.setData('matomoUrl', e.target.value)}
                            />
                            {form.errors.matomoUrl && (
                                <p className="text-sm text-destructive">{form.errors.matomoUrl}</p>
                            )}
                        </div>
                        <div className="space-y-1.5">
                            <Label htmlFor="matomoSiteId">معرّف الموقع (Site ID)</Label>
                            <Input
                                id="matomoSiteId"
                                dir="ltr"
                                type="number"
                                min={1}
                                placeholder="1"
                                value={form.data.matomoSiteId}
                                onChange={(e) => form.setData('matomoSiteId', e.target.value)}
                            />
                            {form.errors.matomoSiteId && (
                                <p className="text-sm text-destructive">{form.errors.matomoSiteId}</p>
                            )}
                        </div>
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="matomoContainerId">معرّف حاوية Tag Manager (اختياري)</Label>
                        <Input
                            id="matomoContainerId"
                            dir="ltr"
                            placeholder="rkUM09pP"
                            value={form.data.matomoContainerId}
                            onChange={(e) => form.setData('matomoContainerId', e.target.value)}
                        />
                        <p className="text-sm text-muted-foreground">
                            عند إدخاله، يُحمّل الموقع حاوية Matomo Tag Manager بدل المتتبّع المباشر. اتركه فارغاً لاستخدام المتتبّع الافتراضي.
                        </p>
                        {form.errors.matomoContainerId && (
                            <p className="text-sm text-destructive">{form.errors.matomoContainerId}</p>
                        )}
                    </div>

                    <div className="space-y-1.5">
                        <Label htmlFor="matomoToken">رمز الوصول (Auth Token)</Label>
                        <Input
                            id="matomoToken"
                            dir="ltr"
                            type="password"
                            autoComplete="off"
                            placeholder={settings.matomoTokenSet ? '•••••••• (محفوظ — اتركه فارغاً للإبقاء عليه)' : 'أدخل رمز الوصول'}
                            value={form.data.matomoToken}
                            onChange={(e) => form.setData('matomoToken', e.target.value)}
                            disabled={form.data.matomoTokenClear}
                        />
                        {settings.matomoTokenSet && (
                            <label className="flex items-center gap-2 text-xs text-muted-foreground">
                                <input
                                    type="checkbox"
                                    className="size-3.5"
                                    checked={form.data.matomoTokenClear}
                                    onChange={(e) => form.setData('matomoTokenClear', e.target.checked)}
                                />
                                إزالة الرمز المحفوظ
                            </label>
                        )}
                        {form.errors.matomoToken && (
                            <p className="text-sm text-destructive">{form.errors.matomoToken}</p>
                        )}
                    </div>
                </section>
            </form>
        </AppLayout>
    );
}
