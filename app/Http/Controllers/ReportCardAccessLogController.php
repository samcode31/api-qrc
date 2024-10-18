<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\ReportCardLog;
use App\Models\Student;
use App\Models\FormClass;
use Carbon\Carbon;

class ReportCardAccessLogController extends Controller
{
    private $pdf;
    private $col1 = 15; 
    private $col2 = 51; 
    private $col3 = 65;
    private $col4 = 30; 
    private $col5 = 35;    

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show(Request $request)
    {
        date_default_timezone_set('America/Caracas');  
        
        $year = $request->year;
        $term = $request->term;
        $className = $request->className;
        $classGroup = $request->classGroup;
        $classLevel = $request->classLevel;
        $startDate = $request->startDate;
        $endDate = $request->endDate;

        $formClassRecord = FormClass::where([
            ['class_name', $className],
            ['class_group', $classGroup]
        ])->first();


        $formClassId = $formClassRecord ? $formClassRecord->id : null;

             
        $maxRows = 30;
        $data = $this->reportData($year, $term, $className, $classGroup, $classLevel, $formClassId, $startDate, $endDate); 

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(10, 10);
        $this->pdf->SetAutoPageBreak(false);
        
        foreach($data as $key => $record)
        {
            if($key%$maxRows==0)
            {
                if($key!=0) $this->footer();
                $this->pdf->AddPage('P', 'Letter');
                $this->header($year, $term, $startDate, $endDate); 
            }
            
            $border = 0;
            
            $this->pdf->SetFont('Times', '', 11);

            if($year & $term){
                $this->pdf->Cell($this->col1, 6, $record->student_id, $border, 0, 'L');
                $this->pdf->Cell($this->col2+20, 6, $record->last_name.', '.$record->first_name, $border, 0, 'L');
                $this->pdf->Cell($this->col3+10, 6, $record->class_name, $border, 0, '');                
                $this->pdf->Cell($this->col5, 6, $record->date_accessed, $border, 0, 'L');
            }
            else{
                $this->pdf->Cell($this->col1, 6, $record->student_id, $border, 0, 'L');
                $this->pdf->Cell($this->col2, 6, $record->last_name.', '.$record->first_name, $border, 0, 'L');
                $this->pdf->Cell($this->col3, 6, $record->class_name, $border, 0, '');
                $this->pdf->Cell($this->col4, 6, 'Term '.$record->term.', '.$record->year, $border, 0, '');
                $this->pdf->Cell($this->col5, 6, $record->date_accessed, $border, 0, 'L');
            }
            
            $this->pdf->Ln();            
        }

        if (sizeof($data) == 0) {
            $this->pdf->AddPage('P', 'Letter');
            $this->header($year, $term, $startDate, $endDate); 
        }

        $formStudents = Student::join('form_classes', 'form_classes.id', 'students.form_class_id')
        ->select('students.id')
        ->where('form_classes.class_level', $classLevel)
        ->get();

        $classStudents = Student::select('id')
        ->where('form_class_id', $formClassId)
        ->get();

        // $this->pdf->SetY(-25);
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->Ln(10);
        if($classLevel && !$formClassId){
            $this->pdf->Cell(40, 6, 'Total Accessed:  '.sizeof($data), 0, 0, 'L');
            $this->pdf->Cell(40, 6, 'Total in Class:  '.sizeof($formStudents), 0, 0, 'L' );
        }
        elseif($classLevel && $formClassId ){
            $this->pdf->Cell(40, 6, 'Total Accessed:  '.sizeof($data), 0, 0, 'L');
            $this->pdf->Cell(40, 6, 'Total in Class:  '.sizeof($classStudents), 0, 0, 'L' );
        }
        else{
            $this->pdf->MultiCell(0, 6, 'Total Accessed:  '.sizeof($data));
        }        
                
        $this->footer();

        $this->pdf->Output('I', 'Report Card Access Logs.pdf');
        exit;
    }

    private function reportData($year, $term, $className, $classGroup, $classLevel, $formClassId, $startDate, $endDate)
    {
        $query = DB::Table('report_card_logs')
        ->join('students', 'report_card_logs.student_id', 'students.id')
        ->join('form_classes', 'students.form_class_id', 'form_classes.id')
        ->leftJoin('form_teachers', 'form_teachers.form_class_id', 'form_classes.id')
        ->leftJoin('employees', 'form_teachers.employee_id', 'employees.id')
        ->select(
            'report_card_logs.student_id',
            'students.first_name',
            'students.last_name',
            'report_card_logs.year',
            'report_card_logs.term',
            'report_card_logs.date_accessed',
            DB::raw('
                CASE
                    WHEN employees.id IS NOT NULL THEN CONCAT(form_classes.class_name, " - ",employees.first_name, " ", employees.last_name)
                    ELSE CONCAT(form_classes.class_name, " - ",form_classes.class_group)
                END AS class_name'  
            ),    
        );
        // ->orderBy('date_accessed', 'desc')
        // ->get();

        $query->when($startDate, function($query, $startDate){
            return $query->whereDate('report_card_logs.date_accessed', '>=', Carbon::parse($startDate))
            ->orderBy('date_accessed', 'desc');
        });

        $query->when($endDate, function($query, $endDate){
            return $query->whereDate('report_card_logs.date_accessed', '<=', Carbon::parse($endDate))
            ->orderBy('date_accessed', 'desc');
        });

        $query->when($className, function($query, $className){
            return $query->where('form_classes.class_name', $className)
            ->orderBy('date_accessed', 'desc');
        });

        $query->when($classGroup, function($query, $classGroup){
            return $query->where('form_classes.class_group', $classGroup)
            ->orderBy('date_accessed', 'desc');
        });

        $query->when($classLevel, function($query, $classLevel){
            return $query->where('form_classes.class_level', $classLevel)
            ->orderBy('date_accessed', 'desc');
        });


        return $query->get();
        
    }

    private function header($year, $term, $startDate, $endDate)
    {
        $logo = public_path('/imgs/logo.png');        
        $school = config('app.school_name');        
        $address = config('app.school_address_line_1');
        $contact = config('app.school_contact');

        $this->pdf->Image($logo, 15, 6, 24);
        $this->pdf->SetFont('Times', 'B', '14'); 
        $this->pdf->SetXY(38,15);      
        $this->pdf->MultiCell(0, 8, strtoupper($school), 0, 'C' );
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 4, $contact, 0, 'C' );
        $this->pdf->Ln(8);

        $this->pdf->SetFont('Times', 'IB', 14);
        $this->pdf->SetLineWidth(0.6);
        $y = $this->pdf->GetY();
        $this->pdf->MultiCell(0, 8, 'Report Card Access Logs', 'B', 'C');
        if($year && $term){
            $this->pdf->SetXY(166, $y);
            $this->pdf->SetFont('Times', 'IB', 12);
            $this->pdf->Cell(30, 8, 'Term '.$term.', '.$year, 0, 0, 'R');
            $this->pdf->Ln();
        }
        elseif(!$year && !$term)
        {
            if(!isset($startDate) && !isset($endDate) && $startDate && $endDate && $startDate == $endDate){
                $this->pdf->SetXY(166, $y);
                $this->pdf->SetFont('Times', 'IB', 12);
                $this->pdf->Cell(30, 8, date_create($startDate)->format('l d M, Y'), 0, 0, 'R');
                $this->pdf->Ln();
            }
            elseif ($startDate && $endDate) {
                $this->pdf->SetXY(166, $y);
                $this->pdf->SetFont('Times', 'IB', 12);
                $this->pdf->Cell(30, 8, date_create($startDate)->format('d M').' - '.date_create($endDate)->format('d M, Y'), 0, 0, 'R');
                $this->pdf->Ln();
            }
            
        }
        

        $border = 'B';
        $this->pdf->SetFont('Times', 'B', 11);        

        if($year && $term){
            $this->pdf->Cell($this->col1, 8, 'ID #', $border, 0, 'L');
            $this->pdf->Cell($this->col2+20, 8, 'Name', $border, 0, 'L');
            $this->pdf->Cell($this->col3+10, 8, 'Class', $border, 0, '');            
            $this->pdf->Cell($this->col5, 8, 'Date Accessed', $border, 0, 'L');
        }
        else{
            $this->pdf->Cell($this->col1, 8, 'ID #', $border, 0, 'L');
            $this->pdf->Cell($this->col2, 8, 'Name', $border, 0, 'L');
            $this->pdf->Cell($this->col3, 8, 'Class', $border, 0, '');
            $this->pdf->Cell($this->col4, 8, 'Report', $border, 0, '');
            $this->pdf->Cell($this->col5, 8, 'Date Accessed', $border, 0, 'L');
        }

        $this->pdf->Ln(10);
    }

    private function footer()
    {
        $this->pdf->SetY(-15);
        $this->pdf->SetFont('Times','I',10);
        $this->pdf->SetDrawColor(220, 220, 220);
        $this->pdf->SetLineWidth(1);
        $this->pdf->Cell(88, 6, date("l, F d, Y"), 'T', 0, 'L');
        $this->pdf->Cell(88, 6, 'Page '.$this->pdf->PageNo().'/{nb}', 'T', 0, 'R');
        $this->pdf->SetDrawColor(0);
        $this->pdf->SetLineWidth(0.6);
    }
}
