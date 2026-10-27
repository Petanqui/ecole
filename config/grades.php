<?php

/*
|--------------------------------------------------------------------------
| Grades module defaults
|--------------------------------------------------------------------------
|
| Standard subjects and the default programme (subject => coefficient) applied
| to a class when it is created. Programmes can still be adjusted class by class
| from the class page in /gestion.
|
| Secondary coefficients follow circulaire n° 02505/MENA/DPFC of 21 July 2022
| (in force since 2022-2023). Optional subjects ("Fac") and the subjects excluded
| from averages (religious instruction, manual work) are not part of the defaults.
|
| Upper secondary programmes depend on the series, matched against the start of
| the class section: "C1" => C, "A2" => A2. The longest matching series wins.
|
*/

return [

    'subjects' => [
        'FR' => 'Français',
        'FR-OL' => 'Expression orale et lecture',
        'FR-OG' => 'Orthographe et grammaire',
        'FR-EE' => 'Expression écrite (rédaction)',
        'DICT' => 'Dictée',
        'MATH' => 'Mathématiques',
        'ANG' => 'Anglais',
        'LV2' => 'LV2 (allemand / espagnol)',
        'HG' => 'Histoire-Géographie',
        'SVT' => 'Sciences de la vie et de la terre',
        'PC' => 'Physique-Chimie',
        'SCI' => 'Sciences et technologie',
        'PHILO' => 'Philosophie',
        'EDHC' => 'Éducation aux droits de l\'homme et à la citoyenneté',
        'ART' => 'Arts plastiques / Éducation musicale',
        'EPS' => 'Éducation physique et sportive',
    ],

    'programmes' => [
        'primary' => ['DICT' => 1, 'FR' => 1, 'MATH' => 1, 'SCI' => 1, 'HG' => 1, 'EDHC' => 1, 'ANG' => 1, 'ART' => 1, 'EPS' => 1],

        // Premier cycle
        '6e' => ['FR-OL' => 1, 'FR-OG' => 1, 'FR-EE' => 1, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'EDHC' => 1, 'ART' => 1, 'EPS' => 1],
        '5e' => ['FR-OL' => 1, 'FR-OG' => 1, 'FR-EE' => 1, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'EDHC' => 1, 'ART' => 1, 'EPS' => 1],
        '4e' => ['FR-OL' => 1, 'FR-OG' => 1, 'FR-EE' => 2, 'MATH' => 3, 'ANG' => 2, 'LV2' => 1, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'EDHC' => 1, 'ART' => 1, 'EPS' => 1],
        '3e' => ['FR-OL' => 1, 'FR-OG' => 1, 'FR-EE' => 2, 'MATH' => 3, 'ANG' => 2, 'LV2' => 1, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'EDHC' => 1, 'ART' => 1, 'EPS' => 1],

        // Second cycle
        '2nde' => [
            'A' => ['FR' => 4, 'MATH' => 3, 'ANG' => 3, 'LV2' => 3, 'HG' => 3, 'SVT' => 2, 'PC' => 2, 'ART' => 1, 'EPS' => 1],
            'C' => ['FR' => 3, 'MATH' => 5, 'ANG' => 3, 'LV2' => 1, 'HG' => 2, 'SVT' => 2, 'PC' => 4, 'ART' => 1, 'EPS' => 1],
        ],
        '1ere' => [
            'A1' => ['FR' => 4, 'PHILO' => 3, 'MATH' => 3, 'ANG' => 4, 'LV2' => 3, 'HG' => 3, 'SVT' => 1, 'PC' => 1, 'ART' => 1, 'EPS' => 1],
            'A2' => ['FR' => 4, 'PHILO' => 3, 'MATH' => 2, 'ANG' => 4, 'LV2' => 3, 'HG' => 3, 'SVT' => 1, 'PC' => 1, 'ART' => 1, 'EPS' => 1],
            'C' => ['FR' => 3, 'PHILO' => 2, 'MATH' => 5, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 5, 'ART' => 1, 'EPS' => 1],
            'D' => ['FR' => 3, 'PHILO' => 2, 'MATH' => 4, 'ANG' => 2, 'HG' => 2, 'SVT' => 4, 'PC' => 4, 'ART' => 1, 'EPS' => 1],
        ],
        'tle' => [
            'A1' => ['FR' => 4, 'PHILO' => 5, 'MATH' => 4, 'ANG' => 4, 'LV2' => 3, 'HG' => 3, 'SVT' => 2, 'ART' => 1, 'EPS' => 1],
            'A2' => ['FR' => 4, 'PHILO' => 5, 'MATH' => 2, 'ANG' => 4, 'LV2' => 3, 'HG' => 3, 'SVT' => 2, 'ART' => 1, 'EPS' => 1],
            'C' => ['FR' => 3, 'PHILO' => 2, 'MATH' => 5, 'ANG' => 1, 'HG' => 2, 'SVT' => 2, 'PC' => 5, 'ART' => 1, 'EPS' => 1],
            'D' => ['FR' => 3, 'PHILO' => 2, 'MATH' => 4, 'ANG' => 1, 'HG' => 2, 'SVT' => 4, 'PC' => 4, 'ART' => 1, 'EPS' => 1],
        ],
    ],

];
