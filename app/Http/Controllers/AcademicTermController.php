<?php

namespace App\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Models\TermReport;
use Illuminate\Http\Request;
use App\Models\AcademicTermDetail;
use Carbon\CarbonPeriod;
use App\Models\StudentAttendance;
use App\Models\StudentAttendanceArchive;
use App\Models\Table1;


class AcademicTermController extends Controller
{
    public function show()
    {
        $data = [];

        $currentMonth = date("n");
        $currentYear = date("Y");
        $academicYear = $currentYear.($currentYear+1);
        if($currentMonth < 9) $academicYear = ($currentYear-1).($currentYear);

        $data['terms'] = Term::select(
            'id', 
            'title',
        )->get();

        $data['terms_archive'] = AcademicTerm::select(
            'academic_year_id',
            'term',
            'possible_attendance',
            'new_term_beginning'
        )
        ->orderBy('academic_year_id', 'desc')
        ->orderBy('term', 'desc')
        ->get();

        $academicTermArchives = Table1::select(
            'year',
            'term',
            'new_term_beginning',
            'possible_attendance'
        )
        ->orderBy('year', 'desc')
        ->orderBy('term', 'desc')
        ->distinct()
        ->get();

        foreach($academicTermArchives as $termArchive)
        {
            $year = $termArchive->year;
            $term = $termArchive->term;
            $academicYearId = $year.($year+1);
            $newTermBeginning = $termArchive->new_term_beginning;
            $possibleAttendance = $termArchive->possible_attendance;
            
            $academicTermRecord = AcademicTerm::where([
                ['academic_year_id', $academicYearId],
                ['term', $term]
            ])
            ->first();

            if($academicTermRecord) continue;

            $academicTermRecord = new AcademicTerm([
                'academic_year_id' => $academicYearId,
                'term' => $term,
                'new_term_beginning' => $newTermBeginning,
                'possible_attendance' => $possibleAttendance
            ]);

            $data['terms_archive']->push($academicTermRecord);

        }

        $currentTerm = AcademicTerm::whereIsCurrent(1)
        ->first();

        $academicTerms = AcademicTerm::get();

        if(!$currentTerm && sizeof($academicTerms) == 0)
        {
            //no records in Academic Term
            $currentTerm = AcademicTerm::create([
                "academic_year_id" => $academicYear,
                "term" => 1,
                "new_term_beginning" => substr($academicYear,-4)."-01-02",
                "possible_attendance" => 100,
                "is_current" => 1
            ]);
        }

        if(!$currentTerm && sizeof($academicTerms) > 0){
            $currentTerm = AcademicTerm::orderBy('academic_year_id', 'desc')
            ->orderBy('term', 'desc')
            ->first();

            $currentTerm->is_current = 1;
            $currentTerm->save();
            

        }
        // return $this->possibleAttendance($currentTerm);
        // $possibleAttendance = $this->possibleAttendance($currentTerm);
        // if($possibleAttendance)
        // {
        //     $currentTerm->possible_attendance = $this->possibleAttendance($currentTerm);
        //     $currentTerm->save();
        // }
        $currentTerm->save();
        $data['current_term'] = AcademicTerm::whereIsCurrent(1)->first();
        return $data;
    }

    private function possibleAttendance ($termRecord)
    {
        $today = date('Y-m-d');
        $academicYearId = $termRecord ? $termRecord->academic_year_id : null;
        $term = $termRecord ? $termRecord->term : null;
        $termStartDate = null;
        $termEndDate = null;
        $year = $termRecord ? substr($termRecord->academic_year_id, 0, 4) : null;
        $validDates = array();
        $sessionsTotal = 0;

        // $termDetailRecord = AcademicTermDetail::whereDate('term_start', '<=', $today)
        // ->whereDate('term_end', '>=', $today)
        // ->first();

        $termDetailRecord = AcademicTermDetail::where([
            ['academic_year_id', $academicYearId],
            ['term', $term]
        ])
        ->first();

        if(!$termDetailRecord) return;

        $termStartDate = $termDetailRecord->term_start;
        $termEndDate = $termDetailRecord->term_end;

        //remove weekends from start date to today
        $dates = collect(CarbonPeriod::create($termStartDate, $today)->toArray())
        ->filter(fn ($date) => $date->isWeekday())
        ->map(fn ($date) => $date->format('Y-m-d'))
        ->toArray();

        //sort dates in descending order
        rsort($dates);

        //check for valid current term dates
        foreach($dates as $date){
            if($date <= $termEndDate) $validDates[] = $date;
        }

        foreach($validDates as $date)
        {
            $studentAttendanceArchiveRecord = StudentAttendanceArchive::where('date_scanned', $date)
            ->first();

            $studentAttendanceRecord = StudentAttendance::where('date_scanned', $date)
            ->first();

            if($studentAttendanceArchiveRecord) $sessionsTotal += 2;

            if(!$studentAttendanceArchiveRecord && $studentAttendanceRecord) $sessionsTotal += 2;
        }    

        return $sessionsTotal;
    }

    public function showAll ()
    {
        return Term::all();
    }

    public function store(Request $request)
    {
        $academicYear = AcademicYear::whereId($request->academic_year_id)->first();
        if(!$academicYear){
            $academicYear = AcademicYear::create();
            $academicYear->id = $request->academic_year_id;
            $academicYear->save();
        }

        AcademicTerm::query()->update(['is_current' => 0]);
        $academicTerm = AcademicTerm::updateOrCreate(
            [
            "academic_year_id" => $request->academic_year_id,
            "term" => $request->term 
            ],
            $request->all()
        );

        $academicYearStart = substr($request->academic_year_id, 0, 4);

        TermReport::updateOrCreate(
            [
               'year' => $academicYearStart,
               'term' => $request->term 
            ]
        );
            
        return $academicTerm;
       
    }
}
