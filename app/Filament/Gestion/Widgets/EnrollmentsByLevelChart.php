<?php

namespace App\Filament\Gestion\Widgets;

use App\Enums\Core\GradeLevel;
use App\Filament\Gestion\Concerns\VisibleToStaffMembers;
use App\Models\Core\Enrollment;
use Filament\Widgets\ChartWidget;

/**
 * How many students are enrolled at each level this year, from CP1 to Terminale.
 */
class EnrollmentsByLevelChart extends ChartWidget
{
    use VisibleToStaffMembers;

    protected static ?int $sort = 2;

    protected ?string $heading = 'Élèves par niveau';

    protected ?string $maxHeight = '260px';

    /**
     * @return array<string, mixed>
     */
    protected function getData(): array
    {
        $counts = Enrollment::query()
            ->whereRelation('schoolYear', 'is_current', true)
            ->join('school_classes', 'school_classes.id', '=', 'enrollments.school_class_id')
            ->groupBy('school_classes.level')
            ->selectRaw('school_classes.level, count(*) as total')
            ->pluck('total', 'level');

        $levels = collect(GradeLevel::cases())->filter(fn (GradeLevel $level): bool => isset($counts[$level->value]));

        return [
            'datasets' => [[
                'label' => 'Élèves',
                'data' => $levels->map(fn (GradeLevel $level): int => (int) $counts[$level->value])->values()->all(),
                'backgroundColor' => 'rgb(217, 119, 6)',
                'borderRadius' => 4,
                'maxBarThickness' => 32,
            ]],
            'labels' => $levels->map(fn (GradeLevel $level): string => $level->getLabel())->values()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'plugins' => ['legend' => ['display' => false]],
            'scales' => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
        ];
    }
}
