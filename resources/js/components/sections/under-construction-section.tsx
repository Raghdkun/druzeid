import { Picture } from '@/components/site/picture';
import { ProseHtml } from '@/components/site/prose-html';
import { SectionShell } from '@/components/site/section-shell';
import { useI18n } from '@/hooks/use-i18n';
import { accentClasses } from '@/lib/accent';
import { cn } from '@/lib/utils';
import type { Accent, UnderConstructionSection as UcData } from '@/types/site';

/**
 * The institutional "coming soon" treatment for the five announcement pages.
 *
 * The original site rendered these as flat prose with a tatweel-stretched
 * "قيد الإنشاء". Here the status becomes an honest, dignified banner: what the
 * planned site is, the domain it will live at, and its purpose — styled with
 * the page's category accent so each of the five reads as distinct.
 */
export function UnderConstructionSection({
    section,
    accent,
    tone,
}: {
    section: UcData;
    accent: Accent;
    tone?: 'default' | 'alt' | 'tint';
}) {
    const { t } = useI18n();
    const accentClass = accentClasses(accent);

    return (
        <SectionShell tone={tone ?? 'default'}>
            <div className="grid items-center gap-10 lg:grid-cols-2">
                <div>
                    <span
                        className={cn(
                            'inline-flex items-center gap-2 rounded-full px-4 py-1.5 text-sm font-semibold',
                            accentClass.tint,
                            accentClass.text,
                        )}
                    >
                        <span className={cn('h-2 w-2 rounded-full', accentClass.rule)} aria-hidden />
                        {t('status.under_construction')}
                    </span>

                    <div className="mt-6">
                        <ProseHtml html={section.html} />
                    </div>

                    {section.domain && (
                        <p className="mt-6">
                            <span
                                dir="ltr"
                                className={cn(
                                    'inline-block border-s-4 bg-surface-alt px-4 py-2 font-mono text-sm font-semibold',
                                    accentClass.edge,
                                    accentClass.text,
                                )}
                            >
                                {section.domain}
                            </span>
                        </p>
                    )}
                </div>

                {section.image && (
                    <div className="relative aspect-[4/3] overflow-hidden bg-surface-alt">
                        <Picture
                            image={section.image}
                            sizes="(max-width: 1024px) 100vw, 50vw"
                            className="h-full w-full object-cover"
                        />
                    </div>
                )}
            </div>
        </SectionShell>
    );
}
