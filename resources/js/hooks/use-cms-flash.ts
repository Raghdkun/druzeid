import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import { toast } from 'sonner';

/**
 * Surfaces the server's one-request flash (`flash.success` / `flash.error`) as
 * a toast in the CMS. Controllers redirect with `->with('success', …)` after a
 * save or delete; without this the editor gets no visible confirmation.
 *
 * A ref guards against re-toasting the same message on unrelated re-renders,
 * while still firing again when a genuinely new flash arrives.
 */
export function useCmsFlash(): void {
    const { flash } = usePage().props as unknown as {
        flash?: { success?: string | null; error?: string | null };
    };
    const last = useRef<string>('');

    useEffect(() => {
        const success = flash?.success;
        const error = flash?.error;

        if (success && success !== last.current) {
            last.current = success;
            toast.success(success);
        } else if (error && error !== last.current) {
            last.current = error;
            toast.error(error);
        }
    }, [flash?.success, flash?.error]);
}
