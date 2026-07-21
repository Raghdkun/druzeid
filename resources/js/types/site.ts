/**
 * Shapes the Laravel side sends to the public site. Every translatable string
 * is already resolved to the active locale server-side, so these are plain
 * display values — the front-end never sees a locale map.
 */

export type Accent = 'blue' | 'green' | 'red' | 'yellow' | 'white' | null;

export interface ImageProps {
    id: number;
    url: string;
    alt: string;
    caption: string | null;
    credit: string | null;
    width: number | null;
    height: number | null;
    objectPosition: string;
    /** Responsive next-gen sources; null when no derivatives exist. */
    srcsetAvif: string | null;
    srcsetWebp: string | null;
    /** 'image' picks <picture>; 'video' picks the video player. */
    kind: 'image' | 'video';
    /** Video only: source MIME, poster frame URL, length in seconds. */
    mime: string | null;
    poster: string | null;
    duration: number | null;
}

export interface NavItem {
    id: number;
    label: string;
    href: string | null;
    newTab: boolean;
    children: NavItem[];
}

export interface ContactPhone {
    label: string;
    value: string;
}

export interface SiteSettings {
    'site.tagline': string | null;
    'site.short_name': string | null;
    'contact.email': string | null;
    'contact.phones': ContactPhone[] | null;
    'contact.address': string | null;
    'social.links': Array<{ platform: string; url: string }> | null;
    'footer.note': string | null;
}

export interface SiteMenus {
    primary: NavItem[];
    footerSections: NavItem[];
    footerOrganization: NavItem[];
}

// -- Section payloads (discriminated by `type`) -----------------------------
//
// Prose is stored and delivered as sanitised HTML (an `html` string, resolved
// from a per-locale map server-side), edited in the CMS with the rich-text
// editor and rendered on the site by <ProseHtml>.

export interface HeroAction {
    label: string;
    /** Resolved server-side from `slug`, or an in-page anchor (#id). */
    href?: string;
    slug?: string;
}

export interface HeroSection {
    id: number;
    type: 'hero';
    title: string;
    subtitle: string | null;
    images: ImageProps[];
    actions: HeroAction[];
}

export interface RichTextSection {
    id: number;
    type: 'rich_text';
    variant?: 'feature';
    html: string;
}

export interface Card {
    slug: string;
    /** Resolved server-side from `slug`. */
    href: string;
    title: string;
    excerpt: string | null;
    accent: Accent;
    image: ImageProps | null;
    underConstruction: boolean;
}

export interface CardGridSection {
    id: number;
    type: 'card_grid';
    anchor?: string;
    heading: string | null;
    intro: string | null;
    cards: Card[];
}

export interface AccordionItem {
    title: string;
    html: string;
}

export interface AccordionSection {
    id: number;
    type: 'accordion';
    heading: string | null;
    items: AccordionItem[];
}

export interface UnderConstructionSection {
    id: number;
    type: 'under_construction';
    domain: string | null;
    image: ImageProps | null;
    html: string;
}

export interface GallerySection {
    id: number;
    type: 'gallery';
    images: ImageProps[];
}

export interface VideoSection {
    id: number;
    type: 'video';
    heading: string | null;
    caption: string | null;
    autoplay: boolean;
    /**
     * The video media, hydrated server-side from the payload's `media_id`
     * (so the field arrives under the key `image`). `kind` is 'video'.
     */
    image: ImageProps | null;
}

export interface ContactSection {
    id: number;
    type: 'contact';
    email: string | null;
    phones: string[];
    html: string;
    showForm: boolean;
}

export type Section =
    | HeroSection
    | RichTextSection
    | CardGridSection
    | AccordionSection
    | UnderConstructionSection
    | GallerySection
    | VideoSection
    | ContactSection;

export interface PageData {
    id: number;
    slug: string;
    template: string;
    accent: Accent;
    title: string;
    excerpt: string | null;
    heroImage: ImageProps | null;
    sections: Section[];
}

export interface PageMeta {
    title: string | null;
    description: string | null;
    canonical: string;
    alternates: Array<{ locale: string; href: string }>;
    ogImage: string | null;
    ogLocale: string;
    siteName: string | null;
}
