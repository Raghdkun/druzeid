import { Play } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { cn } from '@/lib/utils';
import type { ImageProps } from '@/types/site';

interface VideoPlayerProps {
    /** A media item with `kind: 'video'` — carries url, mime, poster, dims. */
    media: ImageProps;
    className?: string;
    /** Muted, looping ambient playback. Suppressed under reduced-motion. */
    autoplay?: boolean;
}

/**
 * A restrained, accessible video player. Until the viewer starts it, the frame
 * shows the poster (or first frame) under a single quiet play affordance — no
 * chrome, in keeping with the institutional surface. On play the native
 * controls take over, which keeps keyboard, captions and fullscreen fully
 * accessible without reimplementing a control bar. The aspect-ratio box
 * reserves layout space so nothing shifts as the video loads.
 */
export function VideoPlayer({ media, className, autoplay = false }: VideoPlayerProps) {
    const videoRef = useRef<HTMLVideoElement>(null);
    // Seed from the current preference so the first render is already correct
    // (guarding SSR, where there is no `window`); the effect then only has to
    // keep it in sync if the setting is toggled while the page is open.
    const [motionOk, setMotionOk] = useState(() =>
        typeof window === 'undefined' ? true : ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    );
    const [started, setStarted] = useState(autoplay);

    // Honour prefers-reduced-motion: an ambient autoplay becomes a normal
    // click-to-play so the page never moves on its own for those who opt out.
    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const onChange = () => setMotionOk(!query.matches);
        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, []);

    const ambient = autoplay && motionOk;
    const aspect = media.width && media.height ? media.width / media.height : 16 / 9;
    const label = media.alt || media.caption || 'فيديو';

    const start = () => {
        setStarted(true);
        requestAnimationFrame(() => {
            void videoRef.current?.play();
        });
    };

    return (
        <figure className={cn('space-y-2', className)}>
            <div
                className="relative overflow-hidden rounded-xl bg-black shadow-sm ring-1 ring-black/5"
                style={{ aspectRatio: aspect }}
            >
                <video
                    ref={videoRef}
                    src={media.url}
                    poster={media.poster ?? undefined}
                    controls={started && !ambient}
                    preload="metadata"
                    playsInline
                    muted={ambient}
                    loop={ambient}
                    autoPlay={ambient}
                    aria-label={label}
                    className="h-full w-full object-contain"
                />

                {!started && (
                    <button
                        type="button"
                        onClick={start}
                        aria-label={`تشغيل: ${label}`}
                        className="group absolute inset-0 flex items-center justify-center bg-black/10 transition-colors hover:bg-black/25 focus-visible:outline-none"
                    >
                        <span className="flex size-16 items-center justify-center rounded-full bg-druze-blue/95 text-white shadow-lg ring-4 ring-white/25 transition-transform duration-300 group-hover:scale-110 group-focus-visible:ring-white/60 sm:size-20">
                            {/* Nudged for the triangle's optical centre. */}
                            <Play className="size-7 translate-x-0.5 fill-current sm:size-9" aria-hidden />
                        </span>
                        {media.duration ? (
                            <span className="absolute bottom-3 end-3 rounded bg-black/70 px-1.5 py-0.5 font-mono text-xs tabular-nums text-white">
                                {formatDuration(media.duration)}
                            </span>
                        ) : null}
                    </button>
                )}
            </div>

            {(media.caption || media.credit) && (
                <figcaption className="text-sm text-ink-muted">
                    {media.caption}
                    {media.credit && <span className="block text-xs opacity-75">{media.credit}</span>}
                </figcaption>
            )}
        </figure>
    );
}

function formatDuration(seconds: number): string {
    const total = Math.max(0, Math.round(seconds));
    const minutes = Math.floor(total / 60);
    const rest = total % 60;

    return `${minutes}:${rest.toString().padStart(2, '0')}`;
}
