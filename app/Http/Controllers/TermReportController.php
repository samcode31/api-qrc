<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table1;
use App\Models\TermReport;

class TermReportController extends Controller
{
    public function show(){        
        return TermReport::join('terms', 'terms.id', 'term_reports.term')
        ->select('year', 'term_reports.term', 'terms.title', 'posted', 'date_posted')
        ->orderBy('year', 'desc')
        ->orderBy('term', 'desc')
        ->get();
    }

    public function store(Request $request){
        $term_report = TermReport::where([
            ['year', $request->year],
            ['term', $request->term]
        ])->first();

        $term_report->posted = $request->posted;
        $term_report->date_posted = $request->date_posted;
        $term_report->save();

        return $term_report;
    }

    public function postedReports()
    {
        $table1Records = Table1::join(
            'students',
            'table1.student_id',
            'students.id'
        )
        ->select(
            'year',
            'term'
        )
        ->whereBetween('students.class_id', ['0%', '7%'])
        ->orderBy('year', 'desc')
        ->orderBy('term', 'desc')
        ->distinct()
        ->get();

        foreach($table1Records as $table1Record)
        {
            TermReport::create([
                'year' => $table1Record->year,
                'term' => $table1Record->term,
                'posted' => 1
            ]);
        }

        return $table1Records;
    }
}
