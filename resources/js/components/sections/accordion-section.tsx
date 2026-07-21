import { useState } from 'react';

import { ProseHtml } from '@/components/site/prose-html';
import { SectionHeading, SectionShell } from '@/components/site/section-shell';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import type { AccordionSection as AccordionData } from '@/types/site';

/**
 * The planned-sections accordion used by the five "under construction" pages —
 * e.g. the fifteen collections the Archives will hold. Each item is an
 * accessible disclosure (Radix Collapsible) with a rotating chevron and a
 * start-anchored accent rule.
 */
export function AccordionSection({
    section,
    tone,
}: {
    section: AccordionData;
    tone?: 'default' | 'alt' | 'tint';
}) {
    return (
        <SectionShell tone={tone ?? 'default'}>
            {section.heading && (
                <SectionHeading align="start">{section.heading}</SectionHeading>
            )}

            <div className="divide-y divide-hairline border-y border-hairline">
                {section.items.map((item, index) => (
                    <AccordionRow key={index} title={item.title} defaultOpen={index === 0}>
                        <ProseHtml html={item.html} />
                    </AccordionRow>
                ))}
            </div>
        </SectionShell>
    );
}

function AccordionRow({
    title,
    defaultOpen,
    children,
}: {
    title: string;
    defaultOpen: boolean;
    children: React.ReactNode;
}) {
    const [open, setOpen] = useState(defaultOpen);

    return (
        <Collapsible open={open} onOpenChange={setOpen}>
            <CollapsibleTrigger className="group flex w-full items-center justify-between gap-4 py-5 text-start">
                <span className="flex items-center gap-3">
                    <span
                        aria-hidden
                        className="h-5 w-[3px] shrink-0 bg-druze-blue transition-all group-data-[state=open]:h-7"
                    />
                    <span className="font-display text-lg font-semibold text-ink">{title}</span>
                </span>
                <svg
                    width="20"
                    height="20"
                    viewBox="0 0 24 24"
                    fill="none"
                    aria-hidden
                    className="shrink-0 text-ink-muted transition-transform duration-200 group-data-[state=open]:rotate-180"
                >
                    <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </CollapsibleTrigger>
            <CollapsibleContent className="overflow-hidden data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
                <div className="pb-6 ps-6">{children}</div>
            </CollapsibleContent>
        </Collapsible>
    );
}
