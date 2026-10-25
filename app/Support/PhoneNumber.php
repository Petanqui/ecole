<?php

namespace App\Support;

class PhoneNumber
{
    /**
     * Country calling code applied to numbers entered without an international prefix (Côte d'Ivoire).
     */
    public const DefaultCountryCode = '225';

    /**
     * Normalize a phone number to the E.164 format (e.g. "+2250700000000").
     *
     * Accepts local Ivorian numbers ("07 00 00 00 00") as well as international
     * numbers prefixed with "+" or "00". Returns null when the input is not a valid number.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = preg_replace('/[\s.\-()\/]/', '', $phone);

        if (str_starts_with($phone, '00')) {
            $phone = '+'.substr($phone, 2);
        }

        if (preg_match('/^\+[1-9]\d{7,14}$/', $phone)) {
            return $phone;
        }

        if (preg_match('/^\d{10}$/', $phone)) {
            return '+'.self::DefaultCountryCode.$phone;
        }

        return null;
    }
}
