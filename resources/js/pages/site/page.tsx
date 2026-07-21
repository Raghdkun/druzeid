import { Head, usePage } from '@inertiajs/react';

import { SectionRenderer } from '@/components/sections/section-renderer';
import { PageHeader } from '@/components/site/page-header';
import { SiteLayout } from '@/layouts/site-layout';
import type { PageData, PageMeta, SiteMenus, SiteSettings } from '@/types/site';

interface PageProps {
    page: PageData;
    meta: PageMeta;
    menus: SiteMenus;
    settings: SiteSettings;
    contactAction: string;
    [key: string]: unknown;
}

/**
 * The single public page renderer. Every CMS page — home, interior, contact,
 * announcement — routes through here; its template only decides whether an
 * interior <PageHeader> precedes the sections (the home page leads with its
 * own hero instead).
 */
export default function Page() {
    const { props } = usePage<PageProps>();
    const { page, meta, menus, settings, contactAction } = props;
    const flash = (props.flash as { success?: string } | undefined)?.success;

    const isHome = page.template === 'home';
    const leadsWithHero = page.sections[0]?.type === 'hero';

    const title = meta.title ?? page.title;

    return (
        <SiteLayout menus={menus} settings={settings}>
            {/* The full SEO/OG/JSON-LD block is rendered server-side in the
                Blade root view so crawlers see it without running JS. Here we
                keep only the title, which Inertia updates on SPA navigation. */}
            <Head title={title ?? undefined} />

            {/* Interior pages get a header; the home page leads with its hero. */}
            {!isHome && !leadsWithHero && (
                <PageHeader
                    title={page.title}
                    excerpt={page.excerpt}
                    image={page.heroImage}
                    accent={page.accent}
                />
            )}

            <SectionRenderer
                sections={page.sections}
                accent={page.accent}
                contactAction={contactAction}
                contactFlash={flash}
            />
        </SiteLayout>
    );
}
