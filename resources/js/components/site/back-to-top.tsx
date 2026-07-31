import { ArrowUp } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

import { useI18n } from '@/hooks/use-i18n';
import { cn } from '@/lib/utils';

/**
 * A floating "back to top" control that fades in once the visitor has scrolled
 * down a long page and returns them to the top. Positioned above the contact
 * bubble (inline-end) so the two never collide, hidden from the tab order and
 * assistive tech while off-screen, and honoring reduced-motion for the scroll.
 */
export function BackToTop() {
    const { t } = useI18n();
    const [visible, setVisible] = useState(false);
    const ticking = useRef(false);
    const [motionOk, setMotionOk] = useState(() =>
        typeof window === 'undefined' ? true : ! window.matchMedia('(prefers-reduced-motion: reduce)').matches,
    );

    useEffect(() => {
        const query = window.matchMedia('(prefers-reduced-motion: reduce)');
        const onChange = () => setMotionOk(!query.matches);
        query.addEventListener('change', onChange);

        return () => query.removeEventListener('change', onChange);
    }, []);

    useEffect(() => {
        const onScroll = () => {
            if (ticking.current) {
                return;
            }

            ticking.current = true;
            requestAnimationFrame(() => {
                // Appears once past one viewport — i.e. through the lower part
                // of any page long enough to need scrolling.
                setVisible(window.scrollY > window.innerHeight);
                ticking.current = false;
            });
        };

        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();

        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    return (
        <button
            type="button"
            onClick={() => window.scrollTo({ top: 0, behavior: motionOk ? 'smooth' : 'auto' })}
            aria-label={t('common.back_to_top')}
            aria-hidden={!visible}
            tabIndex={visible ? 0 : -1}
            className={cn(
                'fixed bottom-24 end-5 z-40 inline-flex size-11 items-center justify-center rounded-full bg-druze-blue text-white shadow-lg ring-1 ring-black/5 transition-all duration-300 ease-out hover:bg-druze-blue-deep focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-druze-blue print:hidden',
                visible ? 'translate-y-0 opacity-100' : 'pointer-events-none translate-y-3 opacity-0',
            )}
        >
            <ArrowUp className="size-5" aria-hidden />
        </button>
    );
}
