<?php

namespace Database\Seeders;

use App\Models\Grades\Subject;
use Illuminate\Database\Seeder;

/**
 * Creates the standard subjects listed in config/grades.php. Safe to run again:
 * existing subjects are matched by code and keep any name edited in /gestion.
 */
class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (array_keys(config('grades.subjects')) as $position => $code) {
            Subject::firstOrCreate(
                ['code' => $code],
                ['name' => config("grades.subjects.{$code}"), 'sort_order' => $position + 1],
            );
        }
    }
}
