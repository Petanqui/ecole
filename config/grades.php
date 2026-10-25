<?php

/*
|--------------------------------------------------------------------------
| Grades module defaults
|--------------------------------------------------------------------------
|
| Standard subjects and the default programme (subject => coefficient) applied
| to a class when it is created. These follow common Ivorian practice and can be
| adjusted here, or class by class from the class page in /gestion.
|
| Upper secondary programmes depend on the series, read from the first letter
| of the class section (2nde C1 => C, Tle D2 => D).
|
*/

return [

    'subjects' => [
        'FR' => 'Français',
        'MATH' => 'Mathématiques',
        'ANG' => 'Anglais',
        'HG' => 'Histoire-Géographie',
        'SVT' => 'Sciences de la vie et de la terre',
        'PC' => 'Physique-Chimie',
        'PHILO' => 'Philosophie',
        'ESP' => 'Espagnol',
        'ALL' => 'Allemand',
        'EDHC' => 'Éducation aux droits de l\'homme et à la citoyenneté',
        'EPS' => 'Éducation physique et sportive',
        'ART' => 'Arts plastiques',
        'MUS' => 'Musique',
        'SCI' => 'Sciences et technologie',
    ],

    'programmes' => [
        'primary' => ['FR' => 1, 'MATH' => 1, 'SCI' => 1, 'HG' => 1, 'EDHC' => 1, 'ANG' => 1, 'EPS' => 1, 'ART' => 1],

        '6e' => ['FR' => 4, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 1, 'EDHC' => 1, 'EPS' => 1, 'ART' => 1, 'MUS' => 1],
        '5e' => ['FR' => 4, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 1, 'EDHC' => 1, 'EPS' => 1, 'ART' => 1, 'MUS' => 1],
        '4e' => ['FR' => 4, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'ESP' => 1, 'EDHC' => 1, 'EPS' => 1],
        '3e' => ['FR' => 4, 'MATH' => 3, 'ANG' => 2, 'HG' => 2, 'SVT' => 2, 'PC' => 2, 'ESP' => 1, 'EDHC' => 1, 'EPS' => 1],

        '2nde' => [
            'A' => ['FR' => 4, 'ANG' => 3, 'HG' => 3, 'ESP' => 2, 'MATH' => 2, 'SVT' => 1, 'PC' => 1, 'EDHC' => 1, 'EPS' => 1],
            'C' => ['MATH' => 5, 'PC' => 4, 'SVT' => 2, 'FR' => 3, 'ANG' => 2, 'HG' => 2, 'EDHC' => 1, 'EPS' => 1],
        ],
        '1ere' => [
            'A' => ['FR' => 4, 'PHILO' => 3, 'ANG' => 3, 'HG' => 3, 'ESP' => 2, 'MATH' => 2, 'SVT' => 1, 'EPS' => 1],
            'C' => ['MATH' => 5, 'PC' => 5, 'SVT' => 2, 'FR' => 3, 'PHILO' => 2, 'ANG' => 2, 'HG' => 2, 'EPS' => 1],
            'D' => ['MATH' => 4, 'PC' => 4, 'SVT' => 4, 'FR' => 3, 'PHILO' => 2, 'ANG' => 2, 'HG' => 2, 'EPS' => 1],
        ],
        'tle' => [
            'A' => ['FR' => 4, 'PHILO' => 4, 'ANG' => 3, 'HG' => 3, 'ESP' => 2, 'MATH' => 2, 'SVT' => 1, 'EPS' => 1],
            'C' => ['MATH' => 5, 'PC' => 5, 'SVT' => 2, 'FR' => 3, 'PHILO' => 2, 'ANG' => 2, 'HG' => 2, 'EPS' => 1],
            'D' => ['MATH' => 4, 'PC' => 4, 'SVT' => 4, 'FR' => 3, 'PHILO' => 2, 'ANG' => 2, 'HG' => 2, 'EPS' => 1],
        ],
    ],

];
