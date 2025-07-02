<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table1;
use App\Models\Table2;
use App\Models\Term;
use App\Models\FormClass;
use App\Models\Weighting;

class RankSheetController extends Controller
{
    private $pdf;  
    private $participationMaxTotal = 25;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }    

    public function show(Request $request)
    {  
        //return $this->summaryData($year, $term, $class_id);   
        $year = $request->year;
        $term = $request->term;
        $class_id = $request->classId;   
        date_default_timezone_set('America/Caracas');
        
        $termRecord = Term::where('id', $term)
        ->first();
        $report_term = $termRecord ? $termRecord->title : null;

        $logo = public_path('/imgs/logo.png');        
        $school = config('app.school_name');        
        $address = config('app.school_address_line_1');   
        $contact = config('app.school_contact_line_1');         
        $border = 1; 

        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);              
        $this->pdf->AddPage('P', 'Letter');
        $this->pdf->Image($logo, 10, 8, 25);
        $this->pdf->SetFont('Times', 'B', '18');
        $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 4, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 4, $contact, 0, 'C' );
        $this->pdf->Ln(8);

        $this->pdf->SetFont('Times', 'B', 12);
        $this->pdf->MultiCell(0, 6, 'CLASS SUMMARY SHEET-RANK', 0, 'C' );        

        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->SetLineWidth(1);
        $this->pdf->Line($x, $y, 206, $y);
        $this->pdf->SetLineWidth(0.2); 
        //$this->pdf->Ln(4);

        $border = 0; 
        $this->pdf->SetFont('Times', '', 13);
        $this->pdf->Cell(49, 9, $class_id, $border, 0, 'L');
        $this->pdf->Cell(49, 9, $year.'-'.($year+1), $border, 0, 'L');
        $this->pdf->Cell(49, 9, $report_term, $border, 0, 'L');
        $this->pdf->Cell(49, 9, 'End of Term', $border, 0, 'C');
        $this->pdf->Ln();

        $border = 'TB';
        $this->pdf->SetFont('Times', 'B', 11); 
        $this->pdf->Cell(25, 8, 'Student ID', $border, 0, 'L');
        $this->pdf->Cell(51, 8, 'Name', $border, 0, 'L');
        $this->pdf->Cell(30, 8, 'Avg (%)', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Rank', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Sessions Absent', $border, 0, 'C');
        $this->pdf->Cell(30, 8, 'Sessions Late', $border, 0, 'C');
        $this->pdf->Ln();
        $this->pdf->Ln(2);

        $summary_data =  $this->summaryData($year, $term, $class_id);
        // return $summary_data; 

        foreach($summary_data as $index => $record){
            if($index%2 == 0)$this->pdf->SetFillColor(255,255,255);
            else $this->pdf->SetFillColor(220,220,220);

            if($record['average'] == 0){
                $student_average = '-';
                $rank = '-';
            }
            else{
                $student_average = ($record['average'] == 0);
                $rank = $record['rank'];
            }
            $student_average = ($record['average'] == 0) ? '-' : number_format($record['average'],1);
            
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(25, 5, $record['student_id'], 0, 0, 'L', true);
            $this->pdf->Cell(51, 5, $record['name'], 0, 0, 'L', true);
            $this->pdf->Cell(30, 5, $student_average, 0, 0, 'C', true);
            $this->pdf->Cell(30, 5, $rank, 0, 0, 'C', true);
            $this->pdf->Cell(30, 5, $record['sessions_absent'], 0, 0, 'C', true);
            $this->pdf->Cell(30, 5, $record['sessions_late'], 0, 0, 'C', true);
            $this->pdf->Ln();
        }

        $this->pdf->SetY(-15);
        $this->pdf->SetFont('Times','I',8);
        
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();
        $this->pdf->SetLineWidth(0.6);
        $this->pdf->SetDrawColor(190,190,190);
        $this->pdf->Line($x, $y, 206, $y);
        $this->pdf->SetLineWidth(0.2);

        $this->pdf->Cell(98, 6, date("l, F j, Y"), 0, 0, 'L');
        $this->pdf->Cell(98, 6, 'Page '.$this->pdf->PageNo().'of {nb}', 0, 0, 'R');



        $this->pdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function summaryData($year, $term, $class_id){
        $data = [];
        $students_registered = Table1::join(
            'students', 
            'students.id', 
            'table1.student_id'
        )
        ->select(
            'student_id', 
            'first_name', 
            'last_name', 
            'times_absent', 
            'times_late'
        )
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class_id]
        ])
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->get();

        $formClassRecord = FormClass::where('id', $class_id)
        ->first();

        $formLevel = $formClassRecord ? $formClassRecord->form_level : null;
        $averages = array();
        foreach($students_registered as $student){
            $student_record = []; 
            $student_id = $student->student_id;
            $student_record['student_id'] = $student_id;
            $student_record['name'] = $student->last_name.', '.$student->first_name;
            $student_record['average'] =  $this->studentAverage($year, $term, $student_id, $formLevel, $averages);
            // $student_record['rank'] = $this->rank($students_registered, $year, $term, $student_average, $formLevel);
            $student_record['sessions_absent'] = $student->times_absent;
            $student_record['sessions_late'] = $student->times_late;
            array_push($data, $student_record);
        }

        foreach($data as &$record){
            $record['rank'] = $this->rank($averages, $record['average']);
        }

        unset($record);

        $data = $this->sort($data);

        return $data;
    }

    private function studentAverage($year, $term, $student_id, $formLevel, &$averages)
    {
        $subjects = 0; 
        $totalMarks = 0; 
        $average = 0;
        
        $table2_records = Table2::select('exam_mark', 'course_mark')
        ->where([
            ['year', $year],
            ['term', $term],
            ['student_id', $student_id]
        ])->get();

        foreach($table2_records as $record){
            $subjects++;
            $courseMark = $record->course_mark;
            $examMark = $record->exam_mark;

            if($term == 1 && (
                $formLevel == 5 ||
                $formLevel == 6 ||
                $formLevel == 7
            )
            ){
                $totalMarks += is_numeric($courseMark) ? $courseMark : 0; 
            }
            elseif($term == 2 && (
                $formLevel == 1 ||
                $formLevel == 2 ||
                $formLevel == 3 ||
                $formLevel == 4
            )){
                $totalMarks += is_numeric($courseMark) ? $courseMark : 0; 
            }
            elseif($term == 2 && (
                    $formLevel == 5 ||
                    $formLevel == 6 ||
                    $formLevel == 7
                )
            )
            {
                $totalMarks += is_numeric($examMark) ? $examMark : 0; 
            }
            else {
                $totalMarks += is_numeric($courseMark) ? number_format($courseMark*0.3,1) : 0; 
                $totalMarks += is_numeric($examMark) ? number_format($examMark,1) : 0;
            }
            
        }

        if($subjects != 0){
            $average = $totalMarks / $subjects; 
            $averages[] = $average;          
        }
        
        return $average = $average != 0 ? $average : 0;
    }

    private function rank($averages, $key)
    {
        rsort($averages);
        foreach($averages as $index => $average){
            if($average == $key){
                return $index + 1;
            }
        }
        return null;
    }

    private function sort($array){
        $l=0; $m=0; $keyAvg=0; $keyArray=[]; $n = sizeof($array);        
        for($l = 1; $l < $n; $l++){
            $keyId = $array[$l]['student_id'];
            $keyName = $array[$l]['name']; 
            $keyAvg = $array[$l]['average'];
            $keyRank = $array[$l]['rank'];
            $keyAbsent = $array[$l]['sessions_absent'];
            $keyLate = $array[$l]['sessions_late'];           
            
            $m=$l-1;
            while($m >=0 && ($keyRank && $keyRank < $array[$m]['rank'])){
                $array[$m+1] = $array[$m];
                --$m;
            }
            if($m+1 == $l) continue;            
            $keyArray['student_id']=$keyId;
            $keyArray['average']=$keyAvg;
            $keyArray['rank']=$keyRank;
            $keyArray['sessions_absent']=$keyAbsent;
            $keyArray['sessions_late']=$keyLate;            
            $keyArray['name']=$keyName;
            $array[$m+1]=$keyArray;
        }
        return $array;
    }
}
