import { AccordionSection } from '@/components/sections/accordion-section';
import { CardGridSection } from '@/components/sections/card-grid-section';
import { ContactSection } from '@/components/sections/contact-section';
import { GallerySection } from '@/components/sections/gallery-section';
import { HeroSection } from '@/components/sections/hero-section';
import { RichTextSection } from '@/components/sections/rich-text-section';
import { UnderConstructionSection } from '@/components/sections/under-construction-section';
import { VideoSection } from '@/components/sections/video-section';
import type { Accent, Section } from '@/types/site';

/**
 * Maps a section's `type` to its component — the single place that grows when
 * a new section type is added (one component, one case here). The hero spans
 * full width; everything else is banded, and the band alternates by the
 * section's position among the banded ones.
 */
export function SectionRenderer({
    sections,
    accent,
    contactAction,
    contactFlash,
}: {
    sections: Section[];
    accent: Accent;
    contactAction: string;
    contactFlash?: string;
}) {
    // Band tone alternates across the non-hero sections only, so the
    // white/grey rhythm reads cleanly regardless of where the hero sits.
    // Precomputed as a pure map — no mutable counter during render.
    const bandTone = new Map<number, 'default' | 'alt'>();
    let band = 0;

    for (const section of sections) {
        if (section.type !== 'hero') {
            bandTone.set(section.id, band % 2 === 0 ? 'default' : 'alt');
            band += 1;
        }
    }

    return (
        <>
            {sections.map((section) => {
                if (section.type === 'hero') {
                    return <HeroSection key={section.id} section={section} />;
                }

                const tone = bandTone.get(section.id) ?? 'default';

                switch (section.type) {
                    case 'rich_text':
                        return <RichTextSection key={section.id} section={section} tone={tone} />;
                    case 'card_grid':
                        return <CardGridSection key={section.id} section={section} tone={tone} />;
                    case 'accordion':
                        return <AccordionSection key={section.id} section={section} tone={tone} />;
                    case 'under_construction':
                        return (
                            <UnderConstructionSection
                                key={section.id}
                                section={section}
                                accent={accent}
                                tone={tone}
                            />
                        );
                    case 'gallery':
                        return <GallerySection key={section.id} section={section} tone={tone} />;
                    case 'video':
                        return <VideoSection key={section.id} section={section} tone={tone} />;
                    case 'contact':
                        return (
                            <ContactSection
                                key={section.id}
                                section={section}
                                action={contactAction}
                                flash={contactFlash}
                                tone={tone}
                            />
                        );
                    default:
                        return null;
                }
            })}
        </>
    );
}
