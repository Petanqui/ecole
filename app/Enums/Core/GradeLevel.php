<?php

namespace App\Enums\Core;

use Filament\Support\Contracts\HasLabel;

enum GradeLevel: string implements HasLabel
{
    case Cp1 = 'cp1';
    case Cp2 = 'cp2';
    case Ce1 = 'ce1';
    case Ce2 = 'ce2';
    case Cm1 = 'cm1';
    case Cm2 = 'cm2';
    case Sixieme = '6e';
    case Cinquieme = '5e';
    case Quatrieme = '4e';
    case Troisieme = '3e';
    case Seconde = '2nde';
    case Premiere = '1ere';
    case Terminale = 'tle';

    public function getLabel(): string
    {
        return match ($this) {
            self::Cp1 => 'CP1',
            self::Cp2 => 'CP2',
            self::Ce1 => 'CE1',
            self::Ce2 => 'CE2',
            self::Cm1 => 'CM1',
            self::Cm2 => 'CM2',
            self::Sixieme => '6e',
            self::Cinquieme => '5e',
            self::Quatrieme => '4e',
            self::Troisieme => '3e',
            self::Seconde => '2nde',
            self::Premiere => '1ère',
            self::Terminale => 'Terminale',
        };
    }

    /**
     * Build a class name from this level and a section: "6e" + "1" gives "6e1",
     * "2nde" + "C1" gives "2nde C1", "CP1" + "A" gives "CP1 A".
     */
    public function className(string $section): string
    {
        $label = $this->getLabel();
        $section = trim($section);

        $glued = ctype_alpha(substr($label, -1)) && ctype_digit(substr($section, 0, 1));

        return $glued ? $label.$section : "{$label} {$section}";
    }

    /**
     * Averages are out of 10 in primary school and out of 20 in secondary school.
     */
    public function gradingScale(): int
    {
        return $this->isPrimary() ? 10 : 20;
    }

    /**
     * In primary school a subject average adds up points, so a composition out of 50 weighs
     * five times a test out of 10. In secondary school every mark counts equally once
     * brought back to the grading scale.
     */
    public function weightsMarksByMaxScore(): bool
    {
        return $this->isPrimary();
    }

    /**
     * Upper secondary programmes depend on the series (A, C, D…).
     */
    public function hasSeries(): bool
    {
        return in_array($this, [self::Seconde, self::Premiere, self::Terminale], true);
    }

    /**
     * The default programme of a class at this level, as subject code => coefficient.
     * For upper secondary the series is the first letter of the section ("C1" => "C").
     *
     * @return array<string, int>
     */
    public function defaultProgramme(string $section): array
    {
        $programmes = config('grades.programmes');

        if ($this->isPrimary()) {
            return $programmes['primary'];
        }

        if ($this->hasSeries()) {
            return $programmes[$this->value][strtoupper(substr(trim($section), 0, 1))] ?? [];
        }

        return $programmes[$this->value] ?? [];
    }

    public function isPrimary(): bool
    {
        return in_array($this, [self::Cp1, self::Cp2, self::Ce1, self::Ce2, self::Cm1, self::Cm2], true);
    }

    /**
     * Levels grouped by cycle, for use as grouped select options.
     *
     * @return array{Primaire: array<string, string>, Secondaire: array<string, string>}
     */
    public static function groupedOptions(): array
    {
        $options = ['Primaire' => [], 'Secondaire' => []];

        foreach (self::cases() as $level) {
            $options[$level->isPrimary() ? 'Primaire' : 'Secondaire'][$level->value] = $level->getLabel();
        }

        return $options;
    }
}
