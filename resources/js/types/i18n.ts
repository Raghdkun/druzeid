export type LocaleCode = 'ar' | 'en' | 'fr' | 'nl';

export type Direction = 'rtl' | 'ltr';

export interface LocaleOption {
    code: LocaleCode;
    /** Endonym — the language's name in its own language. Never a flag. */
    name: string;
    dir: Direction;
    active: boolean;
    /** Equivalent of the current URL in this locale. */
    url: string;
}

export type Translations = Record<string, string>;
