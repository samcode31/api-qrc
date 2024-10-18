<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Table1;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    public function show () {
        $data = array();
        
        // $academicYears = AcademicYear::select('id')
        // ->order('id', 'desc')
        // ->all();

        // foreach($academicYears as $academicYear)
        // {
        //     $data[] = $academicYear;
        // }

        $academicYears = Table1::select(
            'year',
        )
        ->distinct()
        ->orderBy('year', 'desc')
        ->get();
        
        foreach($academicYears as $academicYear)
        {
            $year = $academicYear->year;
            $academicYearId = $year.($year+1);
            $data[] = array("id" => $year.($year+1));
        }

        return $data;
    }
}
