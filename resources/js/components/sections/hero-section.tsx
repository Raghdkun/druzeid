import { Link } from '@inertiajs/react';
import { useEffect, useState } from 'react';

import { BrandLockup } from '@/components/site/brand-lockup';
import { Picture } from '@/components/site/picture';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { HeroSection as HeroData } from '@/types/site';

/**
 * Full-bleed hero with a slow cross-fade between the historic photographs the
 * original site rotated. A dark scrim keeps the white display type legible
 * over any frame; the images are decorative here (the headline carries the
 * meaning) so they take empty alt.
 */
export function HeroSection({ section }: { section: HeroData }) {
    const { isRtl } = useI18n();
    const images = section.images.length > 0 ? section.images : [];
    const [active, setActive] = useState(0);

    useEffect(() => {
        if (images.length < 2) {
            return;
        }

        const timer = setInterval(() => {
            setActive((current) => (current + 1) % images.length);
        }, 6000);

        return () => clearInterval(timer);
    }, [images.length]);

    return (
        <section className="relative isolate flex min-h-[70vh] items-center overflow-hidden bg-bar-dark">
            {/* Background rotation */}
            <div aria-hidden className="absolute inset-0 -z-10">
                {images.map((image, index) => (
                    <Picture
                        key={image.id}
                        image={{ ...image, alt: '' }}
                        priority={index === 0}
                        sizes="100vw"
                        className={cn(
                            'absolute inset-0 h-full w-full object-cover transition-opacity duration-1000 ease-in-out',
                            index === active ? 'opacity-100' : 'opacity-0',
                        )}
                    />
                ))}
                <div className="absolute inset-0 bg-black/55" />
            </div>

            <div className="mx-auto w-full max-w-6xl px-4 py-24 text-center sm:px-6 lg:px-8">
                {/* The emblem sits within the image frame; the name is the h1. */}
                <div className="mb-6 flex justify-center">
                    <BrandLockup variant="emblem" />
                </div>
                <h1 className="mx-auto max-w-3xl font-display text-4xl font-black text-white drop-shadow-sm sm:text-5xl lg:text-6xl">
                    {section.title}
                </h1>

                {section.subtitle && (
                    <p className="mx-auto mt-6 max-w-2xl text-lg text-white/90 sm:text-xl">
                        {section.subtitle}
                    </p>
                )}

                {section.actions.length > 0 && (
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        {section.actions.map((action, index) => (
                            <HeroButton
                                key={index}
                                href={action.href ?? '#'}
                                primary={index === 0}
                            >
                                {action.label}
                            </HeroButton>
                        ))}
                    </div>
                )}
            </div>

            {/* Scroll cue */}
            <span
                aria-hidden
                className={cn(
                    'absolute bottom-6 start-1/2 -translate-x-1/2 text-white/70',
                    isRtl && 'translate-x-1/2',
                )}
            >
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" className="animate-bounce">
                    <path d="M6 9l6 6 6-6" stroke="currentColor" strokeWidth="2" strokeLinecap="round" />
                </svg>
            </span>
        </section>
    );
}

function HeroButton({
    href,
    primary,
    children,
}: {
    href: string;
    primary: boolean;
    children: React.ReactNode;
}) {
    const classes = cn(
        'inline-flex items-center justify-center rounded-full border-2 px-7 py-2.5 text-base font-semibold transition-colors',
        primary
            ? 'border-white bg-white text-bar-dark hover:bg-white/90'
            : 'border-white/80 text-white hover:bg-white/10',
    );

    // In-page anchors use a plain link; page URLs use Inertia navigation.
    if (href.startsWith('#')) {
        return (
            <a href={href} className={classes}>
                {children}
            </a>
        );
    }

    return (
        <Link href={href} className={classes}>
            {children}
        </Link>
    );
}
