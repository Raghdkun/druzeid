import { Facebook, Globe, Instagram, Linkedin, Send, Share2, Twitter, X, Youtube } from 'lucide-react';
import { useEffect, useState } from 'react';

import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { SiteSettings } from '@/types/site';

/** Platform → icon + brand colour. Unknown platforms fall back to a globe. */
const PLATFORMS: Record<string, { icon: typeof Facebook; color: string; label: string }> = {
    facebook: { icon: Facebook, color: 'bg-[#1877f2]', label: 'Facebook' },
    instagram: { icon: Instagram, color: 'bg-[#e4405f]', label: 'Instagram' },
    twitter: { icon: Twitter, color: 'bg-[#1da1f2]', label: 'Twitter' },
    x: { icon: Twitter, color: 'bg-black', label: 'X' },
    youtube: { icon: Youtube, color: 'bg-[#ff0000]', label: 'YouTube' },
    linkedin: { icon: Linkedin, color: 'bg-[#0a66c2]', label: 'LinkedIn' },
    telegram: { icon: Send, color: 'bg-[#26a5e4]', label: 'Telegram' },
};

/**
 * A floating social-media bubble at the inline-start/bottom of the public page
 * (the physical right in the RTL Arabic layout). Expands to the organisation's
 * social links, which are edited in the dashboard (the `social.links` setting).
 * Renders nothing until at least one link is configured.
 */
export function SocialBubble({ settings }: { settings: SiteSettings }) {
    const { t } = useI18n();
    const [open, setOpen] = useState(false);
    const links = (settings['social.links'] ?? []).filter((l) => l.url);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onKey = (event: KeyboardEvent) => event.key === 'Escape' && setOpen(false);
        document.addEventListener('keydown', onKey);

        return () => document.removeEventListener('keydown', onKey);
    }, [open]);

    if (links.length === 0) {
        return null;
    }

    return (
        <div className="fixed bottom-5 start-5 z-40 flex flex-col items-start gap-3 print:hidden">
            {open && (
                <ul className="flex flex-col items-start gap-3">
                    {links.map((link, index) => {
                        const meta = PLATFORMS[link.platform.toLowerCase()] ?? {
                            icon: Globe,
                            color: 'bg-druze-blue',
                            label: link.platform,
                        };

                        return (
                            <li key={`${link.platform}-${index}`}>
                                <a
                                    href={link.url}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    aria-label={meta.label}
                                    className={cn(
                                        'flex size-12 items-center justify-center rounded-full text-white shadow-lg transition-transform hover:scale-105',
                                        meta.color,
                                    )}
                                >
                                    <meta.icon className="size-5" aria-hidden />
                                    <span className="sr-only">{meta.label}</span>
                                </a>
                            </li>
                        );
                    })}
                </ul>
            )}

            <button
                type="button"
                onClick={() => setOpen((v) => !v)}
                aria-expanded={open}
                aria-label={t('footer.social_label')}
                className="flex size-14 items-center justify-center rounded-full bg-druze-blue text-white shadow-xl ring-4 ring-druze-blue/20 transition-transform hover:scale-105"
            >
                {open ? <X className="size-6" aria-hidden /> : <Share2 className="size-6" aria-hidden />}
            </button>
        </div>
    );
}
