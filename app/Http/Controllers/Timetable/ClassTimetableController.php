<?php

namespace App\Http\Controllers\Timetable;

use App\Http\Controllers\Controller;
use App\Models\Core\SchoolClass;
use App\Models\Timetable\TimetableSlot;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class ClassTimetableController extends Controller
{
    /**
     * The week of a class as a printable PDF (A4 landscape).
     */
    public function __invoke(SchoolClass $schoolClass): Response
    {
        Gate::authorize('view-class-timetable', $schoolClass);

        return Pdf::loadView('timetable.pdf', [
            'schoolClass' => $schoolClass,
            'slots' => TimetableSlot::query()->ofClass($schoolClass->getKey())->get(),
        ])
            ->setPaper('a4', 'landscape')
            ->stream(Str::slug("emploi du temps {$schoolClass->name}").'.pdf');
    }
}
