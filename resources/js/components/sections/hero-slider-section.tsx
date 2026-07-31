import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight, Pause, Play } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

import { Picture } from '@/components/site/picture';
import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';
import type { HeroSliderSection as HeroSliderData, HeroSlide } from '@/types/site';

const INTERVAL = 6500;

/**
 * The homepage hero as an accessible, auto-advancing slide deck. Each slide is
 * a full-bleed image (or a brand panel when it has none) under a dark scrim,
 * carrying a headline, optional subtitle and up to two buttons. Manual slides
 * are authored in the CMS; dynamic slides pull their content from a linked
 * article, category or page. Motion pauses under `prefers-reduced-motion`, on
 * hover, and on focus — and can be toggled off entirely.
 */
export function HeroSliderSection({ section }: { section: HeroSliderData }) {
    const { t } = useI18n();
    const slides = section.slides ?? [];
    const count = slides.length;

    const [active, setActive] = useState(0);
    const [paused, setPaused] = useState(false);
    const [hovered, setHovered] = useState(false);
    // Seed from the current preference so the first render is correct (SSR-safe).
    const [motionOk, setMotionOk] = useState(() =>
        typeof window === 'undefined' ? true : ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    );

    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const onChange = () => setMotionOk(!query.matches);
        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, []);

    const go = useCallback(
        (index: number) => setActive((count + (index % count)) % count),
        [count],
    );

    const rootRef = useRef<HTMLElement>(null);
    const autoplay = motionOk && !paused && !hovered && count > 1;

    useEffect(() => {
        if (!autoplay) {
            return;
        }

        const timer = setInterval(() => setActive((c) => (c + 1) % count), INTERVAL);

        return () => clearInterval(timer);
    }, [autoplay, count]);

    // Pause auto-advance whenever focus is inside the carousel (keyboard users).
    useEffect(() => {
        const node = rootRef.current;

        if (!node) {
            return;
        }

        const onFocusIn = () => setHovered(true);
        const onFocusOut = (e: FocusEvent) => {
            if (!node.contains(e.relatedTarget as Node | null)) {
                setHovered(false);
            }
        };
        node.addEventListener('focusin', onFocusIn);
        node.addEventListener('focusout', onFocusOut);

        return () => {
            node.removeEventListener('focusin', onFocusIn);
            node.removeEventListener('focusout', onFocusOut);
        };
    }, []);

    if (count === 0) {
        return null;
    }

    const onKeyDown = (e: React.KeyboardEvent) => {
        // Match the visual direction: ArrowRight advances in LTR, precedes in RTL.
        const rtl = document.dir === 'rtl';

        if (e.key === 'ArrowRight') {
            go(active + (rtl ? -1 : 1));
        } else if (e.key === 'ArrowLeft') {
            go(active + (rtl ? 1 : -1));
        }
    };

    return (
        <section
            ref={rootRef}
            aria-roledescription="carousel"
            aria-label={t('slider.label')}
            onMouseEnter={() => setHovered(true)}
            onMouseLeave={() => setHovered(false)}
            onKeyDown={onKeyDown}
            className="relative isolate flex min-h-[60vh] items-center overflow-hidden bg-bar-dark sm:min-h-[70vh]"
        >
            {/* Live region announces the current slide for screen readers. */}
            <div aria-live="polite" aria-atomic className="sr-only">
                {t('slider.status', { current: String(active + 1), total: String(count) })}
            </div>

            {slides.map((slide, index) => (
                <Slide key={index} slide={slide} isActive={index === active} position={index} total={count} />
            ))}

            {count > 1 && (
                <>
                    <NavButton edge="start" label={t('slider.previous')} onClick={() => go(active - 1)} />
                    <NavButton edge="end" label={t('slider.next')} onClick={() => go(active + 1)} />

                    <div className="absolute inset-x-0 bottom-5 z-10 flex items-center justify-center gap-3">
                        <button
                            type="button"
                            onClick={() => setPaused((p) => !p)}
                            aria-label={paused || !motionOk ? t('slider.play') : t('slider.pause')}
                            className="inline-flex size-8 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white"
                        >
                            {paused || !motionOk ? <Play className="size-4" /> : <Pause className="size-4" />}
                        </button>

                        <div className="flex items-center gap-2" role="tablist" aria-label={t('slider.label')}>
                            {slides.map((_, index) => (
                                <button
                                    key={index}
                                    type="button"
                                    role="tab"
                                    aria-selected={index === active}
                                    aria-label={t('slider.go_to_slide', { number: String(index + 1) })}
                                    onClick={() => go(index)}
                                    className={cn(
                                        'h-2 rounded-full transition-all duration-300 ease-out focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
                                        index === active ? 'w-6 bg-white' : 'w-2 bg-white/45 hover:bg-white/70',
                                    )}
                                />
                            ))}
                        </div>
                    </div>
                </>
            )}
        </section>
    );
}

function Slide({
    slide,
    isActive,
    position,
    total,
}: {
    slide: HeroSlide;
    isActive: boolean;
    position: number;
    total: number;
}) {
    const { t } = useI18n();
    const heading = slide.title ?? '';

    return (
        <div
            role="group"
            aria-roledescription="slide"
            aria-label={t('slider.status', { current: String(position + 1), total: String(total) })}
            aria-hidden={!isActive}
            inert={!isActive || undefined}
            className={cn(
                'absolute inset-0 transition-opacity duration-700 ease-out',
                isActive ? 'opacity-100' : 'pointer-events-none opacity-0',
            )}
        >
            {/* Background: image + scrim, or a brand panel when there is none. */}
            <div aria-hidden className="absolute inset-0 -z-10">
                {slide.image ? (
                    <Picture
                        image={{ ...slide.image, alt: '' }}
                        priority={position === 0}
                        sizes="100vw"
                        className="absolute inset-0 h-full w-full object-cover"
                    />
                ) : (
                    <div className="absolute inset-0 bg-gradient-to-br from-bar-dark to-druze-blue-deep" />
                )}
                <div className="absolute inset-0 bg-black/55" />
            </div>

            <div className="mx-auto flex min-h-[60vh] w-full max-w-6xl flex-col items-center justify-center px-4 py-20 text-center sm:min-h-[70vh] sm:px-6 lg:px-8">
                {heading &&
                    (slide.href ? (
                        <Link href={slide.href} className="group/heading rounded-sm focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-white">
                            <SlideHeading>{heading}</SlideHeading>
                        </Link>
                    ) : (
                        <SlideHeading>{heading}</SlideHeading>
                    ))}

                {slide.subtitle && (
                    <p className="mx-auto mt-6 max-w-2xl text-lg text-white/90 sm:text-xl">{slide.subtitle}</p>
                )}

                {slide.actions.length > 0 && (
                    <div className="mt-10 flex flex-wrap items-center justify-center gap-4">
                        {slide.actions.map((action, index) => (
                            <SlideButton key={index} href={action.href || '#'} primary={index === 0}>
                                {action.label}
                            </SlideButton>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}

function SlideHeading({ children }: { children: React.ReactNode }) {
    return (
        <h2 className="mx-auto max-w-3xl font-display text-4xl font-black text-white drop-shadow-sm group-hover/heading:underline sm:text-5xl lg:text-6xl">
            {children}
        </h2>
    );
}

function NavButton({ edge, label, onClick }: { edge: 'start' | 'end'; label: string; onClick: () => void }) {
    // The visual arrow points outward on each edge and flips under RTL.
    const forward = edge === 'end';

    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className={cn(
                'absolute top-1/2 z-10 hidden size-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/15 text-white backdrop-blur-sm transition-colors hover:bg-white/25 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white sm:inline-flex',
                edge === 'start' ? 'start-4' : 'end-4',
            )}
        >
            {forward ? (
                <>
                    <ChevronRight className="size-6 rtl:hidden" aria-hidden />
                    <ChevronLeft className="hidden size-6 rtl:block" aria-hidden />
                </>
            ) : (
                <>
                    <ChevronLeft className="size-6 rtl:hidden" aria-hidden />
                    <ChevronRight className="hidden size-6 rtl:block" aria-hidden />
                </>
            )}
        </button>
    );
}

function SlideButton({ href, primary, children }: { href: string; primary: boolean; children: React.ReactNode }) {
    const classes = cn(
        'inline-flex items-center justify-center rounded-full border-2 px-7 py-2.5 text-base font-semibold transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-white',
        primary ? 'border-white bg-white text-bar-dark hover:bg-white/90' : 'border-white/80 text-white hover:bg-white/10',
    );

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
