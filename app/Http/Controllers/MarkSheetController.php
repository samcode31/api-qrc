<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\FormTeacher;
use App\Models\Table1;
use App\Models\Table2;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\FormDean;
use Illuminate\Support\Facades\DB;
use App\Models\Weighting;

class MarkSheetController extends Controller
{
    protected $pdf;
    private $maxTermTest = 100;
    private $participationMaxTotal = 25;
    

    public function __construct(\App\Models\Pdf $pdf)
    {        
        $this->pdf = $pdf;
    }

    public function show(Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $year = $request->year;
        $term = $request->term;
        $class_id = $request->classId;
        $logo = public_path('/imgs/logo.png');
        $school = strtoupper(config('app.school_name'));        
        $address = config('app.school_address_line_1');
        $contact = config('app.school_contact_line_1');       
        $passMark = 50;
        $max_cols=22; 
        $max_rows=15;
        $averages = array();

        $data = $this->markSheetData($year, $term, $class_id, $averages);
        //return $data;
        $distinct_subjects = $this->distinctSubjects($year, $term, $class_id);
        $form_teacher_assignments = $this->formTeachers($year, $class_id);
        // return $form_teacher_assignments;
        $academicYearId = $year.($year+1);
        $form_dean_assignments = FormDean::where([
            ['academic_year_id', $academicYearId],
            ['form_class_id', $class_id]
        ])
        ->join(
            'employees',
            'form_deans.employee_id',
            'employees.id'
        )
        ->select(
            DB::raw(
                "CONCAT(LEFT(first_name,1), '. ', last_name) AS name"
            )
        )
        ->pluck('name')
        ->toArray();
        // ->get();
        // return $form_dean_assignments;
        

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);
        $this->pdf->SetAutoPageBreak(false);

        foreach($data as $key => $record){
            if($key%$max_rows==0){
                if($key!=0){                    
                    $this->pdf->SetY(-15);
                    $this->pdf->SetFont('Times','I',8);
                    $this->pdf->Cell(168, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
                    $this->pdf->Cell(168, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
                }
                if(sizeof($distinct_subjects) > $max_cols){
                    $subjectColumnsWidth = 16 * sizeof($distinct_subjects);
                    $pageWidth = 132 + $subjectColumnsWidth;
                    $pageHeight = $pageWidth/1.6;
                    $rowsHeight = $pageHeight - 111;
                    $max_rows = floor($rowsHeight/7);
                    //$this->pdf->AddPage('L', array($pageWidth, 215.9));
                    $this->pdf->AddPage('L', array($pageWidth, $pageHeight));
                    
                } 
                else $this->pdf->AddPage('L', 'Legal');
                $this->pdf->Image($logo, 10, 6, 28);
                $this->pdf->SetFont('Times', 'B', '18');
                $this->pdf->MultiCell(0, 8, $school, 0, 'C' );
                $this->pdf->SetFont('Times', 'I', 10);
                $this->pdf->MultiCell(0, 4, $address, 0, 'C' );
                $this->pdf->MultiCell(0, 4, $contact, 0, 'C' );
                $this->pdf->Ln(4);

                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->MultiCell(0,6, 'CLASS SUMMARY MARK SHEET', 0, 'C');                
                $this->pdf->Ln(10);

                $border=0;
                $this->pdf->SetDrawColor(220,220,220);
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(14,8,'Class: ',$border,0,'L');
                $this->pdf->SetFont('Times', '', 12);
                $this->pdf->Cell(64,8, $class_id ,$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(24,8,'Form Dean: ',$border,0,'L');
                $this->pdf->SetFont('Times', 'I', 12);
                $this->pdf->Cell(70,8,implode(" / ", $form_dean_assignments),$border,0,'L');                
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(30,8,'Form Teacher:',$border,0,'L');
                $this->pdf->SetFont('Times', 'I', 12);
                $this->pdf->Cell(80,8,implode(" / ", $form_teacher_assignments),$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Cell(20,8,"Term $term : ",$border,0,'R');
                $this->pdf->SetFont('Times', '', 12);
                $this->pdf->Cell(34,8, $year.'-'.($year+1),$border,0,'L');
                $this->pdf->SetFont('Times', 'B', 12);
                $this->pdf->Ln();

                $x = $this->pdf->GetX();
                $y = $this->pdf->GetY();
                
                $this->pdf->MultiCell(62, 35, "STUDENT'S NAME", 'TLR', 'C');
                $this->pdf->SetXY($x+62, $y);
                $this->pdf->SetFont('Times', 'B', 10);
                $distinct_subject_count = 0;
                foreach($distinct_subjects as $subject){
                    $distinct_subject_count++;
                    $x = $this->pdf->GetX();
                    $y = $this->pdf->GetY();
                    $NbLines = $this->pdf->NbLines(10,$subject->abbr);
                    if($NbLines == 1) $this->pdf->MultiCell(10,15,$subject->abbr,1,'C');
                    else $this->pdf->MultiCell(10,7.5,$subject->abbr,1,'C');
                    $this->pdf->SetXY($x+10,$y);
                }
                if($distinct_subject_count < $max_cols){
                    for($i=$distinct_subject_count; $i<$max_cols; $i++ ){                
                        $x = $this->pdf->GetX();
                        $y = $this->pdf->GetY();
                        $this->pdf->MultiCell(10,15,'',1,'C');
                        $this->pdf->SetXY($x+10,$y);
                    }
                }
                $this->pdf->Cell(30,15,"AGGREGATE",1,0,'C');
                $this->pdf->MultiCell(20,7.5,"NUMBER\t\nOF TIMES",1,'C');
                $x = $this->pdf->GetX();
                $this->pdf->setX($x+62);
                $this->pdf->SetFont('Times','',10);

                foreach($distinct_subjects as $subject){
                    $this->pdf->Cell(10,20,"",'BR',0,'C');
                    $this->pdf->RotateText("Total",90,9);
                }

                if($distinct_subject_count < $max_cols){
                    for($i=$distinct_subject_count; $i<$max_cols; $i++ ){                
                        $this->pdf->Cell(10,20,"",'BR',0,'C');                
                    }
                }
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Marks",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Avg %",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Rank",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Absent",90,10);
                $this->pdf->Cell(10,20,"",1,0,'C');
                $this->pdf->RotateText("Late",90,10);
                $this->pdf->Ln();
            }
            if($key%2==0) $this->pdf->SetFillColor(239,240,242);
            else $this->pdf->SetFillColor(255,255,255);
            $this->pdf->Cell(8,7,$key+1,'TLB','0','C',true);
            $this->pdf->Cell(54,7,$record['name'],'TLB',0,'L',true);
            $term_marks = $record['term_marks'];

            foreach($term_marks as $mark){                
                if(
                    is_numeric($mark['exam_mark']) &&
                    $mark['exam_mark'] < $passMark
                ) $this->pdf->SetTextColor(255,0,0);
                $this->pdf->Cell(10,7,$mark['exam_mark'],1,0,'C',true);
                $this->pdf->SetTextColor(0,0);
            }

            if($distinct_subject_count<$max_cols){
                for($i=count($term_marks); $i<$max_cols; $i++ ){                
                    $this->pdf->Cell(10,7,'',1,0,'C',true);
                }
            }
            $this->pdf->Cell(10,7,$record['total_marks'],1,0,'C',true);
            
            if($record['average'] < $passMark) $this->pdf->SetTextColor(255,0,0);
            $this->pdf->Cell(10,7,$record['average'],1,0,'R',true);
            $this->pdf->SetTextColor(0);
            $this->pdf->Cell(10,7,$record['rank'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['sessions_absent'],1,0,'C',true);
            $this->pdf->Cell(10,7,$record['sessions_late'],1,0,'C',true);
            $this->pdf->Ln();            
        }
        
        $this->pdf->SetY(-15);
        $this->pdf->SetFont('Times','I',8);
        if(sizeof($distinct_subjects) > $max_cols){
            $cellWidth = ($pageWidth/2)-10;
            $this->pdf->Cell($cellWidth, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
            $this->pdf->Cell($cellWidth, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
        }
        else{
            $this->pdf->Cell(168, 6, 'Report Generated: '.date("d/m/Y h:i:sa"), 0, 0, 'L');
            $this->pdf->Cell(168, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 0, 0, 'R');
        }
        

        $this->pdf->Output('I', 'Class Summary Mark Sheet.pdf');
    }

    private function distinctSubjects($year, $term, $class_id)
    {
        $distinct_subjects = Table2::join('table1', 'table1.student_id', 'table2.student_id')
        ->join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('subject_id', 'abbr')
        ->where([
            ['table1.year', $year],
            ['table1.term', $term],
            ['class_id', $class_id],
            ['table2.year', $year],
            ['table2.term', $term]
        ])
        ->distinct()
        ->orderBy('abbr')
        ->get();

        return $distinct_subjects;
    }

    private function markSheetData($year, $term, $class_id, &$averages)
    {
        $data = [];
        
        $distinct_subjects = Table2::join('table1', 'table1.student_id', 'table2.student_id')
        ->join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('subject_id', 'abbr')
        ->where([
            ['table1.year', $year],
            ['table1.term', $term],
            ['class_id', $class_id],
            ['table2.year', $year],
            ['table2.term', $term]
        ])
        ->distinct()
        ->orderBy('abbr')
        ->get();

        $students_registered = Table1::join('students', 'students.id', 'table1.student_id')
        ->select('student_id', 'first_name', 'last_name', 'times_late', 'times_absent')
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class_id]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        //return $students_registered;

        $form_class = FormClass::where('id', $class_id)
        ->first();

        $form_level = $form_class ? $form_class->form_level : null;

        foreach($students_registered as $student){
            $student_record = []; $student_term_marks = [];
            $total_marks = 0; $total_subjects = 0;

            $student_id = $student->student_id;
            foreach($distinct_subjects as $subject){
                $mark_record = [];
                $subject_id = $subject->subject_id;

                $table2_record = Table2::where([
                    ['student_id', $student_id],
                    ['year', $year],
                    ['term', $term],
                    ['subject_id', $subject_id]
                ])
                ->first();

                if(!$table2_record)
                {
                    $mark_record['exam_mark'] = null;
                    continue;
                } 

                
                $course_mark = $table2_record->course_mark;
                $exam_mark = $table2_record->exam_mark;
                $mark_record['course_mark'] = is_null($course_mark) ? 'Ab' : $course_mark;
                $mark_record['exam_mark'] = is_null($exam_mark) ? 'Ab' : $exam_mark;
                if($term == 1 && (
                        $form_level == 5 ||
                        $form_level == 6 ||
                        $form_level == 7
                    )
                ){
                    $total_marks += is_numeric($course_mark) ? $course_mark : 0; 
                    $mark_record['exam_mark'] = is_null($course_mark) ? 'Abs' :$course_mark;
                }
                elseif($term == 2 && (
                    $form_level == 1 ||
                    $form_level == 2 ||
                    $form_level == 3 ||
                    $form_level == 4
                )){
                    $total_marks += is_numeric($course_mark) ? $course_mark : 0; 
                    $mark_record['exam_mark'] = is_null($course_mark) ? 'Abs' :$course_mark;
                }
                elseif($term == 2 && (
                        $form_level == 5 ||
                        $form_level == 6 ||
                        $form_level == 7
                    )
                )
                {
                    $total_marks += is_numeric($exam_mark) ? $exam_mark : 0; 
                    $mark_record['exam_mark'] = is_null($exam_mark) ? 'Abs' : $exam_mark;
                }
                else {
                    $total_marks += is_numeric($course_mark) ? number_format($course_mark*0.3,1) : 0; 
                    $total_marks += is_numeric($exam_mark) ? number_format($exam_mark*0.7,1) : 0;
                    $mark_record['exam_mark'] = is_null($course_mark) && is_null($exam_mark) ? 'Abs' : $course_mark*0.3 + $exam_mark*0.7;
                }

                $total_subjects++;
            
                
                $mark_record['subject'] = $subject->abbr;
                $mark_record['subject_id'] = $subject_id;
                array_push($student_term_marks, $mark_record);
            }

            $average = ($total_subjects != 0) ? number_format($total_marks/$total_subjects, 2) : null;
            if($average) $averages[] = $average;
            $student_record['total_marks'] = ($total_marks != 0) ? $total_marks : null;
            $student_record['average'] = $average;
            $student_record['rank'] = $this->rank($averages, $average);
            $student_record['sessions_absent'] = $student->times_absent;
            $student_record['sessions_late'] = $student->times_late;
            $student_record['term_marks'] = $student_term_marks;
            $student_record['name'] = $student->last_name.', '.$student->first_name;
            array_push($data, $student_record);
        }

        return $data;
    }

    private function rank($averages, $rank_average)
    {
        rsort($averages);
        foreach($averages as $index => $average){
            if($average == $rank_average ) return $index + 1;
        }
        return null;
    }

    private function formTeachers($year, $class_id)
    {
        $form_teacher_assignments = [];
        $academic_year_id = $year.($year+1);
        $form_teachers = FormTeacher::join('employees', 'employees.id', 'form_teachers.employee_id')
        ->select('first_name', 'last_name')
        ->where([
            ['form_class_id', $class_id],
            ['academic_year_id', $academic_year_id]
        ])
        ->get();

        foreach($form_teachers as $teacher){
            $teacher_assigned = $teacher->first_name[0].'. '.$teacher->last_name;
            array_push($form_teacher_assignments, $teacher_assigned);
        }

        return $form_teacher_assignments;
    }
    

    public function download ($year, $term, $class_id)
    {       
        $subjectMarksColStart = 8; $colStart = 7;

        $dataSummary = $this->spreadsheetDataSummary($year, $term, $class_id);
        // return $dataSummary;
        $dataMarks = $this->spreadSheetDataMarks($year, $term, $class_id);
        // return $dataMarks;
        $distinct_subjects = $this->distinctSubjects($year, $term, $class_id);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $arrayDataHeaders = [
            "Name", 
            "Class", 
            "Year", 
            "Term", 
            "Abs",
            "Late",
            "Avg%",
            "GPA",
        ];

        foreach($distinct_subjects as $subject){
            array_push($arrayDataHeaders, $subject->abbr);
        }       

        $sheet->fromArray($arrayDataHeaders);
        $sheet->fromArray(
            $dataSummary,
            NULL,
            'A2'
        );
        
        
        $row = 1; 
        // $oneDigitSpace = 7; $twoDigitSpace = 6; $threeDigitSpace = 5; $nullDigitSpace = 8;
        foreach($dataMarks as $markRecord){
            $col = $colStart;
            $row++;
            $sheet->setCellValueByColumnAndRow($col, $row, $markRecord["average"]);
            $sheet->setCellValueByColumnAndRow(++$col, $row, $markRecord["gpa"]);
            foreach($markRecord["marks"] as $record){
                $subjectTotal = null;
                
                if($term == 2 && $class_id[0] <= 4)
                {
                    if(is_numeric($record["course_mark"])) $subjectTotal += $record["course_mark"];
                    $cellValue = $subjectTotal;
                }

                else
                {
                    if(is_numeric($record["course_mark"])) $subjectTotal += $record["course_mark"];
                    if(is_numeric($record["exam_mark"])) $subjectTotal += $record["exam_mark"];
                    if(!is_null($subjectTotal)) $subjectTotal = number_format($subjectTotal/2,0);
    
                    $cellValue = $record["course_mark"]."   ".$record["exam_mark"]." | ".$subjectTotal;
                    if(is_null($subjectTotal)){
                        $cellValue = $record["course_mark"]."   ".$record["exam_mark"];
                    }

                }


                // if($record["course_mark"] == 'Ab'){
                //     $cellValue = $record["course_mark"].str_repeat(' ', 3).
                //     $record["exam_mark"];
                // }
                // elseif($record["course_mark"] < 10){
                //     $cellValue = $record["course_mark"].str_repeat(' ', 6).
                //     $record["exam_mark"];
                // }
                // else{
                //     $cellValue = $record["course_mark"].str_repeat(' ', 4).
                //     $record["exam_mark"];
                // }
                
                
                $sheet->setCellValueByColumnAndRow(
                    ++$col, 
                    $row, 
                    $cellValue
                );
            }
        }
        

        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        $sheet->getStyle('B1:'.$highestColumn.'1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
        for($row = 2; $row <= $highestRow; ++$row){
            for($col = 2; $col < $subjectMarksColStart; ++$col){
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $sheet->getStyle($column.$row)
                ->getAlignment()
                ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            }
        }

        for($row = 2; $row <= $highestRow; ++$row){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colStart);
            $sheet->getStyle($column.$row)
            ->getNumberFormat()->setFormatCode('#0.00');
        }

        for($col = 1; $col < $subjectMarksColStart; ++$col){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        for($col = $subjectMarksColStart; $col <= $highestColumnIndex; $col++){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setWidth(13);
        }
        
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'argb' => 'FFBFBFBF'
                ]
            ]
        ];

        // $sheet->getStyle('A1:'.$highestColumn.'1')->getFill()
        // ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        // ->getStartColor()->setARGB('FFBFBFBF');        

        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray($styleArray);

        $sheet->freezePane('A2');

        $file = $class_id." Mark Sheet.xlsx";
        // $filePath = './files/'.$file;
        $filePath = storage_path('app/public/'.$file);
        
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

    private function spreadSheetDataSummary ($year, $term, $class_id)
    {
        $data = [];

        $students_registered = Table1::join('students', 'students.id', 'table1.student_id')
        ->select('student_id', 'first_name', 'last_name', 'table1.*')
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class_id]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();
        
        switch($term){
            case 1:
                $term = "Term 1";
                break;
            case 2:
                $term = "Term 2";
                break;
            case 3:
                $term = "Term 3";
                break;        
        }
        

        foreach($students_registered as $student){
            $student_record = [];

            array_push($student_record, $student->last_name.', '.$student->first_name);
            array_push($student_record, $student->class_id);
            array_push($student_record, $year.'-'.($year+1));
            array_push($student_record, $term);
            array_push($student_record, $student->times_absent);
            array_push($student_record, $student->times_late);
            array_push($data, $student_record);
        }
        
        return $data;
    }

    private function spreadsheetDataMarks ($year, $term, $class_id) 
    {
        $data = [];

        $distinct_subjects = Table2::join('table1', 'table1.student_id', 'table2.student_id')
        ->join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('subject_id', 'abbr')
        ->where([
            ['table1.year', $year],
            ['table1.term', $term],
            ['class_id', $class_id],
            ['table2.year', $year],
            ['table2.term', $term]
        ])
        ->distinct()
        ->orderBy('abbr')
        ->get();
            
        $form_class = FormClass::where('id', $class_id)
        ->first();

        $form_level = $form_class ? $form_class->form_level : null;
        

        $students_registered = Table1::join('students', 'students.id', 'table1.student_id')
        ->select('student_id', 'first_name', 'last_name', 'table1.class_id')
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class_id]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($students_registered as $student){
            $student_record = []; 
            $student_marks = [];
            $total_marks = 0; 
            $total_subjects = 0;
            $totalGPA = 0; 

            $student_id = $student->student_id;
            foreach($distinct_subjects as $subject){
                $mark_record = [];                
                $subject_id = $subject->subject_id;

                $table2_record = Table2::where([
                    ['student_id', $student_id],
                    ['year', $year],
                    ['term', $term],
                    ['subject_id', $subject_id]
                ])
                ->first();

                if($table2_record){
                    $course_mark = $table2_record->course_mark;
                    $exam_mark = $table2_record->exam_mark;
                    $subjectGPA = $this->subjectGPA(($course_mark+$exam_mark)/2);
                    $mark_record['course_mark'] = is_null($course_mark) ? 'Ab' : $course_mark;                   
                    $mark_record['exam_mark'] = is_null($exam_mark) ? 'Ab' : $exam_mark;
                    
                    if(
                        $term == 2 && (
                            $form_level == 1 ||
                            $form_level == 2 ||
                            $form_level == 3 ||
                            $form_level == 4
                        ) &&
                        is_numeric($course_mark)
                    ){
                        $total_marks += $course_mark;
                        $mark_record['exam_mark'] = "--";
                        $subjectGPA = $this->subjectGPA($course_mark);
                    }                    
                    else{
                        $total_marks += ($exam_mark + $course_mark) / 2;
                    }
                    $total_subjects++;
                    $totalGPA += $subjectGPA;
                }
                else{
                    $mark_record['course_mark'] = "--";
                    $mark_record['exam_mark'] = "--";
                }
               
                array_push($student_marks, $mark_record);
            }

            $average = ($total_subjects != 0) ? $total_marks/$total_subjects : null; 
            $averageGPA = ($total_subjects != 0) ? $totalGPA/$total_subjects : null;                 
            $student_record['average'] = $average;
            $student_record['gpa'] = number_format($averageGPA, 2);
            $student_record['marks'] = $student_marks;
            array_push($data, $student_record);
        }

        return $data;

    }

    private function subjectGPA($mark)
    {
        $gpa = 0;
        $scoreGPA = [
            ['min' => 90, 'gpa' => '4.00'],
            ['min' => 85, 'gpa' => '3.66'],
            ['min' => 80, 'gpa' => '3.33'],
            ['min' => 75, 'gpa' => '3.00'],
            ['min' => 70, 'gpa' => '2.66'],
            ['min' => 65, 'gpa' => '2.33'],
            ['min' => 60, 'gpa' => '2.00'],
            ['min' => 55, 'gpa' => '1.66'],
            ['min' => 50, 'gpa' => '1.33'],
            ['min' => 45, 'gpa' => '1.00'],
            ['min' => 0, 'gpa' => '0.00'],
        ];

        foreach($scoreGPA as $range)
        {
            if($mark >= $range['min'])
            {
                $gpa = $range['gpa'];
                break;
            }
        }

        return $gpa;
    }
}
