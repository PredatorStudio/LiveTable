<?php

namespace Workbench\Database\Seeders;

use Illuminate\Database\Seeder;
use Workbench\App\Models\DemoUser;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $imiona = [
            'Jan', 'Anna', 'Piotr', 'Maria', 'Tomasz', 'Katarzyna', 'Marek', 'Agnieszka',
            'Krzysztof', 'Małgorzata', 'Andrzej', 'Joanna', 'Stanisław', 'Barbara', 'Michał',
            'Ewa', 'Paweł', 'Zofia', 'Grzegorz', 'Monika',
        ];

        $nazwiska = [
            'Kowalski', 'Nowak', 'Wiśniewski', 'Wójcik', 'Kowalczyk', 'Kamiński', 'Lewandowski',
            'Zielński', 'Szymański', 'Woźniak', 'Dąbrowski', 'Kozłowski', 'Jankowski', 'Mazur',
            'Wojciechowski', 'Kwiatkowski', 'Krawczyk', 'Piotrowska', 'Grabowski', 'Nowakowski',
        ];

        $ulice = [
            'Kwiatowa', 'Różana', 'Lipowa', 'Słoneczna', 'Leśna', 'Polna', 'Ogrodowa',
            'Parkowa', 'Szkolna', 'Kościelna', 'Nowa', 'Krótka', 'Długa', 'Miła', 'Spokojna',
        ];

        $miasta = [
            ['nazwa' => 'Warszawa',   'kod' => '00'],
            ['nazwa' => 'Kraków',     'kod' => '30'],
            ['nazwa' => 'Wrocław',    'kod' => '50'],
            ['nazwa' => 'Poznań',     'kod' => '60'],
            ['nazwa' => 'Gdańsk',     'kod' => '80'],
            ['nazwa' => 'Szczecin',   'kod' => '70'],
            ['nazwa' => 'Łódź',       'kod' => '90'],
            ['nazwa' => 'Katowice',   'kod' => '40'],
            ['nazwa' => 'Lublin',     'kod' => '20'],
            ['nazwa' => 'Białystok',  'kod' => '15'],
        ];

        $records = [];

        for ($i = 0; $i < 100; $i++) {
            $imie = $imiona[$i % count($imiona)];
            $suffix = (int) ($i / count($imiona)) > 0 ? ' '.(string) ((int) ($i / count($imiona)) + 1) : '';
            $miasto = $miasta[$i % count($miasta)];
            $nrDomu = ($i % 50) + 1;
            $nrKodu = str_pad((string) (($i % 99) + 1), 3, '0', STR_PAD_LEFT);

            $records[] = [
                'imie' => $imie.$suffix,
                'nazwisko' => $nazwiska[$i % count($nazwiska)],
                'adres' => 'ul. '.$ulice[$i % count($ulice)].' '.$nrDomu.', '.$miasto['kod'].'-'.$nrKodu.' '.$miasto['nazwa'],
                'status' => $i % 3 === 0 ? 'inactive' : 'active',
            ];
        }

        DemoUser::insert($records);
    }
}
