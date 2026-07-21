import { cn } from '@/lib/utils';

/**
 * Renders sanitised rich-text HTML from the CMS.
 *
 * The HTML was cleaned server-side on save (App\Support\Html) against a strict
 * allowlist, so injecting it here is safe. `.prose-content` gives it the same
 * typography the editor previews, and it inherits the document direction so it
 * reads correctly in Arabic and Latin alike.
 */
export function ProseHtml({ html, className }: { html: string | null | undefined; className?: string }) {
    if (!html) {
        return null;
    }

    return (
        <div
            className={cn('prose-content', className)}
             
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
