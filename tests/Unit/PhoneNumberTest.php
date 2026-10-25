<?php

use App\Support\PhoneNumber;

test('local ivorian numbers get the +225 prefix', function (string $input) {
    expect(PhoneNumber::normalize($input))->toBe('+2250700000000');
})->with([
    '07 00 00 00 00',
    '0700000000',
    '07.00.00.00.00',
    '07-00-00-00-00',
]);

test('international numbers are kept in E.164 format', function (string $input, string $expected) {
    expect(PhoneNumber::normalize($input))->toBe($expected);
})->with([
    ['+225 07 00 00 00 00', '+2250700000000'],
    ['00225 07 00 00 00 00', '+2250700000000'],
    ['+33 6 12 34 56 78', '+33612345678'],
]);

test('invalid numbers are rejected', function (?string $input) {
    expect(PhoneNumber::normalize($input))->toBeNull();
})->with([null, '', 'abc', '07 00 00', '+0123456789', '+1234567890123456']);
