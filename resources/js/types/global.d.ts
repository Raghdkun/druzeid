import type { Auth } from '@/types/auth';
import type { Direction, LocaleCode, LocaleOption, Translations } from '@/types/i18n';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            locale: LocaleCode;
            dir: Direction;
            /** BCP-47 tag for <html lang>, e.g. "en-GB". */
            htmlLang: string;
            locales: LocaleOption[];
            translations: Translations;
            /** Home URL in the ACTIVE locale — never the bare Arabic root. */
            homeUrl: string;
            [key: string]: unknown;
        };
    }
}
