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
use App\Models\Student;
use App\Models\AcademicTerm;
use App\Models\FormDean;

class ReportCardController extends Controller
{
    private $pdf;   
    private $pageBreakHeight = 310; 

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

        $studentId = $request->input('studentId');
        $year = $request->input('year');
        $term = $request->input('term');
        $classId = $request->input('classId');
        $studentAccess = $request->input('studentAccess');

        $academicYearId = $year.($year + 1);

        $academicTermRecord = AcademicTerm::where([
            ['academic_year_id', $academicYearId],
            ['term', $term]
        ])
        ->first();

        $termStart = null;
        $termEnd = null;
        $reportPeriod = null;

        if($academicTermRecord)
        {
            $termStart = date('M-Y',strtotime($academicTermRecord->date_start));
            $termEnd = date('M-Y',strtotime($academicTermRecord->date_end));
            $reportPeriod = $termStart.' - '.$termEnd;
        }

        $table1Records = Table1::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])->get();

        if($classId)
        {
            $table1Records = Table1::where([
                ['year', $year],
                ['term', $term],
                ['class_id', $classId]
            ])->get();
                
        } else {
            $classId = $table1Records[0]->class_id;
        }

        $classRecords = Table1::where([
            ['year', $year],
            ['term', $term],
            ['class_id', $classId]
        ])
        ->select('student_id')
        ->get();

        $noInClass = sizeof($classRecords);

        $form_class = FormClass::where('id', $classId)
        ->select('form_level')
        ->first();
        $form_level = $form_class ? $form_class->form_level : null;

        $classAverages = $this->classAverages($classRecords, $year, $term, $form_level);

        $median = $this->median($classAverages);

        foreach($table1Records as $table1Record) {

            $studentId = $table1Record->student_id;
            $year = $table1Record->year;
            $term = $table1Record->term;       
            $formClass = $table1Record->class_id;
            $newTermBeginning = $table1Record->new_term_beginning;
            $possibleAttendance = $table1Record->possible_attendance;
            $timesAbsent = $table1Record->times_absent;
            $timesLate = $table1Record->times_late;
            $formTeacherComments = $table1Record->comments;
            $formDeanComments = $table1Record->dcomments;
            $auth = $table1Record->auth;
            $resp = $table1Record->resp;
            $coop = $table1Record->coop;
            $coCurricular = $table1Record->cocurricular;
            $mLate = $table1Record->mlate;
            $mAbs = $table1Record->mabs;
            $mGrade = $table1Record->mgrade;
            $mApp = $table1Record->mapp;
            $mCon = $table1Record->mcon;
            $mComments = $table1Record->mcomments;

            $totalMarks = 0;
            $total = 0;
            $totalSubjects = 0;
            $average = '';        
            $yearEnd = intval($year)+1;
            $academic_year = $year.' -- '.$yearEnd;
            $student = Student::where('id', $studentId)->first();
            $dob = $student ? $student->date_of_birth : null;
            $pass_mark = 50;
            $academic_year_id = $year.$yearEnd;
            $reportAcademicYear = $year.' / '.$yearEnd;
            $formTeachers = [];
            $formDeans = [];
            

            $formTeacherRecords = FormTeacher::join('employees', 'employees.id', 'form_teachers.employee_id')
            ->select('employees.first_name', 'employees.last_name')
            ->where([
                ['form_class_id', $formClass],
                ['academic_year_id', $academic_year_id]
            ])->get();
            
            foreach($formTeacherRecords as $formTeacher){
                array_push($formTeachers, $formTeacher->first_name[0].'. '.$formTeacher->last_name );
            }

            $formDeanRecords = FormDean::join('employees', 'employees.id', 'form_deans.employee_id')
            ->select('employees.first_name', 'employees.last_name')
            ->where([
                ['form_class_id', $formClass],
                ['academic_year_id', $academic_year_id]
            ])
            ->get();

            foreach($formDeanRecords as $formDean){
                array_push($formDeans, $formDean->first_name[0].'. '.$formDean->last_name );
            }

            $average = $this->averageMark($year, $term, $studentId, $form_level);
            
            $dob = $dob ? date_format(date_create($table1Record->student->date_of_birth), 'd-M-y') : "";        
            
            $name = $table1Record->student->last_name.', '.$table1Record->student->first_name;  
            
            
            $logo = public_path('/imgs/logo.jpg');
            $waterMarkLogo = public_path('/imgs/logo-report.png');
            $school = config('app.school_name');        
            $address = config('app.school_address_line_1');
            $contact = config('app.school_contact_line_1');
            $red = config('app.school_color_red');
            $green = config('app.school_color_green');
            $blue = config('app.school_color_blue');        
            $border = 0;
    
            $this->pdf->SetMargins(10, 8);
            $this->pdf->SetAutoPageBreak(false);              
            $this->pdf->AddPage('P', 'Legal');
            $this->pdf->setWaterMark($waterMarkLogo, 40);
            
            //$this->waterMark();
            //$this->pdf->SetDisplayMode('fullpage', 'single');
            $this->pdf->Image($logo, 10, 8, 30);
            $this->pdf->SetFont('Times', 'B', '16');       
            $this->pdf->MultiCell(0, 8, strtoupper($school), 0, 'C' );
            $this->pdf->SetFont('Times', 'I', 10);
            $this->pdf->MultiCell(0, 5, $address, 0, 'C' );
            $this->pdf->MultiCell(0, 5, $contact, 0, 'C' );
            $this->pdf->Ln(2);
            
            $this->pdf->SetFont('Times', 'UB', 12);
            $this->pdf->MultiCell(0, 6, 'END OF TERM REPORT - '.$reportAcademicYear, 0, 'C' );
            $this->pdf->SetFont('Times', '', 12);
            $this->pdf->Ln(2);
    
            //$this->pdf->SetDash(0.3, 1);
            $this->pdf->SetDrawColor(0);
            
            $border=0;
            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Student', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $name, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'Assessment', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, 'Term '.$term, $border, 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Date of Birth', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $dob, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'Student Number', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, $studentId, $border, 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Reporting Period', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $reportPeriod, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'Rank', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, $this->rank($classAverages, $average), $border, 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Class', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $formClass, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'No. in Class', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, $noInClass, $border, 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Average', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $average, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'Median', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, $median, $border, 0, 'L');
            $this->pdf->Ln();

            $this->pdf->SetFont('Times', 'B', 11);
            $this->pdf->Cell(49, 6, 'Number of Sessions Late', $border, 0, 'L');
            $this->pdf->Cell(69, 6, $timesLate."/".$possibleAttendance, $border, 0, 'L');
            $this->pdf->Cell(49, 6, 'Number of Sessions Absent', $border, 0, 'L');
            $this->pdf->Cell(28.9, 6, $timesAbsent."/".$possibleAttendance, $border, 0, 'L');
            $this->pdf->Ln();
           
            $this->pdf->SetDrawColor(190,190,190);
            //$this->pdf->Row(array("SUBJECT", "COURSE\n(20)", "EXAM\n(80)", "TOTAL\n(100)", "Highest Mark", "REMARKS"));
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 12);
            $this->pdf->MultiCell(40, 15, "SUBJECT", 1, 'C');
            $this->pdf->SetXY($x+40,$y);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $form_level = intval($form_level);
    
            if(
                $term == 1 && 
                (
                    $form_level == 6 || 
                    $form_level == 7 || 
                    $form_level == 5
                )
            ){ 
                $this->pdf->SetFont('Times', 'B', 8);
                $this->pdf->MultiCell(15, 3, "\n\nCOURSE\n(100%)\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                $this->pdf->MultiCell(15, 3, "\n\nEXAM\n----\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
            }
    
            elseif($term == 2 && (
                $form_level == 1 ||
                $form_level == 2 ||
                $form_level == 3 ||
                $form_level == 4
            )){
                $this->pdf->SetFont('Times', 'B', 8);
                $this->pdf->MultiCell(15, 3, "\n\nCOURSE\n(100%)\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                $this->pdf->MultiCell(15, 3, "\n\nEXAM\n----\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
            }
    
            elseif($term == 2 && $form_level > 4)
            {
                $this->pdf->SetFont('Times', 'B', 8);
                $this->pdf->MultiCell(15, 3, "\n\nCOURSE\----\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                $this->pdf->MultiCell(15, 3, "\n\nEXAM\n(100%)\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
            }
    
            else{
                $this->pdf->SetFont('Times', 'B', 8);
                $this->pdf->MultiCell(15, 3, "\n\nCOURSE\n(30%)\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
                $x=$this->pdf->GetX();
                $y=$this->pdf->GetY();
                $this->pdf->MultiCell(15, 3, "\n\nEXAM\n(70%)\n\t", 1, 'C');
                $this->pdf->SetXY($x+15,$y);
            }
        
            
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->MultiCell(15, 3, "\n\nTOTAL\n(100%)\n\t", 1, 'C');

            $this->pdf->SetXY($x+15,$y);
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 7);
            $this->pdf->MultiCell(12, 3, "\n\nLATE\n\t\n\t", 1, 'C');
            
            $this->pdf->SetXY($x+12,$y); 
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 7);
            $this->pdf->MultiCell(12, 3, "\n\nABS\n\t\n\t", 1, 'C'); 

            $this->pdf->SetXY($x+12,$y); 
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 7);
            $this->pdf->MultiCell(12, 3, "\n\nAPP\n\t\n\t", 1, 'C'); 
            
            $this->pdf->SetXY($x+12,$y); 
            $x=$this->pdf->GetX();
            $y=$this->pdf->GetY();
            $this->pdf->SetFont('Times', 'B', 7);
            $this->pdf->MultiCell(12, 3, "\n\nCON\n\t\n\t", 1, 'C'); 
                
                
            $this->pdf->SetXY($x+12,$y); 
            $this->pdf->SetFont('Times', 'B', 8);
            $this->pdf->MultiCell(62.9, 15, "SUBJECT TEACHER'S REMARKS", 1, 'C');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->SetWidths(array(40, 15, 15, 15, 12, 12, 12, 12, 62.9));
            $this->pdf->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'L'));
        
            $table2Records = Table2::join(
                'subjects', 
                'table2.subject_id', 
                'subjects.id'
            )
            ->leftJoin(
                'employees',
                'employees.id',
                'table2.employee_id'
            )
            ->where([
                ['student_id', $studentId],
                ['year', $year],
                ['term', $term]
            ])
            ->select(
                'table2.*', 
                'subjects.title',
                'employees.first_name',
                'employees.last_name'
            )
            ->orderBy('subjects.title')
            ->get();
            
            foreach($table2Records as $record)
            {
                $average_mark = 0;  
                $exam_mark = is_null($record->exam_mark) ?  'Abs' : number_format($record->exam_mark*0.75,1);
                $course_mark = is_null($record->course_mark) ?  'Abs' : number_format($record->course_mark*0.25,1);
                $comment = $record->comment;
                $app = $record->app;
                $con = $record->con;
                $late = $record->late;
                $absent = $record->absent;
                $teacher = $record->first_name && $record->last_name ? $record->first_name[0].'. '.$record->last_name : null;
    
                if(
                    $term == 1 && 
                    (
                        $form_level == 6 || 
                        $form_level == 7 || 
                        $form_level == 5
                    )
                ){ 
                    $exam_mark = '----';
                    $course_mark = is_null($record->course_mark) ? 'Abs' : number_format($record->course_mark,1);
                    $average_mark = is_numeric($course_mark) ? $course_mark : '----';
                }
    
                elseif($term == 2 && (
                    $form_level == 1 ||
                    $form_level == 2 ||
                    $form_level == 3 ||
                    $form_level == 4
                )){
                    $exam_mark = '----';
                    $course_mark = is_null($record->course_mark) ? 'Abs' : number_format($record->course_mark,1);
                    $average_mark = is_numeric($course_mark) ? $course_mark : '----';
                }
    
                elseif($term == 2 && $form_level > 4)
                {
                    $exam_mark = is_null($record->exam_mark) ? 'Abs' : number_format($record->exam_mark,1);
                    $course_mark = '----';
                    $average_mark = is_numeric($exam_mark) ? $exam_mark : '----';
                }
    
                else
                {
                    $exam_mark = is_null($record->exam_mark) ? 'Abs' : number_format($record->exam_mark*0.7,1);
                    $course_mark = is_null($record->course_mark) ? 'Abs' : number_format($record->course_mark*0.3,1);

                    $average_mark += is_numeric($course_mark) ? $course_mark : 0;
                    $average_mark += is_numeric($exam_mark) ? $exam_mark : 0;
                }
                        
                $subject = $record->title;
                $subject_id = $record->subject_id;
                $this->pdf->SetFont('Times', '', 10);
                $this->pdf->ReportCardRow(array(
                    $subject, 
                    $course_mark, 
                    $exam_mark, 
                    $average_mark, 
                    $late,
                    $absent,
                    $app,
                    $con,
                    rtrim($comment)."\n\t",
                    $teacher
                ));                           
                
            }

            $this->pdf->SetWidths(array(40, 45, 12, 12, 12, 12, 62.9));
            $this->pdf->SetAligns(array('L', 'C', 'C', 'C', 'C', 'C', 'L'));

            
            if($form_level < 4){
                $this->pdf->ReportCardRow(array(
                    'Music', 
                    "Grade \t\t".$mGrade,
                    $mLate, 
                    $mAbs, 
                    $mApp,
                    $mCon,
                    $mComments,
                ));   
            }
            
            $this->pdf->Ln(2);
            $y=$this->pdf->GetY();
            //$this->pdf->Cell(0, 6, $this->pdf->CustomPageBreakTrigger()."  y".$y, 1, 0, 'C');

            $border=1;
            $this->pdf->Cell(65.3, 6, "Attitude to Authority : ".$auth, $border, 0, 'L');
            $this->pdf->Cell(65.3, 6, "Responsibility : ".$resp, $border, 0, 'L');
            $this->pdf->Cell(65.3, 6, "Co-operation : ".$coop, $border, 0, 'L');
            $this->pdf->Ln(8);

            $this->pdf->SetWidths(array(40, 155.9));
            $this->pdf->SetAligns(array('L', 'L'));

            $this->pdf->Row(array(
                'Co-Curricular Activities: ',
                $coCurricular
            )); 
            
            $this->pdf->Row(array(
                "Form Teacher's Remarks:",
                rtrim($formTeacherComments)
            ), false, 'LR');
            
            
            $this->pdf->Cell(40, 6, "", 'LBR', 0, 'L');
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(0, 6, implode(" & ", $formTeachers), 'LBR', 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln();
            
            $this->pdf->Row(array(
                "Form Dean's Remarks:",
                $formDeanComments
            ), false, "LR");  
          
            $this->pdf->Cell(40, 6, "", "LBR", 0, 'L');
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(0, 6, implode(" & ", $formDeans), "LBR", 0, 'L');
            $this->pdf->SetFont('Times', '', 10);
            $this->pdf->Ln(8);

            
    
            $border=0;
            $this->pdf->SetDash(0.5,1);
            $this->pdf->SetFont('Times', 'B', 10);
            $this->pdf->Cell(40, 6, 'School Re-opens on:', $border, 0, 'L');
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell(0, 6, date_format(date_create($newTermBeginning), 'l, d F y'), 0, 0, 'L');
            $this->pdf->SetDash();
            $this->pdf->Ln(8);
    
            $this->pdf->SetFont('Times', '', 11);

            $y=$this->pdf->GetY();
            if($y > $this->pageBreakHeight)
            {
                $this->waterMark();
                $this->pdf->AddPage('P', 'Legal');
            }
            
            $this->pdf->SetY(-40);
            $this->pdf->SetDrawColor(78,78,78);
            $this->pdf->Cell(0,6,"A: 75-100, \t B: 65-74.9, \tC: 55-64.9, \tD:50-54.9, \tE: 0-49.9", 'LTR', 0, 'C');
            $this->pdf->Ln();

            $this->pdf->Cell(0,6,"LATE: Late \t\t\tABS - Absent \t\t\tAPP - Application \t\t\tCON - Conduct ", 'LR', 0, 'C');
            $this->pdf->Ln();

            $this->pdf->Cell(0, 6, "Rating Scale: \t\tA - Excellent \t\tB - Good \t\tC - Satisfactory \t\tD - Needs Improvement \t\tE- Urgent Intervention Needed", 'LBR', 0, 'C');
            $this->pdf->Ln(10);

            $this->pdf->SetFont('Times', 'I', 11);
            $this->pdf->MultiCell(0,6,'This report is not valid unless it bears the School\'s stamp and Principal\'s signature',0,'C');
        
            
           $this->waterMark();
    
            $this->pdf->SetFillColor(255,255,255);
            //$this->pdf->Rect(0,0, 215.9, 10, 'F');
            //$this->pdf->Rect(0,269.4, 215.9, 10, 'F');            
           
            
            if($studentAccess)
            {
                ReportCardLog::updateOrCreate(
                    [
                        'student_id' => $studentId,
                        'year' => $year,
                        'term' => $term
                    ],
                    ['date_accessed' => date("Y-m-d h:i:s")]
                );
            }
        }

        
        $this->pdf->Output('I', 'ReportCard.pdf');
        exit;
    }

    private function waterMark()
    {
        $waterMarkLogo = public_path('/imgs/logo-report.png');
        $size = 40; // Size of the watermark image
        $xSpacing = $size + 10; // Horizontal spacing between images
        $ySpacing = $size + 10; // Vertical spacing between images
        $pageWidth = $this->pdf->GetPageWidth(); // Get the page width
        $pageHeight = $this->pdf->GetPageHeight(); // Get the page height

        for ($y = -10; $y < $pageHeight; $y += $ySpacing) {
            for ($x = 10; $x < $pageWidth; $x += $xSpacing) {
                $this->pdf->Image($waterMarkLogo, $x, $y, $size);
            }
        }
    }


    public function data ($studentId, $year, $term, $classId)
    {
        $data = array();

        $table1Records = Table1::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])->get();

        if($classId)
        {
            $table1Records = Table1::where([
                ['year', $year],
                ['term', $term],
                ['class_id', $classId]
                ])->get();
                
        } else {
            $classId = $table1Records[0]->class_id;
        }


        $classRecords = Table1::where([
            ['year', $year],
            ['term', $term],
            ['class_id', $classId]
        ])
        ->select('student_id')
        ->get();
        
        $data['table1Records'] = $table1Records;
        $data['classRecords'] = $classRecords;
        return $data;
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

    private function averageMark($year, $term, $studentId, $formLevel)
    {
        $table2Records = Table2::where([
            ['year', $year],
            ['term', $term],
            ['student_id', $studentId]
        ])
        ->get();
        $totalMarks = 0;
        $subjects = 0;
        $average = null;
        
        if(
            $term == 1 && 
            (
                $formLevel == 6 || 
                $formLevel == 7 || 
                $formLevel == 5
            )
        ){ 
            foreach($table2Records as $record){
                $subjects++;
                $totalMarks += $record->course_mark;
            }
        }

        elseif($term == 2 && (
            $formLevel == 1 ||
            $formLevel == 2 ||
            $formLevel == 3 ||
            $formLevel == 4
        )){
            foreach($table2Records as $record){
                $subjects++;
                $totalMarks += $record->course_mark;
            }
            
        }

        elseif($term == 2 && $formLevel > 4){
            foreach($table2Records as $record){
                $subjects++;
                $totalMarks += $record->exam_mark;
            }
        }

        else{
            foreach($table2Records as $record){
                $subjects++;
                $totalMarks += $record->course_mark*0.3 + $record->exam_mark*0.7;
            }
        }

        $average = $subjects != 0 ? number_format($totalMarks/$subjects, 1) : null;

        return $average;
        // return $subjects;
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

    private function grade($average)
    {
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

    private function classAverages($table1Records, $year, $term, $formLevel)
    {
        $data = array();
        foreach($table1Records as $record){
            $studentId = $record->student_id;
            $averageMark = $this->averageMark($year, $term, $studentId, $formLevel);
            if(!$averageMark) continue;
            $data [] = $averageMark;
        }
        rsort($data);
        return $data;
    }

    private function rank($classAverages, $average)
    {
       foreach($classAverages as $key => $value){
           if($value == $average) return $key+1;
       }
       return null;
    }

    private function median($classAverages)
    {
        $count = count($classAverages);

        if($count == 0) return null;

        if($count % 2 == 1)
        {
            $middleIndex = floor($count / 2);
            return $classAverages[$middleIndex];
        }

        $middleIndex1 = $count / 2 -1;
        $middleIndex2 = $middleIndex1 + 1;
        return ($classAverages[$middleIndex1] + $classAverages[$middleIndex2]) / 2;
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
