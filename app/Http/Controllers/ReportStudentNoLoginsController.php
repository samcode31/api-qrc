<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\UserStudent;
use Illuminate\Support\Facades\DB;

class ReportStudentNoLoginsController extends Controller
{
    private $pdf;
    private $col1 = 20; 
    private $col2 = 20; 
    private $col3 = 40; 
    private $col4 = 76; 
    private $col5 = 20;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show () 
    {
        date_default_timezone_set('America/Caracas');
        $maxRows = 30; $maxPageHeight = 242;
        
        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(20, 10);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage('P', 'Letter');

        $this->header();

        $formClasses = FormClass::whereNotNull('form_level')
        ->select('id')
        ->get();
        
        $recordCount = 0;
        $y = 0;

        // return $this->data('3L');

        foreach($formClasses as $formClass)
        {
            $border=0;
            $data = $this->data($formClass->id);
            //if($formClass->id = '3L') return $data;            

            foreach($data as $index => $record){
                $recordCount++;

                if( $y > $maxPageHeight ){                    
                    $this->footer();
                    $this->pdf->AddPage('P', 'Letter');
                    $this->header();
                    $this->pdf->SetFont('Times', 'B', 14);
                    $this->pdf->Cell(0, 8, $formClass->id, 0, 0, 'L');
                    $this->pdf->SetFont('Times', '', 11);                   
                    $this->pdf->Ln();
                }

                $this->pdf->SetFont('Times', 'B', 14);
                if($index == 0 && $recordCount != 1 && $y+14 < $maxPageHeight){
                   
                    $this->pdf->SetLineWidth(0.2);
                    $this->pdf->Cell(0, 8, $formClass->id, 'T', 0, 'L');                   
                    $this->pdf->Ln();
                    $this->pdf->SetLineWidth(0.6);
                }
                elseif( $index == 0 && $recordCount == 1){
                    $this->pdf->Cell(0, 8, $formClass->id, 0, 0, 'L');                   
                    $this->pdf->Ln();
                }
                $this->pdf->SetFont('Times', '', 11);                
                
                $this->pdf->SetFont('Times', '', 11);
                $this->pdf->Cell($this->col1, 6, '', $border, 0, 'L'); 
                $this->pdf->Cell($this->col2, 6, $record->student_id, $border, 0, 'L');
                $this->pdf->Cell($this->col3, 6, '' , $border, 0, 'L');
                $this->pdf->Cell($this->col4, 6, $record->last_name.', '.$record->first_name, $border, 0, '');
                $this->pdf->SetFont('Times', 'B', 11);
                if($index == sizeof($data) - 1)
                $this->pdf->Cell($this->col5, 6, 'Total: '.sizeof($data), $border, 0, '');
                $this->pdf->SetFont('Times', '', 11);           
                $this->pdf->Ln();
                $y = $this->pdf->GetY();
            }
            

            
        }

        $this->pdf->Ln(10);
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->MultiCell(0, 6, 'Total :  '.$recordCount);

        $this->footer();

        $this->pdf->Output('I', 'Students Never Logged In.pdf');
        exit;
    }

    private function header ()
    {
        $logo = public_path('/imgs/logo.png');        
        $school = config('app.school_name');        
        $address = config('app.school_address_line_1');
        $contact = config('app.school_contact_line_1');

        $this->pdf->Image($logo, 15, 10, 24);
        $this->pdf->SetFont('Times', 'B', '16');       
        $this->pdf->MultiCell(0, 8, strtoupper($school), 0, 'C' );
        $this->pdf->SetFont('Times', 'I', 10);
        $this->pdf->MultiCell(0, 6, $address, 0, 'C' );
        $this->pdf->MultiCell(0, 4, $contact, 0, 'C' );
        $this->pdf->Ln(8);

        $this->pdf->SetFont('Times', 'IB', 14);
        $this->pdf->SetLineWidth(0.6);
        $y = $this->pdf->GetY();
        $this->pdf->MultiCell(0, 10, 'Never Logged In - Students', 'B', 'C');

        $border = 'B';
        $this->pdf->SetFont('Times', 'B', 11);        

        $this->pdf->Cell($this->col1, 8, '', $border, 0, 'L');
        $this->pdf->Cell($this->col2, 8, 'ID #', $border, 0, 'L');
        $this->pdf->Cell($this->col3, 8, '', $border, 0, '');        
        $this->pdf->Cell($this->col4 + $this->col5, 8, 'Name', $border, 0, '');        
        

        $this->pdf->Ln(10);
    }

    private function footer ()
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

    public function data ($classId) 
    {
        return UserStudent::join(
            'students', 
            'students.id', 
            'user_students.student_id'
        )
        ->select(
            'user_students.student_id',
            'students.first_name',
            'students.last_name',
            'students.class_id',
            'user_students.created_at',
            'user_students.updated_at'
        )
        ->whereRaw('user_students.created_at = user_students.updated_at')
        ->where('students.class_id', $classId)
        ->orderBy('students.last_name')
        ->orderBy('students.first_name')
        ->get();
    }
}
