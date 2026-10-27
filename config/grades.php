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

    /*
    | Annual average = sum(term average × weight) / sum(weights), over the terms that have
    | an average. Primary (MENA guide): (T1 + T2 + T3) / 3 ; secondary: (T1 + 2×T2 + 2×T3) / 5.
    */
    'annual_term_weights' => [
        'primary' => [1 => 1, 2 => 1, 3 => 1],
        'secondary' => [1 => 1, 2 => 2, 3 => 2],
    ],

    /*
    | Primary classes from CP1 to CM1 move up when the annual average reaches this mark (out of 10).
    | CM2 is an exam class: admission to 6e needs a MAP (mean of the regional compositions and
    | mock exams, out of 20) of at least cm2_admission_average.
    */
    'primary_passing_average' => 5,

    'cm2_admission_average' => 10,

    /*
    | Thresholds are on a scale of 20 and are scaled down for primary school (out of 10).
    | Each list is checked from the top: the first threshold reached wins.
    */
    'distinctions' => [
        'Félicitations' => 16,
        'Encouragements' => 14,
        'Tableau d\'honneur' => 12,
    ],

    'warning' => [
        'label' => 'Avertissement',
        'below' => 8.5,
    ],

    'appreciations' => [
        'Excellent' => 18,
        'Très bien' => 16,
        'Bien' => 14,
        'Assez bien' => 12,
        'Passable' => 10,
        'Insuffisant' => 8,
        'Faible' => 0,
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
