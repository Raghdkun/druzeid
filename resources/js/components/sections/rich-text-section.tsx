import { ProseHtml } from '@/components/site/prose-html';
import { SectionShell } from '@/components/site/section-shell';
import { cn } from '@/lib/utils';
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

    return (
        <SectionShell tone={tone ?? (feature ? 'alt' : 'default')}>
            <ProseHtml html={section.html} className={cn(feature && 'mx-auto max-w-3xl')} />
        </SectionShell>
    );
}
