import { Link } from '@inertiajs/react';

import { DruzeStar } from '@/components/site/druze-star';
import { useI18n } from '@/hooks/use-i18n';
import type { NavItem, SiteSettings } from '@/types/site';

/**
 * The footer — dark, with the signature 4px blue top edge borrowed from
 * un.org. Two link columns reuse the navigation groupings; a contact column
 * carries the real email and phone numbers; a legal row closes it out.
 */
export function SiteFooter({
    sections,
    organization,
    settings,
}: {
    sections: NavItem[];
    organization: NavItem[];
    settings: SiteSettings;
}) {
    const { t } = useI18n();
    const year = new Date().getFullYear();
    const phones = settings['contact.phones'] ?? [];
    const social = settings['social.links'] ?? [];

    return (
        <footer className="border-t-4 border-druze-blue bg-bar-dark text-white/85">
            <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                    {/* Identity — the logo shares its source with the header
                        (settings['site.logo_url']), so changing the main logo
                        in the dashboard updates the footer automatically. */}
                    <div className="lg:col-span-1">
                        <div className="flex items-center gap-3">
                            {settings['site.logo_url'] ? (
                                <img
                                    src={settings['site.logo_url']}
                                    alt=""
                                    className="h-11 w-11 shrink-0 object-contain"
                                />
                            ) : (
                                <DruzeStar className="h-11 w-11" decorative />
                            )}
                            <span className="font-display text-base font-bold text-white">
                                {settings['site.short_name']}
                            </span>
                        </div>
                        {settings['footer.note'] && (
                            <p className="mt-4 text-sm leading-relaxed text-white/70">
                                {settings['footer.note']}
                            </p>
                        )}
                    </div>

                    <FooterColumn title={t('footer.sections')} items={sections} />
                    <FooterColumn title={t('footer.organization')} items={organization} />

                    {/* Contact */}
                    <div>
                        <FooterHeading>{t('footer.contact')}</FooterHeading>
                        <ul className="mt-4 space-y-2 text-sm">
                            {settings['contact.email'] && (
                                <li>
                                    <a
                                        href={`mailto:${settings['contact.email']}`}
                                        dir="ltr"
                                        className="text-white/80 hover:text-white hover:underline"
                                    >
                                        {settings['contact.email']}
                                    </a>
                                </li>
                            )}
                            {phones.map((phone) => (
                                <li key={phone.value} className="flex items-center gap-2">
                                    <span className="text-white/55">{phone.label}</span>
                                    <a
                                        href={`tel:${phone.value}`}
                                        dir="ltr"
                                        className="text-white/80 hover:text-white"
                                    >
                                        {phone.value}
                                    </a>
                                </li>
                            ))}
                        </ul>

                        {social.length > 0 && (
                            <div className="mt-5">
                                <p className="mb-2 text-xs font-semibold uppercase tracking-wide text-white/55">
                                    {t('footer.social_label')}
                                </p>
                                <ul className="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    {social.map((link) => (
                                        <li key={link.url}>
                                            <a
                                                href={link.url}
                                                target="_blank"
                                                rel="noreferrer noopener"
                                                className="capitalize text-white/80 hover:text-white hover:underline"
                                            >
                                                {link.platform}
                                            </a>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                        )}
                    </div>
                </div>

                {/* Legal row */}
                <div className="mt-12 flex flex-col items-start justify-between gap-3 border-t border-white/15 pt-6 text-xs text-white/60 sm:flex-row sm:items-center">
                    <p>
                        © {year} {settings['site.short_name']}. {t('footer.rights')}
                    </p>
                </div>
            </div>
        </footer>
    );
}

function FooterColumn({ title, items }: { title: string; items: NavItem[] }) {
    if (items.length === 0) {
        return null;
    }

    return (
        <div>
            <FooterHeading>{title}</FooterHeading>
            <ul className="mt-4 space-y-2 text-sm">
                {items.map((item) => (
                    <li key={item.id}>
                        <Link
                            href={item.href ?? '#'}
                            className="text-white/80 transition-colors hover:text-white hover:underline"
                        >
                            {item.label}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function FooterHeading({ children }: { children: React.ReactNode }) {
    return <h2 className="font-display text-sm font-bold text-white">{children}</h2>;
}
