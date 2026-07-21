import { Picture } from '@/components/site/picture';
import { SectionShell } from '@/components/site/section-shell';
import type { GallerySection as GalleryData } from '@/types/site';

/**
 * A photo grid for pages that carry their own imagery. Captions and credits
 * show beneath each frame when present; images with real alt text stay
 * described for assistive tech.
 */
export function GallerySection({
    section,
    tone,
}: {
    section: GalleryData;
    tone?: 'default' | 'alt' | 'tint';
}) {
    if (section.images.length === 0) {
        return null;
    }

    return (
        <SectionShell tone={tone ?? 'alt'}>
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                {section.images.map((image) => (
                    <figure key={image.id} className="space-y-2">
                        <div className="aspect-square overflow-hidden bg-surface-sunken">
                            <Picture
                                image={image}
                                sizes="(max-width: 640px) 50vw, (max-width: 1024px) 33vw, 25vw"
                                className="h-full w-full object-cover transition-transform duration-500 hover:scale-105"
                            />
                        </div>
                        {(image.caption || image.credit) && (
                            <figcaption className="text-xs text-ink-muted">
                                {image.caption}
                                {image.credit && <span className="block opacity-75">{image.credit}</span>}
                            </figcaption>
                        )}
                    </figure>
                ))}
            </div>
        </SectionShell>
    );
}
