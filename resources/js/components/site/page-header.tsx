import { Picture } from '@/components/site/picture';
import { accentClasses } from '@/lib/accent';
import { cn } from '@/lib/utils';
import type { Accent, ImageProps } from '@/types/site';

/**
 * Interior page masthead. With a hero image it's a compact banner with a dark
 * scrim; without one it's a plain title block on white — the restrained
 * un.org interior treatment. Either way the accent rule sits at the inline
 * start beneath the title.
 */
export function PageHeader({
    title,
    excerpt,
    image,
    accent,
}: {
    title: string;
    excerpt: string | null;
    image: ImageProps | null;
    accent: Accent;
}) {
    const accentClass = accentClasses(accent);

    if (image) {
        return (
            <div className="relative isolate overflow-hidden bg-bar-dark">
                <Picture
                    image={{ ...image, alt: '' }}
                    priority
                    sizes="100vw"
                    className="absolute inset-0 -z-10 h-full w-full object-cover opacity-40"
                />
                <div className="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
                    <h1 className="max-w-3xl font-display text-3xl font-bold text-white sm:text-5xl">
                        {title}
                    </h1>
                    <span aria-hidden className={cn('mt-4 block h-1 w-16', accentClass.rule)} />
                    {excerpt && (
                        <p className="mt-5 max-w-2xl text-lg text-white/85">{excerpt}</p>
                    )}
                </div>
            </div>
        );
    }

    return (
        <div className="border-b border-hairline bg-white">
            <div className="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                <h1 className="font-display text-3xl font-bold text-ink sm:text-4xl">{title}</h1>
                <span aria-hidden className={cn('mt-4 block h-1 w-16', accentClass.rule)} />
                {excerpt && <p className="mt-4 max-w-2xl text-lg text-ink-muted">{excerpt}</p>}
            </div>
        </div>
    );
}
