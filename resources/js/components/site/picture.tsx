import { cn } from '@/lib/utils';
import type { ImageProps } from '@/types/site';

interface PictureProps {
    image: ImageProps | null | undefined;
    className?: string;
    imgClassName?: string;
    /** Above the fold — skip lazy loading and let the browser prioritise it. */
    priority?: boolean;
    /** Responsive sizes hint. */
    sizes?: string;
}

/**
 * Responsive <picture> with the safeguards un.org's bare <img> lacked.
 *
 * When derivatives exist, AVIF and WebP `<source>`s with width descriptors let
 * the browser pick the smallest sufficient file for the device — often a
 * fraction of the source JPEG's weight. The original is always the `<img>`
 * fallback. Explicit dimensions reserve space (no layout shift), focal-point
 * object-position keeps art-directed crops framed, and images lazy-load and
 * decode async by default. A decorative image arrives with alt="" and is
 * hidden from assistive tech.
 */
export function Picture({ image, className, imgClassName, priority = false, sizes }: PictureProps) {
    if (!image) {
        return null;
    }

    const decorative = image.alt === '';
    const commonImgProps = {
        src: image.url,
        alt: image.alt,
        width: image.width ?? undefined,
        height: image.height ?? undefined,
        loading: priority ? ('eager' as const) : ('lazy' as const),
        decoding: 'async' as const,
        fetchPriority: priority ? ('high' as const) : ('auto' as const),
        sizes,
        'aria-hidden': decorative || undefined,
        className: cn('h-auto max-w-full', imgClassName, className),
        style: { objectPosition: image.objectPosition },
    };

    // No derivatives (e.g. SVG): a plain <img> is enough.
    if (!image.srcsetWebp && !image.srcsetAvif) {
        return <img {...commonImgProps} />;
    }

    return (
        <picture>
            {image.srcsetAvif && <source type="image/avif" srcSet={image.srcsetAvif} sizes={sizes} />}
            {image.srcsetWebp && <source type="image/webp" srcSet={image.srcsetWebp} sizes={sizes} />}
            <img {...commonImgProps} />
        </picture>
    );
}
