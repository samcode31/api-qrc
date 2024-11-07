<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\Subject;

class ReportStudentSubjectController extends Controller
{
    private $pdf;    

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show(Request $request)
    {
        $form = $request->form_level;
        $subjectId = $request->subject_id;
        date_default_timezone_set('America/Caracas');
        $logo = public_path('/imgs/logo.png');        
        $school = config('app.school_name');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');
        $subjectRecord = Subject::where('id', $subjectId)
        ->select('title')
        ->first();

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYear = null;
        if($academicTerm) {
            $academicYearId = $academicTerm->academic_year_id;
            $academicYear = substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4);
        }

        $formClasses = FormClass::where('form_level', $form)
        ->select('id')
        ->get();
        
        $subject = null;
        if($subjectRecord) $subject = $subjectRecord->title;

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(20, 10);
        $this->pdf->SetAutoPageBreak(false); 
                   
        $this->pdf->AddPage('P', 'Letter');       
        $this->pdf->Image($logo, 15, 10, 24);
        $this->pdf->SetFont('Times', 'B', '16');       
        $this->pdf->MultiCell(0, 8, strtoupper($school), 0, 'C' );
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 4, $contact, 0, 'C' );
        $this->pdf->Ln(8);

        $this->pdf->SetFont('Times', 'B', 14);
        $this->pdf->SetLineWidth(0.6);
        $y = $this->pdf->GetY();
        $this->pdf->MultiCell(0, 8, $subject, 'B', 'C');

        $this->pdf->SetXY(173, $y);
        $this->pdf->Cell(23, 8, $academicYear, 0, 0, 'L');
        $this->pdf->Ln();

        $border = 'B';
        $this->pdf->SetFont('Times', 'IB', 12);
        $this->pdf->Cell(20, 8, '', $border, 0, 'L');  
        $this->pdf->Cell(20, 8, 'Id', $border, 0, 'L');  
        $this->pdf->Cell(40, 8, '', $border, 0, 'L');  
        $this->pdf->Cell(96, 8, 'Name', $border, 0, 'L');
        $this->pdf->Ln();
        
        $border = 0;
        $recordCount = 0;
        $maxRecords = 30;
        $maxPageHeight = 242;
        $y = 0;

        foreach($formClasses as $formClass)
        {
            $data = $this->reportData($formClass->id, $subjectId);
            if(sizeof($data) > 0) {
                
                

                foreach($data as $index => $record)
                {
                    $recordCount++;

                    $this->pdf->SetFont('Times', 'B', 14);
                    if($index == 0 && $recordCount != 1) {
                        $this->pdf->SetLineWidth(0.2);
                        $this->pdf->Cell(0, 8, $formClass->id, 'T', 0, 'L');
                        $this->pdf->Ln();
                        $this->pdf->SetLineWidth(0.6);
                    }
                    elseif($index == 0 && $recordCount == 1){
                        $this->pdf->Cell(0, 8, $formClass->id, 0, 0, 'L');
                        $this->pdf->Ln();
                    }               
                    $this->pdf->SetFont('Times', '', 12);

                    if($y > $maxPageHeight){
                        $this->pdf->SetY(-15);
                        $this->pdf->SetFont('Times','I',10);
                        $this->pdf->SetDrawColor(220, 220, 220);
                        $this->pdf->SetLineWidth(1);
                        $this->pdf->Cell(88, 6, date("l, F d, Y"), 'T', 0, 'L');
                        $this->pdf->Cell(88, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 'T', 0, 'R');
                        $this->pdf->SetDrawColor(0);
                        $this->pdf->SetLineWidth(0.6);

                        $this->pdf->AddPage('P', 'Letter');
                        // $recordCount = 0;
                        // $maxRecords = 40;                        
                        $border = 'TB';
                        $this->pdf->SetFont('Times', 'IB', 12);
                        $this->pdf->Cell(20, 8, '', $border, 0, 'L');  
                        $this->pdf->Cell(20, 8, 'Id', $border, 0, 'L');  
                        $this->pdf->Cell(40, 8, '', $border, 0, 'L');  
                        $this->pdf->Cell(96, 8, 'Name', $border, 0, 'L');
                        $this->pdf->SetFont('Times', '', 12);
                        $this->pdf->Ln();
                        
                        $this->pdf->SetFont('Times', 'B', 14);
                        $this->pdf->Cell(0, 8, $formClass->id, 0, 0, 'L');
                        $this->pdf->SetFont('Times', '', 12);
                    }
                    
                    $border = 0;
                    $this->pdf->Cell(20, 6, '', $border, 0, 'L');  
                    $this->pdf->Cell(20, 6, $record->student_id, $border, 0, 'L');  
                    $this->pdf->Cell(40, 6, '', $border, 0, 'L');  
                    $this->pdf->Cell(76, 6, $record->last_name.', '.$record->first_name, $border, 0, 'L');
                    $this->pdf->SetFont('Times', 'B', 11);
                    if($index == sizeof($data) - 1)
                    $this->pdf->Cell(20, 6, 'Total: '.sizeof($data), $border, 0, '');
                    $this->pdf->SetFont('Times', '', 11); 
                    $this->pdf->Ln();
                    $y = $this->pdf->GetY();
                }
            }
            
        }

        $this->pdf->Ln(10);
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->MultiCell(0, 6, 'Total # Students  '.$recordCount);

        $this->pdf->SetY(-15);
        $this->pdf->SetFont('Times','I',10);
        $this->pdf->SetDrawColor(220, 220, 220);
        $this->pdf->SetLineWidth(1);
        $this->pdf->Cell(88, 6, date("l, F d, Y"), 'T', 0, 'L');
        $this->pdf->Cell(88, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 'T', 0, 'R');
        $this->pdf->SetDrawColor(0);
        $this->pdf->SetLineWidth(0.6);

        $this->pdf->Output('I', 'Student Subject Report.pdf');
        exit;
    }

    private function reportData($classId, $subjectId)
    {                
        $students = Student::join('student_subjects', 'student_subjects.student_id', 'students.id')
        ->select('student_subjects.student_id', 'students.first_name', 'students.last_name', 'students.class_id')
        ->where([
            ['students.class_id', $classId],
            ['student_subjects.subject_id', $subjectId]
        ])
        ->orderBy('students.class_id')
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->get();

        return $students;
    }
}
