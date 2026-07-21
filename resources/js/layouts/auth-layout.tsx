import { Link } from '@inertiajs/react';
import { useLayoutEffect } from 'react';

import { DruzeStar } from '@/components/site/druze-star';
import { home } from '@/lib/site-routes';

/**
 * Staff authentication shell.
 *
 * Scene: an editor or administrator signing into the Arabic CMS from a desktop
 * during work hours. That calls for a calm, light, institutional surface — not
 * the reflexive "dark tool" look. A branded column (deep institutional blue,
 * the five-principle Druze star) sits inline-start; the form sits on white
 * inline-end. On small screens the brand collapses to a compact header so the
 * form leads. RTL throughout.
 */
export default function AuthLayout({
    title = '',
    description = '',
    children,
}: {
    title?: string;
    description?: string;
    children: React.ReactNode;
}) {
    // Auth is a light institutional surface. The kit's initializeTheme() may
    // apply `.dark` from system preference on load, so clear it while an auth
    // screen is mounted (restored on unmount into the dark-capable dashboard).
    useLayoutEffect(() => {
        const root = document.documentElement;
        const wasDark = root.classList.contains('dark');
        root.classList.remove('dark');

        return () => {
            if (wasDark) {
                root.classList.add('dark');
            }
        };
    }, []);

    return (
        <div className="grid min-h-svh lg:grid-cols-[1.05fr_1fr]">
            {/* Brand column */}
            <aside className="relative hidden overflow-hidden bg-druze-blue-deep text-white lg:flex lg:flex-col lg:justify-between lg:p-12">
                <Link href={home()} className="flex items-center gap-3">
                    <DruzeStar className="size-11" decorative />
                    <span className="font-display text-lg font-bold">منظمة الهوية الدرزية</span>
                </Link>

                <div className="max-w-md">
                    <p className="font-display text-3xl font-bold leading-snug">لوحة التحكم</p>
                    <p className="mt-4 text-base leading-relaxed text-white/75">
                        فضاء العمل الخاص بفريق المنظمة لإدارة محتوى الموقع بلغاته الأربع، وحفظ التراث
                        والتعريف بالهوية الدرزية.
                    </p>
                </div>

                <p className="text-sm text-white/55">
                    منظمة إنسانية غير ربحية تعرّف بالموحدين الدروز — بني معروف
                </p>

                {/* A faint five-point motif, decorative only. */}
                <DruzeStar
                    className="pointer-events-none absolute -bottom-24 -start-24 size-80 opacity-[0.06]"
                    decorative
                />
            </aside>

            {/* Form column */}
            <main className="flex flex-col justify-center bg-background px-6 py-12 sm:px-10">
                <div className="mx-auto w-full max-w-sm animate-in fade-in slide-in-from-bottom-2 duration-500">
                    {/* Compact brand header for small screens */}
                    <Link href={home()} className="mb-8 flex items-center justify-center gap-2 lg:hidden">
                        <DruzeStar className="size-9" decorative />
                        <span className="font-display text-base font-bold text-ink">الهوية الدرزية</span>
                    </Link>

                    <header className="mb-8 text-center lg:text-start">
                        {title && (
                            <h1 className="font-display text-2xl font-bold text-ink">{title}</h1>
                        )}
                        {description && <p className="mt-2 text-sm text-ink-muted">{description}</p>}
                    </header>

                    {children}
                </div>
            </main>
        </div>
    );
}
