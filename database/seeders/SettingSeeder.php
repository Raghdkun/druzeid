<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Setting;
use Database\Seeders\Support\ExtractedPage;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $contact = ExtractedPage::load('org-call-us', base_path('_extracted'))->contactDetails();

        $settings = [
            'general' => [
                'site.short_name' => [
                    'ar' => 'الهوية الدرزية',
                    'en' => 'Druze Identity',
                    'fr' => 'Identité Druze',
                    'nl' => 'Druzische Identiteit',
                ],
                'site.tagline' => [
                    'ar' => 'منظمة إنسانية غير ربحية تعرف بالموحدين الدروز – بني معروف',
                    'en' => "A non-profit humanitarian organization introducing the Druze — Bani Ma'ruf",
                    'fr' => "Une organisation humanitaire à but non lucratif faisant connaître les Druzes — Bani Ma'ruf",
                    'nl' => "Een humanitaire non-profitorganisatie die de Druzen — Bani Ma'ruf — bekendmaakt",
                ],
            ],

            'contact' => [
                'contact.email' => $contact['emails'][0] ?? 'DRUZE.IDENTITY@gmail.com',
                // The WhatsApp line, in international format for the wa.me link
                // used by the floating contact bubble.
                'contact.whatsapp' => $contact['phones'][1] ?? '00963994315333',
                // Both numbers carried over from the source contact page —
                // the first is the mobile line, the second WhatsApp.
                'contact.phones' => [
                    ['label' => ['ar' => 'موبايل', 'en' => 'Mobile', 'fr' => 'Mobile', 'nl' => 'Mobiel'],
                        'value' => $contact['phones'][0] ?? '0096179025597'],
                    ['label' => ['ar' => 'واتس أب', 'en' => 'WhatsApp', 'fr' => 'WhatsApp', 'nl' => 'WhatsApp'],
                        'value' => $contact['phones'][1] ?? '00963994315333'],
                ],
                'contact.address' => null,
            ],

            'social' => [
                // The source site linked no social profiles; the contact page
                // promises them, so the key exists ready to be populated.
                'social.links' => [],
            ],

            'footer' => [
                'footer.note' => [
                    'ar' => 'منظمة مستقلة تُعنى بحماية وتعزيز الهوية الثقافية والاجتماعية والروحية للموحدين الدروز – بني معروف حول العالم.',
                    'en' => 'An independent organization dedicated to protecting and promoting the cultural, social and spiritual identity of the Druze — Bani Ma\'ruf — worldwide.',
                ],
            ],
        ];

        $count = 0;

        foreach ($settings as $group => $entries) {
            foreach ($entries as $key => $value) {
                Setting::put($key, $value, $group);
                $count++;
            }
        }

        $this->command->info("  settings: {$count} written");
    }
}
