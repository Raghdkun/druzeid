<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds أخبار الدروز — a starter set of news articles so the homepage slider
 * and the /news index have real content out of the box. These are placeholder
 * items in Arabic (with English translations); the client replaces them from
 * the CMS. The old site's news page was an "under construction" announcement,
 * so there is no extracted source to import.
 */
class NewsSeeder extends Seeder
{
    /**
     * @var list<array{ar: string, en: string, excerptAr: string, excerptEn: string, bodyAr: string, bodyEn: string}>
     */
    private const ARTICLES = [
        [
            'ar' => 'افتتاح معرض التراث الدرزي في السويداء',
            'en' => 'Druze Heritage Exhibition Opens in Suwayda',
            'excerptAr' => 'يضم المعرض مقتنيات ووثائق تروي حكاية الموحّدين الدروز عبر القرون.',
            'excerptEn' => 'The exhibition gathers artefacts and documents telling the story of the Druze across the centuries.',
            'bodyAr' => '<p>افتُتح معرض التراث الدرزي بحضور شخصيات ثقافية واجتماعية، ويعرض مجموعة من المخطوطات والصور والأدوات التقليدية التي توثّق حياة بني معروف.</p>',
            'bodyEn' => '<p>The Druze Heritage Exhibition opened before an audience of cultural and community figures, presenting manuscripts, photographs and traditional tools that document the life of Bani Maruf.</p>',
        ],
        [
            'ar' => 'ملتقى الشباب الدرزي يناقش الهوية والانتماء',
            'en' => 'Druze Youth Forum Discusses Identity and Belonging',
            'excerptAr' => 'جلسات حوارية حول دور الشباب في حماية الهوية الثقافية والروحية.',
            'excerptEn' => 'Dialogue sessions on the role of youth in safeguarding cultural and spiritual identity.',
            'bodyAr' => '<p>جمع الملتقى شباباً من مختلف المناطق لمناقشة سبل تعزيز الانتماء والحفاظ على الموروث، وخرج بتوصيات لإطلاق مبادرات توثيقية.</p>',
            'bodyEn' => '<p>The forum brought together young people from many regions to discuss strengthening belonging and preserving heritage, concluding with recommendations to launch documentation initiatives.</p>',
        ],
        [
            'ar' => 'مشروع لتوثيق المخطوطات التاريخية لبني معروف',
            'en' => 'A Project to Document the Historical Manuscripts of Bani Maruf',
            'excerptAr' => 'رقمنة المخطوطات وحفظها ضمن أرشيف مفتوح للباحثين.',
            'excerptEn' => 'Digitising manuscripts and preserving them in an open archive for researchers.',
            'bodyAr' => '<p>يهدف المشروع إلى مسح المخطوطات ضوئياً وفهرستها وإتاحتها ضمن الأرشيف الرقمي، بما يحفظ التراث المكتوب للأجيال القادمة.</p>',
            'bodyEn' => '<p>The project aims to scan, catalogue and publish manuscripts within the digital archive, preserving the written heritage for future generations.</p>',
        ],
        [
            'ar' => 'مبادرة لدعم الحرف اليدوية التقليدية',
            'en' => 'An Initiative to Support Traditional Crafts',
            'excerptAr' => 'دعم الحرفيين وربط منتجاتهم بالأسواق الرقمية.',
            'excerptEn' => 'Supporting artisans and connecting their products to digital markets.',
            'bodyAr' => '<p>تعمل المبادرة على تدريب الحرفيين وتوثيق حرفهم وتسويق منتجاتهم، بما يسهم في تنمية الاقتصاد المحلي وصون المهارات التقليدية.</p>',
            'bodyEn' => '<p>The initiative trains artisans, documents their crafts and markets their products, contributing to the local economy and safeguarding traditional skills.</p>',
        ],
        [
            'ar' => 'ندوة حول دور المرأة الدرزية في المجتمع',
            'en' => 'Symposium on the Role of Druze Women in Society',
            'excerptAr' => 'مساهمات المرأة في التعليم والعمل الاجتماعي والحفاظ على الهوية.',
            'excerptEn' => "Women's contributions in education, social work and preserving identity.",
            'bodyAr' => '<p>سلّطت الندوة الضوء على إسهامات المرأة الدرزية في مختلف الميادين، ودعت إلى توسيع مشاركتها في مبادرات المنظمة.</p>',
            'bodyEn' => '<p>The symposium highlighted the contributions of Druze women across many fields and called for broadening their participation in the organisation\'s initiatives.</p>',
        ],
    ];

    public function run(): void
    {
        $category = ArticleCategory::query()->firstOrCreate(
            ['slug' => 'druze-news'],
            ['name' => ['ar' => 'أخبار الدروز', 'en' => 'Druze News'], 'accent' => 'blue', 'sort' => 0]
        );

        $author = User::query()->where('role', User::ROLE_ADMIN)->first();
        $mediaIds = Media::query()->where('kind', 'image')->inRandomOrder()->limit(count(self::ARTICLES))->pluck('id')->all();

        foreach (self::ARTICLES as $index => $item) {
            Article::query()->updateOrCreate(
                ['slug' => Str::slug($item['en'])],
                [
                    'article_category_id' => $category->id,
                    'featured_media_id' => $mediaIds[$index] ?? null,
                    'author_id' => $author?->id,
                    'title' => ['ar' => $item['ar'], 'en' => $item['en']],
                    'excerpt' => ['ar' => $item['excerptAr'], 'en' => $item['excerptEn']],
                    'body' => ['ar' => $item['bodyAr'], 'en' => $item['bodyEn']],
                    'status' => Article::STATUS_PUBLISHED,
                    'published_at' => now()->subDays($index),
                    // Feature the first three for the homepage slider.
                    'is_featured' => $index < 3,
                ]
            );
        }
    }
}
