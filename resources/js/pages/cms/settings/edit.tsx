import { Head, useForm } from '@inertiajs/react';
import { Plus, Save, Trash2 } from 'lucide-react';

import type { LocaleMeta } from '@/components/cms/locale-badges';
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
    footerNote: Record<string, string>;
    contactEmail: string;
    contactPhones: Phone[];
    contactAddress: string | null;
    socialLinks: Social[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'لوحة التحكم', href: '/cms' },
    { title: 'الإعدادات', href: '/cms/settings' },
];

export default function SettingsEdit({
    settings,
    locales,
}: {
    settings: SettingsData;
    locales: LocaleMeta[];
}) {
    const form = useForm<SettingsData>({
        siteShortName: settings.siteShortName ?? {},
        siteTagline: settings.siteTagline ?? {},
        footerNote: settings.footerNote ?? {},
        contactEmail: settings.contactEmail ?? '',
        contactPhones: settings.contactPhones ?? [],
        contactAddress: settings.contactAddress ?? '',
        socialLinks: settings.socialLinks ?? [],
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
