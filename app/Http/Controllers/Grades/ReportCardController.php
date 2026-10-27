<?php

namespace App\Http\Controllers\Grades;

use App\Http\Controllers\Controller;
use App\Models\Core\Enrollment;
use App\Models\Core\SchoolClass;
use App\Models\Core\Student;
use App\Models\Grades\Term;
use App\Services\Attendance\AttendanceSummary;
use App\Services\Grades\AnnualResults;
use App\Services\Grades\TermResults;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ReportCardController extends Controller
{
    /**
     * Report cards of a whole class (one page per student), or of one of its students
     * with ?student={id}. Staff only.
     */
    public function forClass(Request $request, SchoolClass $schoolClass, Term $term): Response
    {
        Gate::authorize('print-class-report-cards');
        abort_unless($term->school_year_id === $schoolClass->school_year_id, 404);

        $studentId = $request->integer('student') ?: null;

        return $this->render($schoolClass, $term, $studentId);
    }

    /**
     * The report card of one child, for their parent in the portal.
     */
    public function forStudent(Student $student, Term $term): Response
    {
        Gate::authorize('download-report-card', [$student, $term]);

        $schoolClass = Enrollment::query()
            ->where('student_id', $student->getKey())
            ->where('school_year_id', $term->school_year_id)
            ->firstOrFail()
            ->schoolClass;

        return $this->render($schoolClass, $term, $student->getKey());
    }

    protected function render(SchoolClass $schoolClass, Term $term, ?int $studentId): Response
    {
        $results = new TermResults($schoolClass, $term);
        $annual = $term->number === 3 ? new AnnualResults($schoolClass) : null;

        $rows = $studentId === null
            ? $results->students
            : $results->students->only([$studentId]);

        abort_if($rows->isEmpty(), 404);

        $filename = Str::slug(implode(' ', array_filter([
            'bulletin',
            $schoolClass->name,
            $term->name,
            $studentId ? $rows->first()['student']->full_name : null,
        ]))).'.pdf';

        return Pdf::loadView('grades.report-card', [
            'results' => $results,
            'annual' => $annual,
            'attendance' => AttendanceSummary::forClass($schoolClass, $term),
            'rows' => $rows,
            'schoolClass' => $schoolClass,
            'term' => $term,
        ])
            ->setPaper('a4')
            ->stream($filename);
    }
}
