import { Headset, Mail, MessageCircle, Phone, X } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { SiteSettings } from '@/types/site';

/**
 * A floating "contact us" bubble, like the old site's — a fixed action button
 * at the inline-end/bottom that expands to direct-contact actions: a phone
 * call, WhatsApp and email. Each action only appears when its detail is set in
 * the CMS. Positioned with logical properties so it sits bottom-left in the
 * RTL Arabic layout and bottom-right in LTR.
 */
export function ContactBubble({ settings }: { settings: SiteSettings }) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);

    const phone = settings['contact.phones']?.[0]?.value ?? null;
    const whatsapp = settings['contact.whatsapp'];
    const email = settings['contact.email'];

    const actions = [
        phone && {
            key: 'call',
            href: `tel:${phone.replace(/\s+/g, '')}`,
            label: t('contact.call'),
            icon: Phone,
            className: 'bg-druze-blue',
        },
        whatsapp && {
            key: 'whatsapp',
            href: `https://wa.me/${whatsapp.replace(/[^0-9]/g, '')}`,
            label: t('contact.whatsapp'),
            icon: MessageCircle,
            className: 'bg-[#25d366]',
            external: true,
        },
        email && {
            key: 'email',
            href: `mailto:${email}`,
            label: t('contact.email'),
            icon: Mail,
            className: 'bg-druze-blue-deep',
        },
    ].filter(Boolean) as Array<{
        key: string;
        href: string;
        label: string;
        icon: typeof Phone;
        className: string;
        external?: boolean;
    }>;

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [open]);

    if (actions.length === 0) {
        return null;
    }

    return (
        <div className="fixed bottom-5 end-5 z-40 flex flex-col items-end gap-3 print:hidden">
            {open && (
                <ul className="flex flex-col items-end gap-3">
                    {actions.map((action) => (
                        <li key={action.key}>
                            <a
                                href={action.href}
                                target={action.external ? '_blank' : undefined}
                                rel={action.external ? 'noopener noreferrer' : undefined}
                                className="group flex items-center gap-2"
                            >
                                <span className="rounded-full bg-bar-dark/90 px-3 py-1 text-sm font-medium text-white opacity-0 shadow transition-opacity group-hover:opacity-100 group-focus-visible:opacity-100">
                                    {action.label}
                                </span>
                                <span
                                    className={cn(
                                        'flex size-12 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105',
                                        action.className,
                                    )}
                                >
                                    <action.icon className="size-5" aria-hidden />
                                    <span className="sr-only">{action.label}</span>
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            )}

            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                aria-label={t('contact.bubble_label')}
                className="flex size-14 items-center justify-center rounded-full bg-druze-blue text-white shadow-xl ring-4 ring-druze-blue/20 transition-transform hover:scale-105"
            >
                {open ? <X className="size-6" aria-hidden /> : <Headset className="size-6" aria-hidden />}
            </button>
        </div>
    );
}
