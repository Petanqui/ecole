<?php

use App\Enums\Core\GradeLevel;

test('levels are grouped into primary and secondary in school order', function () {
    expect(GradeLevel::groupedOptions())->toBe([
        'Primaire' => [
            'cp1' => 'CP1',
            'cp2' => 'CP2',
            'ce1' => 'CE1',
            'ce2' => 'CE2',
            'cm1' => 'CM1',
            'cm2' => 'CM2',
        ],
        'Secondaire' => [
            '6e' => '6e',
            '5e' => '5e',
            '4e' => '4e',
            '3e' => '3e',
            '2nde' => '2nde',
            '1ere' => '1ère',
            'tle' => 'Terminale',
        ],
    ]);
});
