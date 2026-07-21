import { SectionCard } from '@/components/site/section-card';
import { SectionHeading, SectionShell } from '@/components/site/section-shell';
import type { CardGridSection as CardGridData } from '@/types/site';

/**
 * The "site sections" grid — the home page's map of the six project areas.
 * Columns go 1 → 2 → 3 (never 1 → 1 → 3, the un.org homepage bug) so tablet
 * always shows two.
 */
export function CardGridSection({
    section,
    tone,
}: {
    section: CardGridData;
    tone?: 'default' | 'alt' | 'tint';
}) {
    return (
        <SectionShell id={section.anchor} tone={tone ?? 'alt'}>
            {section.heading && <SectionHeading>{section.heading}</SectionHeading>}

            {section.intro && (
                <p className="mx-auto -mt-4 mb-10 max-w-2xl text-center text-ink-muted">
                    {section.intro}
                </p>
            )}

            <div className="grid grid-cols-1 gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
                {section.cards.map((card) => (
                    <SectionCard key={card.slug} card={card} />
                ))}
            </div>
        </SectionShell>
    );
}
