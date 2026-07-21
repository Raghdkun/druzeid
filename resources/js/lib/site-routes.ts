import { home as bareHome } from '@/routes';

/**
 * Stable route helpers for the public site.
 *
 * The default locale (Arabic) uses bare route names, so its home lives at the
 * root `/`. The authenticated area — auth pages and dashboard — renders in
 * that default locale, so this is the home they link back to.
 */
export const home = bareHome;
