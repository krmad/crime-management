<?php

namespace Database\Seeders;

use App\Models\CrimeCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CrimeCategorySeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'code' => 'book-iv',
                'parent_code' => null,
                'level' => 1,
                'name' => 'Book IV — Crimes Against the Public Interest or the Community',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book IV',
            ],
            [
                'code' => 'book-v',
                'parent_code' => null,
                'level' => 1,
                'name' => 'Book V — Crimes Against Individuals and the Family',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book V',
            ],
            [
                'code' => 'book-vi',
                'parent_code' => null,
                'level' => 1,
                'name' => 'Book VI — Crimes Against Property',
                'sort_order' => 3,
                'source_reference' => 'Criminal Code, Book VI',
            ],
            [
                'code' => 'book-viii',
                'parent_code' => null,
                'level' => 1,
                'name' => 'Book VIII — Petty Offences (Special Part)',
                'sort_order' => 4,
                'source_reference' => 'Criminal Code, Book VIII',
            ],
            [
                'code' => 'book-iv-title-i-breaches-public-confidence',
                'parent_code' => 'book-iv',
                'level' => 2,
                'name' => 'Title I — Breaches of Public Confidence',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book IV, Title I',
            ],
            [
                'code' => 'book-iv-title-vi-crimes-public-security-peace-tranquillity',
                'parent_code' => 'book-iv',
                'level' => 2,
                'name' => 'Title VI — Crimes Against Public Security, Peace and Tranquillity',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book IV, Title VI',
            ],
            [
                'code' => 'book-v-title-i-crimes-against-life-person-health',
                'parent_code' => 'book-v',
                'level' => 2,
                'name' => 'Title I — Crimes Against Life, Person and Health',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book V, Title I',
            ],
            [
                'code' => 'book-v-title-ii-crimes-against-liberty',
                'parent_code' => 'book-v',
                'level' => 2,
                'name' => 'Title II — Crimes Against Liberty',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book V, Title II',
            ],
            [
                'code' => 'book-vi-title-i-crimes-against-rights-property',
                'parent_code' => 'book-vi',
                'level' => 2,
                'name' => 'Title I — Crimes Against Rights in Property',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book VI, Title I',
            ],
            [
                'code' => 'book-viii-title-i-petty-offences-public-interests-community',
                'parent_code' => 'book-viii',
                'level' => 2,
                'name' => 'Title I — Petty Offences Against Public Interests and the Community',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book VIII, Title I',
            ],
            [
                'code' => 'book-viii-title-ii-petty-offences-persons-property',
                'parent_code' => 'book-viii',
                'level' => 2,
                'name' => 'Title II — Petty Offences Against Persons and Property',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book VIII, Title II',
            ],
            [
                'code' => 'book-iv-art375-material-forgery',
                'parent_code' => 'book-iv-title-i-breaches-public-confidence',
                'level' => 3,
                'name' => 'Material Forgery (Article 375)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book IV, Title I, Article 375',
            ],
            [
                'code' => 'book-iv-art376-intangible-forgery',
                'parent_code' => 'book-iv-title-i-breaches-public-confidence',
                'level' => 3,
                'name' => 'Intangible Forgery (Article 376)',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book IV, Title I, Article 376',
            ],
            [
                'code' => 'book-iv-art378-use-forged-instruments',
                'parent_code' => 'book-iv-title-i-breaches-public-confidence',
                'level' => 3,
                'name' => 'Use of Forged Instruments (Article 378)',
                'sort_order' => 3,
                'source_reference' => 'Criminal Code, Book IV, Title I, Article 378',
            ],
            [
                'code' => 'book-iv-art481-prohibited-traffic-arms',
                'parent_code' => 'book-iv-title-vi-crimes-public-security-peace-tranquillity',
                'level' => 3,
                'name' => 'Prohibited Traffic in Arms (Article 481)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book IV, Title VI, Article 481',
            ],
            [
                'code' => 'book-v-art538-homicide',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Homicide (Article 538)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 538',
            ],
            [
                'code' => 'book-v-art539-aggravated-homicide',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Aggravated Homicide (Article 539)',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 539',
            ],
            [
                'code' => 'book-v-art540-ordinary-homicide',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Ordinary Homicide (Article 540)',
                'sort_order' => 3,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 540',
            ],
            [
                'code' => 'book-v-art555-grave-wilful-injury',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Grave Wilful Injury (Article 555)',
                'sort_order' => 4,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 555',
            ],
            [
                'code' => 'book-v-art556-common-wilful-injury',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Common Wilful Injury (Article 556)',
                'sort_order' => 5,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 556',
            ],
            [
                'code' => 'book-v-art560-assaults',
                'parent_code' => 'book-v-title-i-crimes-against-life-person-health',
                'level' => 3,
                'name' => 'Assaults (Article 560)',
                'sort_order' => 6,
                'source_reference' => 'Criminal Code, Book V, Title I, Article 560',
            ],
            [
                'code' => 'book-v-art580-intimidation',
                'parent_code' => 'book-v-title-ii-crimes-against-liberty',
                'level' => 3,
                'name' => 'Intimidation (Article 580)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book V, Title II, Article 580',
            ],
            [
                'code' => 'book-vi-art665-theft',
                'parent_code' => 'book-vi-title-i-crimes-against-rights-property',
                'level' => 3,
                'name' => 'Theft (Article 665)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book VI, Title I, Article 665',
            ],
            [
                'code' => 'book-vi-art669-aggravated-theft',
                'parent_code' => 'book-vi-title-i-crimes-against-rights-property',
                'level' => 3,
                'name' => 'Aggravated Theft (Article 669)',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book VI, Title I, Article 669',
            ],
            [
                'code' => 'book-vi-art670-robbery',
                'parent_code' => 'book-vi-title-i-crimes-against-rights-property',
                'level' => 3,
                'name' => 'Robbery (Article 670)',
                'sort_order' => 3,
                'source_reference' => 'Criminal Code, Book VI, Title I, Article 670',
            ],
            [
                'code' => 'book-vi-art671-aggravated-robbery',
                'parent_code' => 'book-vi-title-i-crimes-against-rights-property',
                'level' => 3,
                'name' => 'Aggravated Robbery (Article 671)',
                'sort_order' => 4,
                'source_reference' => 'Criminal Code, Book VI, Title I, Article 671',
            ],
            [
                'code' => 'book-vi-art692-fraudulent-misrepresentation',
                'parent_code' => 'book-vi-title-i-crimes-against-rights-property',
                'level' => 3,
                'name' => 'Fraudulent Misrepresentation (Article 692)',
                'sort_order' => 5,
                'source_reference' => 'Criminal Code, Book VI, Title I, Article 692',
            ],
            [
                'code' => 'book-viii-art840-assault-minor-acts-violence',
                'parent_code' => 'book-viii-title-ii-petty-offences-persons-property',
                'level' => 3,
                'name' => 'Assault and Minor Acts of Violence (Article 840)',
                'sort_order' => 1,
                'source_reference' => 'Criminal Code, Book VIII, Title II, Article 840',
            ],
            [
                'code' => 'book-viii-art852-petty-theft',
                'parent_code' => 'book-viii-title-ii-petty-offences-persons-property',
                'level' => 3,
                'name' => 'Petty Theft (Article 852)',
                'sort_order' => 2,
                'source_reference' => 'Criminal Code, Book VIII, Title II, Article 852',
            ],
            [
                'code' => 'book-viii-art854-unjustified-possession-suspicious-articles',
                'parent_code' => 'book-viii-title-ii-petty-offences-persons-property',
                'level' => 3,
                'name' => 'Unjustified Possession of Suspicious Articles (Article 854)',
                'sort_order' => 3,
                'source_reference' => 'Criminal Code, Book VIII, Title II, Article 854',
            ],
        ];

        foreach ($categories as $category) {
            $parentId = null;

            if ($category['parent_code'] !== null) {
                $parent = CrimeCategory::query()
                    ->where('code', $category['parent_code'])
                    ->firstOrFail();

                $parentId = $parent->getKey();
            }

            CrimeCategory::query()->updateOrCreate(
                ['code' => $category['code']],
                [
                    'parent_id' => $parentId,
                    'level' => $category['level'],
                    'name' => $category['name'],
                    'sort_order' => $category['sort_order'],
                    'is_active' => true,
                    'source_reference' => $category['source_reference'],
                ],
            );
        }
    }
}
