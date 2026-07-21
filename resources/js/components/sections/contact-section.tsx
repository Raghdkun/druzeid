import { useForm } from '@inertiajs/react';
import { useState } from 'react';

import { ProseHtml } from '@/components/site/prose-html';
import { SectionShell } from '@/components/site/section-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useI18n } from '@/hooks/use-i18n';
import type { ContactSection as ContactData } from '@/types/site';

/**
 * The contact section — structured, actionable details plus a Laravel-native
 * form that replaces the original site's third-party widget. The form carries
 * a honeypot (`website`) and a render timestamp the server uses to reject
 * bots; neither is visible to a real visitor.
 */
export function ContactSection({
    section,
    action,
    flash,
    tone,
}: {
    section: ContactData;
    action: string;
    flash?: string;
    tone?: 'default' | 'alt' | 'tint';
}) {
    const { t } = useI18n();
    // Stamp the render time once (lazy state initializer, not a ref read
    // during render) — the server rejects submissions completed too fast.
    const [renderedAt] = useState(() => Math.floor(Date.now() / 1000));

    const form = useForm({
        name: '',
        email: '',
        subject: '',
        message: '',
        website: '', // honeypot
        rendered_at: renderedAt,
    });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        form.post(action, {
            preserveScroll: true,
            onSuccess: () => form.reset('name', 'email', 'subject', 'message'),
        });
    };

    return (
        <SectionShell tone={tone ?? 'default'}>
            <div className="grid gap-12 lg:grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)]">
                {/* Details */}
                <div>
                    {section.html && (
                        <div className="mb-8">
                            <ProseHtml html={section.html} />
                        </div>
                    )}

                    <dl className="space-y-4">
                        {section.email && (
                            <ContactRow label={t('contact.email')}>
                                <a
                                    href={`mailto:${section.email}`}
                                    dir="ltr"
                                    className="font-medium text-druze-blue hover:underline"
                                >
                                    {section.email}
                                </a>
                            </ContactRow>
                        )}
                        {section.phones.map((phone) => (
                            <ContactRow key={phone} label={t('contact.title')}>
                                <a
                                    href={`tel:${phone}`}
                                    dir="ltr"
                                    className="font-medium text-ink hover:text-druze-blue"
                                >
                                    {phone}
                                </a>
                            </ContactRow>
                        ))}
                    </dl>
                </div>

                {/* Form */}
                {section.showForm && (
                    <div className="border-s-0 lg:border-s lg:border-hairline lg:ps-12">
                        {flash && (
                            <div
                                role="status"
                                className="mb-6 rounded-md border-s-4 border-druze-green bg-[color-mix(in_srgb,var(--color-druze-green)_8%,white)] px-4 py-3 text-sm text-ink"
                            >
                                {flash}
                            </div>
                        )}

                        <form onSubmit={submit} className="space-y-5" noValidate>
                            <Field
                                id="name"
                                label={t('contact.name')}
                                value={form.data.name}
                                onChange={(v) => form.setData('name', v)}
                                error={form.errors.name}
                                required
                            />
                            <Field
                                id="email"
                                type="email"
                                label={t('contact.email')}
                                value={form.data.email}
                                onChange={(v) => form.setData('email', v)}
                                error={form.errors.email}
                                dir="ltr"
                                required
                            />
                            <Field
                                id="subject"
                                label={t('contact.subject')}
                                value={form.data.subject}
                                onChange={(v) => form.setData('subject', v)}
                                error={form.errors.subject}
                            />

                            <div className="space-y-1.5">
                                <Label htmlFor="message">
                                    {t('contact.message')} <RequiredMark />
                                </Label>
                                <textarea
                                    id="message"
                                    rows={5}
                                    value={form.data.message}
                                    onChange={(e) => form.setData('message', e.target.value)}
                                    aria-invalid={!!form.errors.message}
                                    aria-describedby={form.errors.message ? 'message-error' : undefined}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-xs focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                />
                                {form.errors.message && (
                                    <p id="message-error" className="text-sm text-destructive">
                                        {form.errors.message}
                                    </p>
                                )}
                            </div>

                            {/* Honeypot — visually and programmatically hidden from users. */}
                            <div aria-hidden className="hidden">
                                <label htmlFor="website">Website</label>
                                <input
                                    id="website"
                                    type="text"
                                    tabIndex={-1}
                                    autoComplete="off"
                                    value={form.data.website}
                                    onChange={(e) => form.setData('website', e.target.value)}
                                />
                            </div>

                            <Button type="submit" disabled={form.processing} size="lg">
                                {form.processing ? t('contact.sending') : t('contact.send')}
                            </Button>
                        </form>
                    </div>
                )}
            </div>
        </SectionShell>
    );
}

function ContactRow({ label, children }: { label: string; children: React.ReactNode }) {
    return (
        <div className="flex flex-col gap-0.5 border-s-2 border-hairline ps-4">
            <dt className="text-xs font-semibold uppercase tracking-wide text-ink-eyebrow">
                {label}
            </dt>
            <dd>{children}</dd>
        </div>
    );
}

function Field({
    id,
    label,
    value,
    onChange,
    error,
    type = 'text',
    dir,
    required,
}: {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
    error?: string;
    type?: string;
    dir?: 'ltr' | 'rtl';
    required?: boolean;
}) {
    return (
        <div className="space-y-1.5">
            <Label htmlFor={id}>
                {label} {required && <RequiredMark />}
            </Label>
            <Input
                id={id}
                type={type}
                dir={dir}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                aria-invalid={!!error}
                aria-describedby={error ? `${id}-error` : undefined}
            />
            {error && (
                <p id={`${id}-error`} className="text-sm text-destructive">
                    {error}
                </p>
            )}
        </div>
    );
}

function RequiredMark() {
    const { t } = useI18n();

    return (
        <span className="text-destructive" title={t('contact.required')}>
            *
        </span>
    );
}
