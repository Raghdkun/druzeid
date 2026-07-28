import { ProseHtml } from '@/components/site/prose-html';
import { SectionShell } from '@/components/site/section-shell';
import type { RichTextSection as RichTextData } from '@/types/site';

/**
 * A prose section. The `feature` variant (used for the home page's "why a
 * global identity" essay) centres the column and sits on the alternate band
 * to set it apart.
 */
export function RichTextSection({
    section,
    tone,
}: {
    section: RichTextData;
    tone?: 'default' | 'alt' | 'tint';
}) {
    const feature = section.variant === 'feature';

    // The feature essay reads as its own panel — a tinted reading surface a few
    // shades off the page ground, per the client's request for a distinct area
    // for written content.
    if (feature) {
        return (
            <SectionShell tone={tone ?? 'alt'}>
                <div className="mx-auto max-w-3xl rounded-2xl bg-druze-blue-tint/50 p-6 sm:p-10">
                    <ProseHtml html={section.html} />
                </div>
            </SectionShell>
        );
    }

    return (
        <SectionShell tone={tone ?? 'default'}>
            <ProseHtml html={section.html} />
        </SectionShell>
    );
}
