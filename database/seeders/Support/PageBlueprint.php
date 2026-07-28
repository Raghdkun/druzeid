<?php

declare(strict_types=1);

namespace Database\Seeders\Support;

/**
 * Declares how each page of the original site maps onto the new content model.
 *
 * Only structural decisions live here — template, accent colour, ordering and
 * English titles. Every word of body copy still comes from the extraction, so
 * the two can never drift apart.
 *
 * Accents are the five colours of the Druze star, assigned by meaning rather
 * than for variety: العقل/mind (green) for knowledge and enterprise,
 * النفس/soul (red) for heritage and hospitality, الكلمة/word (yellow) for
 * news, السابق/precedent (blue) for structure and connection.
 */
final class PageBlueprint
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => 'org-home',
                'template' => 'home',
                'accent' => 'blue',
                'sort' => 0,
                'titles' => [
                    'ar' => 'الرئيسية',
                    'en' => 'Home',
                    'fr' => 'Accueil',
                    'nl' => 'Home',
                    'es' => 'Inicio',
                    'he' => 'בית',
                    'de' => 'Startseite',
                ],
            ],
            [
                'slug' => 'org-about-us',
                'template' => 'default',
                'accent' => 'blue',
                'sort' => 10,
                'titles' => [
                    'ar' => 'من نحن',
                    'en' => 'Who We Are',
                    'fr' => 'Qui sommes-nous',
                    'nl' => 'Wie wij zijn',
                    'es' => 'Quiénes somos',
                    'he' => 'מי אנחנו',
                    'de' => 'Wer wir sind',
                ],
            ],
            [
                'slug' => 'org-projects',
                'template' => 'default',
                'accent' => 'green',
                'sort' => 20,
                'titles' => [
                    'ar' => 'مشاريع الهوية الدرزية',
                    'en' => 'Druze Identity Projects',
                    'fr' => 'Projets Identité Druze',
                    'nl' => 'Druzische Identiteitsprojecten',
                    'es' => 'Proyectos de la Identidad Drusa',
                    'he' => 'מיזמי הזהות הדרוזית',
                    'de' => 'Projekte der drusischen Identität',
                ],
            ],
            [
                'slug' => 'org-yp',
                'template' => 'under_construction',
                'accent' => 'blue',
                'sort' => 30,
                'titles' => [
                    'ar' => 'دليل المجتمع الدرزي',
                    'en' => 'Druze Community Directory',
                    'fr' => 'Annuaire de la communauté druze',
                    'nl' => 'Druzische Gemeenschapsgids',
                    'es' => 'Directorio de la Comunidad Drusa',
                    'he' => 'מדריך הקהילה הדרוזית',
                    'de' => 'Verzeichnis der drusischen Gemeinschaft',
                ],
            ],
            [
                'slug' => 'druze-archives',
                'template' => 'under_construction',
                'accent' => 'red',
                'sort' => 40,
                'titles' => [
                    'ar' => 'أرشيف الدروز',
                    'en' => 'Druze Archives',
                    'fr' => 'Archives druzes',
                    'nl' => 'Druzisch Archief',
                    'es' => 'Archivos Drusos',
                    'he' => 'ארכיון הדרוזים',
                    'de' => 'Drusisches Archiv',
                ],
            ],
            [
                'slug' => 'druze-business',
                'template' => 'under_construction',
                'accent' => 'green',
                'sort' => 50,
                'titles' => [
                    'ar' => 'الإقتصاد الدرزي',
                    'en' => 'Druze Economy',
                    'fr' => 'Économie druze',
                    'nl' => 'Druzische Economie',
                    'es' => 'Economía Drusa',
                    'he' => 'הכלכלה הדרוזית',
                    'de' => 'Drusische Wirtschaft',
                ],
            ],
            [
                'slug' => 'druze-news',
                'template' => 'under_construction',
                'accent' => 'yellow',
                'sort' => 60,
                'titles' => [
                    'ar' => 'أخبار الدروز',
                    'en' => 'Druze News',
                    'fr' => 'Actualités druzes',
                    'nl' => 'Druzisch Nieuws',
                    'es' => 'Noticias Drusas',
                    'he' => 'חדשות הדרוזים',
                    'de' => 'Drusen-Nachrichten',
                ],
            ],
            [
                'slug' => 'druze-madafa',
                'template' => 'under_construction',
                'accent' => 'red',
                'sort' => 70,
                'titles' => [
                    'ar' => 'مضافة الدروز',
                    'en' => 'The Druze Madafa',
                    'fr' => 'La Madafa druze',
                    'nl' => 'De Druzische Madafa',
                    'es' => 'La Madafa Drusa',
                    'he' => 'המדאפה הדרוזית',
                    'de' => 'Die drusische Madafa',
                ],
            ],
            [
                'slug' => 'org-memberships',
                'template' => 'default',
                'accent' => 'blue',
                'sort' => 80,
                'titles' => [
                    'ar' => 'عضويات المنظمة',
                    'en' => 'Memberships',
                    'fr' => 'Adhésions',
                    'nl' => 'Lidmaatschappen',
                    'es' => 'Membresías',
                    'he' => 'חברות',
                    'de' => 'Mitgliedschaften',
                ],
            ],
            [
                'slug' => 'org-partners',
                'template' => 'default',
                'accent' => 'blue',
                'sort' => 90,
                'titles' => [
                    'ar' => 'شركاؤنا والداعمين',
                    'en' => 'Partners and Supporters',
                    'fr' => 'Partenaires et soutiens',
                    'nl' => 'Partners en Ondersteuners',
                    'es' => 'Socios y colaboradores',
                    'he' => 'שותפים ותומכים',
                    'de' => 'Partner und Unterstützer',
                ],
            ],
            [
                'slug' => 'org-call-us',
                'template' => 'contact',
                'accent' => 'blue',
                'sort' => 100,
                'titles' => [
                    'ar' => 'اتصل بنا',
                    'en' => 'Contact Us',
                    'fr' => 'Nous contacter',
                    'nl' => 'Contact',
                    'es' => 'Contáctanos',
                    'he' => 'צור קשר',
                    'de' => 'Kontakt',
                ],
            ],
        ];
    }

    /**
     * Slugs of the six sections listed under "مشاريع المنظمة" in the primary
     * navigation, in the order the source site presented them.
     *
     * @return list<string>
     */
    public static function projectSlugs(): array
    {
        return [
            'org-projects',
            'org-yp',
            'druze-archives',
            'druze-business',
            'druze-news',
            'druze-madafa',
        ];
    }

    /**
     * Slugs grouped under "تعريف بالمنظمة".
     *
     * @return list<string>
     */
    public static function organizationSlugs(): array
    {
        return ['org-about-us', 'org-partners', 'org-memberships'];
    }
}
