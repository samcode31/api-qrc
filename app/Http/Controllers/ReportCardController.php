<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\FormTeacher;
use App\Models\ReportCardLog;
use App\Models\Table1;
use App\Models\Table2;
use Codedge\pdf\pdf\pdf;
use App\Models\Weighting;

class ReportCardController extends Controller
{
    private $pdf;    

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show(Request $request)
    {
        $studentId = $request->studentId;
        $year = $request->year;
        $term = $request->term;
        $table1Record = Table1::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])->first();

        $table2Record = Table2::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])->get();

        return $table2Record[0]->subject;
    }

    public function create(Request $request)
    {
        date_default_timezone_set('America/Caracas');

        $studentId = $request->studentId;
        $year = $request->year;
        $term = $request->term;
        $className = $request->className;
        $classGroup = $request->classGroup;

        
        $logo = public_path('/imgs/logo.png');
        $principalSignature = public_path('/imgs/desnercorriapaul.png');
        $waterMarkLogo = public_path('/imgs/logo-report.png');
        $school = strtoupper(config('app.school_name')); 
        $addressLine1 = config('app.school_address_line_1');
        $addressLine2 = config('app.school_address_line_2');
        $contactLine1 = config('app.school_contact_line_1');
        $contactLine2 = config('app.school_contact_line_2');
        $red = config('app.school_color_red');
        $green = config('app.school_color_green');
        $blue = config('app.school_color_blue');

        $monthlyTestWeightRecord = Weighting::where([
            ['category', 'Montly Test'],
            ['year', '>=', $year],
            ['term', '>=', $term],
        ])
        ->first();

        $monthlyTestWeight = $monthlyTestWeightRecord ? $monthlyTestWeightRecord->weight : 0;


        $termTestWeightRecord = Weighting::where([
            ['category', 'Term Test'],
            ['year', '>=', $year],
            ['term', '>=', $term],
        ])
        ->first();

        $termTestWeight = $termTestWeightRecord ? $termTestWeightRecord->weight : 0;

        $participationWeightRecord = Weighting::where([
            ['category', 'Participation'], 
            ['year', '>=', $year],
            ['term', '>=', $term],
        ])
        ->first();

        $participationWeight = $participationWeightRecord ? $participationWeightRecord->weight : 0;
        

        $table1Records = Table1::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])->get();


        if($className && $classGroup)
        {
            $table1Records = Table1::where([
                ['year', $year],
                ['term', $term],
                ['class_name', $className],
                ['class_group', $classGroup],
            ])->get();
        }

        foreach($table1Records as $table1Record)
        {
            $formClassRecord = FormClass::where([
                ['class_name', $table1Record->class_name],
                ['class_group', $table1Record->class_group],
            ])->first();

            $formClassId = $formClassRecord ? $formClassRecord->id : null;

            $studentId = $table1Record->student_id;
            $newTermBeginning = $table1Record->new_term_beginning;
            $className = $table1Record->class_name;
            $times_absent = $table1Record->times_absent;
            $times_late = $table1Record->times_late;
            $possible_attendance = $table1Record->possible_attendance;
            $form_teacher_comments = $table1Record->comments;
            $term = $table1Record->term;       
            $year = $table1Record->year;
            $totalAverage = null;
            $projectMax = 25;
            $termTestMaxMark = 100;
            $totalMarks = 0;
            $total = 0;
            $totalSubjects = 0;
            $average = '';        
            $yearEnd = intval($year)+1;
            $academic_year = $year.' - '.$yearEnd;
            $dob = null;
            $pass_mark = 50;
            $total_marks = 0;
            $academic_year_id = $year.$yearEnd;
            $formTeachers = [];
            $name = $table1Record->student->first_name.' '.$table1Record->student->last_name;
            $maxMarkTotal = 0;
            $termTestTotal = 0;
            $termTestTotalPercentage = 0;
            $termTestWeighted = $table1Record->term_test;
            $participationWeighted = 0;
            $participationTotal = 0;
            $participationMaxTotal = 25;

            $sharesCooperates = $table1Record->shares_cooperates;
            $listensActively = $table1Record->listens_actively;
            $persistent = $table1Record->persistent;
            $acceptsChallenges = $table1Record->accepts_challenges;
            $prepared = $table1Record->prepared;

            $participationTotal = $sharesCooperates + $listensActively + $persistent + $acceptsChallenges + $prepared;
            $participationWeighted = floatval(round($participationTotal / $participationMaxTotal * $participationWeight, 0));

            $communication = $table1Record->communication;
            $cooperation = $table1Record->cooperation;
            $respect = $table1Record->respect;
            $responsibility = $table1Record->responsibility;
            $attitude = $table1Record->attitude;
            $judgement = $table1Record->judgement;
            $completeAssignment = $table1Record->complete_assignment;
            $classParticipation = $table1Record->class_participation;
            $selfConfidence = $table1Record->self_confidence;
            $punctual = $table1Record->punctual;

            $formTeacherRecords = FormTeacher::join(
                'employees', 
                'employees.id', 
                'form_teachers.employee_id'
            )
            ->select('employees.first_name', 'employees.last_name')
            ->where([
                ['form_class_id', $formClassId],
                ['academic_year_id', $academic_year_id]
            ])->get();

            foreach($formTeacherRecords as $formTeacher){
                array_push($formTeachers, $formTeacher->first_name[0].'. '.$formTeacher->last_name );
            }

            $table2Records = Table2::join(
                'subjects', 
                'table2.subject_id', 
                'subjects.id'
            )
            ->where([
                ['student_id', $studentId],
                ['year', $year],
                ['term', $term]
            ])
            ->select('table2.*', 'subjects.title')
            ->orderBy('subjects.title')
            ->get();  
            
            $border = 0;
            if($term == 1) $reportTerm = "Term 1";
            elseif($term == 2) $reportTerm = "Term 2";
            else $reportTerm = "Term 3";
    
            $this->pdf->SetMargins(10, 10);
            $this->pdf->SetAutoPageBreak(false);              
            $this->pdf->AddPage('P', 'Letter');

            $this->pdf->Image($logo, 10, 6, 23);
            $this->pdf->SetFont('Times', 'B', '15');
            $this->pdf->Image($waterMarkLogo, 30, 50, 150);
            $this->pdf->SetTextColor($red, $green, $blue);
            $x=$this->pdf->GetX();
            $this->pdf->SetX($x+23);
            $this->pdf->MultiCell(0, 6, $school, 0, 'C' );
            $this->pdf->SetTextColor(0,0,0);
            $this->pdf->SetFont('Times', 'I', 9);
            $x=$this->pdf->GetX();
            $this->pdf->SetX($x+23);
            $this->pdf->MultiCell(0, 4, $addressLine1, 0, 'C' );

            $this->pdf->SetTextColor(0,0,0);
            $this->pdf->SetFont('Times', 'B', 15);
            $this->pdf->Ln();
            $this->pdf->SetTextColor($red, $green, $blue);
            $this->pdf->MultiCell(0,6, 'Report Card', 0, 'C');
            $this->pdf->SetTextColor(0,0,0);
            $this->pdf->Ln();

            $border=0;
            $rowHeight=6;
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Cell(15, $rowHeight, "\tName: ", $border, 0, 'L');
            $this->pdf->SetFont('Times', '', 13);
            $this->pdf->Cell(50.3, $rowHeight, $name, $border, 0, 'L');
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Cell(15, $rowHeight, "\tClass: ", $border, 0, 'L');
            $this->pdf->SetFont('Times', '', 13);
            $this->pdf->Cell(30.3, $rowHeight, $className, $border, 0, 'L');
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->Cell(15, $rowHeight, "Term:", $border, 0, 'L');
            $this->pdf->SetFont('Times', '', 13);
            $this->pdf->Cell(5, $rowHeight, $term, $border, 0, 'L');
            $this->pdf->SetFont('Times', 'B', 13);
            $this->pdf->Cell(40.3, $rowHeight,  "Academic Year:", $border, 0, 'R');
            $this->pdf->SetFont('Times', '', 13);
            $this->pdf->Cell(25, $rowHeight,  $academic_year, $border, 0, 'R');
            $this->pdf->SetFont('Times', '', 12);
            $this->pdf->Ln(10);

            $this->pdf->SetFillColor(219, 219, 219);
            $this->pdf->SetFont('Times','B','10');
            $this->pdf->SetWidths(array(35, 15, 15, 15));
            $this->pdf->SetAligns(array('C', 'C', 'C', 'C'));
            $this->pdf->SetBorders(array(1,1,1,1));

            $xCol2=$this->pdf->GetX();
            $yCol2=$this->pdf->GetY();
            $this->pdf->Row(array("Subject", "Max Mark", "Earned", "%"), false);

            $this->pdf->SetAligns(array('L', 'C', 'C', 'C'));
            $this->pdf->SetBorders(array(1,1,1,1));
            $this->pdf->SetFillColor(255, 255, 255);
            $this->pdf->SetFont('Times', '', 10);

            foreach($table2Records as $index => $table2Record){
                $maxMarkTotal += $termTestMaxMark;
                $termTestTotal += is_numeric($table2Record["exam_mark"]) ? $table2Record["exam_mark"] : 0;
                $this->pdf->Row(array(
                    $table2Record['title'],
                    $termTestMaxMark,
                    $table2Record["exam_mark"],
                    $table2Record["exam_mark"]
                ), false);
            }
            $termTestTotalPercentage = number_format((($termTestTotal ?? 0) / ($maxMarkTotal === 0 ? 1 : $maxMarkTotal)) * 100, 0);
            // $termTestWeighted = number_format((($termTestTotal ?? 0) / ($maxMarkTotal ?? 1)) * $endOfTermWeight, 0);
            $this->pdf->Cell(35,6,"TOTAL", 1, 0);
            $this->pdf->Cell(15,6,$maxMarkTotal, 1, 0, 'C');
            $this->pdf->Cell(15,6,$termTestTotal, 1, 0, 'C');
            $this->pdf->Cell(15,6,"", 1, 0);
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(35,6,"Overall % on Term Test", 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(45,6,$termTestTotalPercentage, 1, 0, 'C');
            $this->pdf->Ln();

            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);

            $this->pdf->MultiCell(35,4, $termTestWeight.'% Contribution to End of Term Overall Grade', 1, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->SetXY($x+35,$y);
            $this->pdf->Cell(45,8,$termTestWeighted,1,0,'C');
            $this->pdf->Ln(15);

            $border=0;
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(60,6,"ATTENDANCE",$border,0,"C");
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(50,6,"Number of Sessions in the Term",1,0,"C");
            $this->pdf->Cell(10,6,$possible_attendance,1,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(50,6,"Number of Sessions Absent",1,0,"C");
            $this->pdf->Cell(10,6,$times_absent,1,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();

            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(50,6,"Number of Sessions Late",1,0,"C");
            $this->pdf->Cell(10,6,$times_late,1,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln(12);
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(60,6,"Grading Scheme",$border,0,"C");
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();

            $border=0;
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(30,6,"Outstanding",$border,0,"L");
            $this->pdf->Cell(20,6,"90-100",$border,0,"L");
            $this->pdf->Cell(10,6,"A",$border,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(30,6,"Very Good",$border,0,"L");
            $this->pdf->Cell(20,6,"80-89",$border,0,"L");
            $this->pdf->Cell(10,6,"B",$border,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(30,6,"Good",$border,0,"L");
            $this->pdf->Cell(20,6,"70-79",$border,0,"L");
            $this->pdf->Cell(10,6,"C",$border,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(30,6,"Satisfactory",$border,0,"L");
            $this->pdf->Cell(20,6,"60-69",$border,0,"L");
            $this->pdf->Cell(10,6,"D",$border,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();
    
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Cell(30,6,"Remediation",$border,0,"L");
            $this->pdf->Cell(20,6,"00-59",$border,0,"L");
            $this->pdf->Cell(10,6,"R",$border,0,"C");
            $this->pdf->Cell(10,6,"",$border,0,"C");
            $this->pdf->Ln();

            $this->pdf->SetXY($xCol2+100, $yCol2);
            $this->pdf->Cell(95.9, 6, 'END OF TERM ACHIEVEMENT', 1, 0, 'C' );
            $this->pdf->Ln();
    
            $this->pdf->SetXY($xCol2+100, $yCol2+6);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->MultiCell(32,5,"AREAS OF ASSESSMENT", 1, 'C');
            $this->pdf->SetXY($x+32,$y);
            $this->pdf->Cell(32,10,"MAXIMUM %", 1, 0, 'C');
            $this->pdf->Cell(31.9,10,"EARNED %", 1, 0, 'C');
            $this->pdf->Ln(); 

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(32,6,"Monthly Test", 1, 0, 'C');
            $this->pdf->Cell(32,6,"20", 1, 0, 'C');
            $this->pdf->Cell(31.9,6,$table1Record["monthly_test"], 1, 0, 'C');
            $this->pdf->Ln();

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(32,6,"Project", 1, 0, 'C');
            $this->pdf->Cell(32,6,$projectMax, 1, 0, 'C');
            $this->pdf->Cell(31.9,6,$table1Record["project"], 1, 0, 'C');
            $this->pdf->Ln();

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(32,6,"Participation", 1, 0, 'C');
            $this->pdf->Cell(32,6,$participationWeight, 1, 0, 'C');
            $this->pdf->Cell(31.9,6,$participationWeighted, 1, 0, 'C');
            $this->pdf->Ln();

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(32,6,'Term Test', 1, 0, 'C');
            $this->pdf->Cell(32,6,$termTestWeight, 1, 0, 'C');
            $this->pdf->Cell(31.9,6,$termTestWeighted, 1, 0, 'C');
            $this->pdf->Ln();

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(32,6,"Total", 1, 0, 'C');
            $this->pdf->Cell(32,6,"100", 1, 0, 'C');
            $endOfTermTotal = $termTestWeighted + $participationWeighted + $table1Record["project"] + $table1Record["monthly_test"];
            $this->pdf->Cell(31.9,6,$endOfTermTotal, 1, 0, 'C');
            $this->pdf->Ln();

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->Cell(64,6,"Overall Grade", 1, 0, 'C');
            $this->pdf->Cell(31.9,6,$this->grade($endOfTermTotal), 1, 0, 'C');
            $this->pdf->Ln(10);

            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(95.9,6,"C - Consistently\t\t\t\t S - Sometimes\t\t\t\t R - Rarely", 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(65.9,5,"SOCIAL BEHAVIOUR", 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(10,5,"C", 1, 0, 'C');
            $this->pdf->Cell(10,5,"S", 1, 0, 'C');
            $this->pdf->Cell(10,5,"R", 1, 0, 'C');
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tCommunicates positively with others", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($communication){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tWorks co-operatively with others", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($cooperation){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tShows respect for others and property", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($respect){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tBehaves responsibly", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($responsibility){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tDisplays a positive attitude", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($attitude){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tMakes appropriate judgement", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($judgement){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(65.9,5,"ATTITUDE TO WORK", 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(10,5,"C", 1, 0, 'C');
            $this->pdf->Cell(10,5,"S", 1, 0, 'C');
            $this->pdf->Cell(10,5,"R", 1, 0, 'C');
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tCompletes assignments", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($completeAssignment){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tParticipates in class", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($classParticipation){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tShows self-confidence in learning", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($selfConfidence){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(65.9,6,"\t\t\t\t\tPunctual attendance to class", 1, 0, 'L');
            $this->pdf->SetFont('ZapfDingbats', '', 13);
            $C = null;
            $S = null;
            $R = null;
            switch($punctual){
                case 'C':
                    $C = 3;
                    break;
                case 'S':
                    $S = 3;
                    break;
                case 'R':
                    $R = 3;
                    break;
            }
            $this->pdf->Cell(10,6,$C, 1, 0, 'C');
            $this->pdf->Cell(10,6,$S, 1, 0, 'C');
            $this->pdf->Cell(10,6,$R, 1, 0, 'C');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln(10);
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'U', 9);
            $this->pdf->Cell(30.9,6,"Teacher's Comment: ", 'LT', 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(65,6,"", 'TR', 0, 'C');
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'I', 9);
            $this->pdf->MultiCell(95.9,5,$form_teacher_comments, 'LR', 'L');
            $this->pdf->SetFont('Times', '', 10);
            // $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(11.9,7,"Teacher:", 'L', 0, 'L');
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(84,7,implode(" / ", $formTeachers), 'R', 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln(4);
    
            $this->pdf->SetX($xCol2+100);
            $this->pdf->Cell(95.9, 6, "", 'LR', 0);
            $this->pdf->Ln(4);
    
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'U', 9);
            $this->pdf->Cell(30.9,6,"Principal's Comment: ", 'L', 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Cell(65,6,"", 'R', 0, 'C');
            $this->pdf->Ln();
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'I', 9);
            $this->pdf->MultiCell(95.9,5,"", 'LR', 'L');
            $this->pdf->SetFont('Times', '', 10);
    
            $this->pdf->SetX($xCol2+100);
            $border = 1;
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', '', 9);
            $this->pdf->Cell(25.9,6,"Principal Signature:", 'L', 0, 'L');
            // $this->pdf->AddFont('BRUSHSCI', '', 'BRUSHSCI.php');
            // $this->pdf->SetFont('BRUSHSCI', '', 12);
            // $this->pdf->Cell(70,6,"Samuel Cassar", 'R', 0, 'L');
            // $this->pdf->AddFont('brushscriptcursive', '', 'brushscriptcursive.php');
            // $this->pdf->SetFont('brushscriptcursive', '', 12);
            $this->pdf->Cell(70,6,"", 'R', 0, 'L');
    
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
    
            // $this->pdf->SetX($xCol2+100);
            // $this->pdf->Cell(95.9, 3, "", 'LR', 0);
            // $this->pdf->Ln();
    
    
            $this->pdf->SetX($xCol2+100);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 9);
            $this->pdf->Cell(28.9,6,"School re-opens on: ", 'LB', 0, 'L');
            $this->pdf->Cell(67,6,date_format(date_create($newTermBeginning), 'd-M-y'), 'RB', 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();

            $this->pdf->SetY(-30);
            $this->pdf->SetDrawColor(78,78,78);
            $this->pdf->Ln(6);
            $this->pdf->MultiCell(0,5,'This report is not valid unless it bears the School\'s stamp and Principal\'s signature',1,'C');
            
            $x=15; $y=50; $size=20;
            
            // $this->pdf->Image($waterMarkLogo, $x, $y, 180);

            if($className && $classGroup) continue;
            ReportCardLog::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'year' => $year,
                    'term' => $term
                ],
                ['date_accessed' => date("Y-m-d h:i:s")]
            );

        }
        
        $this->pdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function highestMark($subject_id, $year, $term, $class_id)
    {
        $highest_mark = 0;
        $marks = Table2::join('table1', 'table2.student_id', 'table1.student_id')
        ->select('course_mark', 'exam_mark')
        ->where([
            ['table2.subject_id', $subject_id],
            ['table2.year', $year],
            ['table2.term', $term],
            ['table1.class_id', $class_id],
            ['table1.year', $year],
            ['table1.term', $term]
        ])
        ->get();
        foreach($marks as $mark){
            $exam_mark = $mark->exam_mark;
            if($exam_mark > $highest_mark) $highest_mark = $exam_mark;
        }       
        return ($highest_mark != 0) ? $highest_mark : null;
    }

    private function averageMark($record, $term)
    {
        $course_mark = $record->course_mark;
        $exam_mark = $record->exam_mark;
        if($term == 2) return '----';
        return ($course_mark === null || $exam_mark === null) ? 'Incomplete' : number_format(($course_mark+$exam_mark)/2,1);
    }

    private function highestAverage($year, $term, $class_id, $class_records)
    {
        $highestAverage = 0;
        foreach($class_records as $record){
            $totalMarks = 0;
            $subjects = 0;
            $student_id = $record->student_id;
            $class_marks = Table2::join('table1', 'table2.student_id', 'table1.student_id')
            ->select('course_mark', 'exam_mark')
            ->where([
                ['table2.year', $year],
                ['table2.term', $term],
                ['table2.student_id', $student_id],
                ['table1.year', $year],
                ['table1.term', $term],
                ['table1.class_id', $class_id]
            ])            
            ->get();

            foreach($class_marks as $marks){
                $totalMarks += $marks->course_mark*0.2 + $marks->exam_mark*0.8;
                $subjects++;
            }

            $average = ($subjects != 0) ? round($totalMarks/$subjects,1) : 0;

            if($average > $highestAverage) $highestAverage = $average;
        }
        
        return $highestAverage;
       
    }    

    private function grade($average){
        if($average >= 90) return 'A';
        elseif ($average >= 85) return 'B';
        elseif ($average >= 70) return 'C';
        elseif ($average >= 60) return 'D';
        return 'R';
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

    private function rank($class_id, $year, $term, $student_average)
    {
        $averages = []; $total_marks = 0; $subjects = 0; $average = 0;
        
        $class_records = Table1::where([
            ['year', $year],
            ['term', $term],
            ['class_id', $class_id]
        ])
        ->select('student_id')
        ->get();        
               
        foreach($class_records as $record){
            $total_marks = 0; 
            $mark_records = Table2::where([
                ['student_id', $record->student_id],
                ['term', $term],
                ['year', $year]
            ])
            ->select('exam_mark')
            ->get();
            $total_marks = $mark_records->sum('exam_mark');
            $subjects = count($mark_records);
            //if($subjects != 0) $average = round(($total_marks/$subjects),1);            
            if($subjects != 0) $average = $total_marks/$subjects;            
            array_push($averages, $average);   
        }
        
        rsort($averages);
        
        foreach($averages as $key => $average){
            if($average == $student_average) return $key+1; 
        }
    }

    public function getRank($class_id, $year, $term)
    {
        $averages = []; $total_marks = 0; $subjects = 0; $average = 0;
        
        $class_records = Table1::where([
            ['year', $year],
            ['term', $term],
            ['class_id', $class_id]
        ])
        ->select('student_id')
        ->get();        
               
        foreach($class_records as $record){
            $total_marks = 0; 
            $mark_records = Table2::where([
                ['student_id', $record->student_id],
                ['term', $term],
                ['year', $year]
            ])
            ->select('exam_mark')
            ->get();
            $total_marks = $mark_records->sum('exam_mark');
            $subjects = count($mark_records);
            //if($subjects != 0) $average = round(($total_marks/$subjects),1);            
            if($subjects != 0) $average = $total_marks;            
            array_push($averages, $average);   
        }
        
        rsort($averages);

        return $averages;
        
        // foreach($averages as $key => $average){
        //     if($average == $student_average) return $key+1; 
        // }
    }

    public function classAverage ($year, $term, $class_id, $form_level)
    {
        $total_averages = 0; $total_subjects = 0; $averageSum = 0; $class_average = 0;
        $average = 0; $studentCount = 0; $incomplete = false;

                  
        $table1_records = Table1::where([
            ['year', $year],
            ['term', $term],
            ['class_id', $class_id]
        ])->get();

        $studentCount = 0;

        foreach($table1_records as $table1_record){ 

            $table2_records = Table2::where([
                ['year', $year],
                ['term', $term],
                ['student_id', $table1_record->student_id]                    
            ])->get();

            foreach($table2_records as $table2_record){

                if($term != 2 && ($table2_record->exam_mark === null || $table2_record->course_mark === null) ) $incomplete = true;
                elseif($form_level != 5 && $table2_record->course_mark === null) $incomplete = true;
                elseif($form_level == 5 && $table2_record->exam_mark === null) $incomplete = true;

                if($term != 2 && !$incomplete){
                    $total_averages += ($table2_record->exam_mark + $table2_record->course_mark) / 2;
                }
                elseif($form_level != 5 && !$incomplete){
                    $total_averages += $table2_record->course_mark;
                }
                elseif(!$incomplete){
                    $total_averages += $table2_record->exam_mark;
                }

                $total_subjects++;
            }

            if($total_subjects != 0 && !$incomplete){
                $averageSum += $total_averages/$total_subjects;
                $studentCount++;
            } 

            //$averageSum += $average;

            $total_averages = 0; $total_subjects = 0; $incomplete = false; 

        }
        
        return ($studentCount != 0) ? number_format($averageSum/$studentCount,1)."%" : null;
        
    }

    public function result ($table2Records, $term)
    {
        $result = null; $passes = 0; $pass_comm_stud = false; $pass_carib_stud = false;
        //$subject_ids = "";
        foreach($table2Records as $record){
            $subject_id = $record->subject_id;

            if($term == 1 && $record->course_mark >= 44){
                //$subject_ids .= $record->subject_id.";";
                //$passes++;
                if($record->subject_id == 156) $pass_comm_stud = true;                
                elseif($record->subject_id == 157) $pass_carib_stud = true;
                else $passes++;
            }
            elseif($record->paper_1 && $record->paper_2 && $record->ia && !isset($weightings[3]))
            {
                if($subject_id == 156) $pass_comm_stud = true;
                elseif($subject_id == 157) $pass_carib_stud = true;
                else $passes++;
            }
            elseif($record->paper_1 && $record->paper_2 && $record->paper_3 && $record->ia && $weightings[3])
            {
                if($subject_id == 156) $pass_comm_stud = true;
                elseif($subject_id == 157) $pass_carib_stud = true;
                else $passes++;
            }
            // elseif($record->exam_mark >= 44){                
            //     if($record->subject_id == 156) $pass_comm_stud = true;
            //     elseif($record->subject_id == 157) $pass_carib_stud = true;
            //     else $passes++;
            // } 
        }

        if($pass_comm_stud && !$pass_carib_stud && $passes != 0) $result = $passes.' + Communication Studies';
        elseif($pass_comm_stud && !$pass_carib_stud && $passes == 0) $result = 'Communication Studies';
        elseif(!$pass_comm_stud && $pass_carib_stud && $passes != 0 ) $result = $passes.' + Caribbean Studies';        
        elseif(!$pass_comm_stud && $pass_carib_stud && $passes == 0) $result = 'Caribbean Studies';
        elseif(!$pass_comm_stud && !$pass_carib_stud && $passes !=0) $result = $passes;
        //elseif(!$pass_carib_stud && $passes !=0) $result = $passes;
        
        return $result;
        // if($pass_carib_stud) return 'Carib stud';
        // else return 'no carib stud';
    }

    public function sortTable2Records($table2Records)
    {
        $data = []; $subjectFoundIndex = -1; $recordFound = null;
        $subjectsCount = sizeof($table2Records); $recordsReturn = [];
        
        foreach($table2Records as $index => $record){            
            if($record->subject_id == 156 || $record->subject_id == 157){
                $subjectFoundIndex = $index;
                break;
            }            
        }
        
        if(sizeof($table2Records) > 0) $recordFound = $table2Records[0];
        
        foreach($table2Records as $index => $record){            
            if($index == $subjectFoundIndex){
                $data[0] = $record;
            }
            else{
                $data[$index] = $record;
            }           
        }
        if($subjectFoundIndex != -1 && $subjectFoundIndex != 0) $data[$subjectFoundIndex] = $recordFound;
        $data[$subjectFoundIndex] = $recordFound;

        for($i = 0; $i < $subjectsCount; $i++){
            $recordsReturn[$i] = $data[$i];
        }

        //$recordsReturn[0] = $subjectsCount;
        
        //return $data;
        return $recordsReturn;
    }
}
