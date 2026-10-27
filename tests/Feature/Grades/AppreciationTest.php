<?php

use App\Services\Grades\Appreciation;

test('subject appreciations follow the thresholds out of 20', function (float $average, string $expected) {
    expect(Appreciation::forSubject($average, 20))->toBe($expected);
})->with([
    [18.5, 'Excellent'],
    [16, 'Très bien'],
    [14.25, 'Bien'],
    [12, 'Assez bien'],
    [10, 'Passable'],
    [9.99, 'Insuffisant'],
    [4, 'Faible'],
]);

test('distinctions follow the thresholds out of 20', function (float $average, ?string $expected) {
    expect(Appreciation::distinction($average, 20))->toBe($expected);
})->with([
    [16.5, 'Félicitations'],
    [14, 'Encouragements'],
    [12.5, 'Tableau d\'honneur'],
    [10, null],
    [8.49, 'Avertissement'],
]);

test('primary averages out of 10 are compared on the same thresholds', function () {
    expect(Appreciation::distinction(8, 10))->toBe('Félicitations')
        ->and(Appreciation::distinction(6, 10))->toBe('Tableau d\'honneur')
        ->and(Appreciation::forSubject(5, 10))->toBe('Passable')
        ->and(Appreciation::distinction(null, 10))->toBeNull();
});
