import { SectionHeading, SectionShell } from '@/components/site/section-shell';
import { VideoPlayer } from '@/components/site/video-player';
import type { VideoSection as VideoData } from '@/types/site';

/**
 * A single embedded video, centred at reading width. The heading takes the
 * usual sectioned treatment; the player carries its own caption and credit.
 */
export function VideoSection({
    section,
    tone,
}: {
    section: VideoData;
    tone?: 'default' | 'alt' | 'tint';
}) {
    if (!section.image) {
        return null;
    }

    return (
        <SectionShell tone={tone}>
            {section.heading && <SectionHeading>{section.heading}</SectionHeading>}
            <div className="mx-auto max-w-3xl">
                <VideoPlayer
                    media={{ ...section.image, caption: section.caption ?? section.image.caption }}
                    autoplay={section.autoplay}
                />
            </div>
        </SectionShell>
    );
}
