<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\AuditLoginStudent;
use Carbon\Carbon;

class ReportLoginFailedController extends Controller
{
    private $pdf;
    private $col1 = 15, $col2 = 95, $col3 = 25, $col4 = 41;

    public function __construct(\App\Models\Pdf $pdf)
    {
        $this->pdf = $pdf;
    }

    public function show (Request $request)
    {
        date_default_timezone_set('America/Caracas');
        $startDate = $request->startDate;
        $endDate = $request->endDate;
        $maxRows = 30; 

        $this->pdf->AliasNbPages();
        $this->pdf->SetMargins(20, 10);
        $this->pdf->SetAutoPageBreak(false);
        $this->pdf->AddPage('P', 'Letter');

        $this->header($startDate, $endDate);

        $data = $this->data($startDate, $endDate);
        $border = 0;
        foreach($data as $index => $record){
            if($index%$maxRows == 0 && $index!= 0){
                $this->footer();
                $this->pdf->AddPage('P', 'Letter');
                $this->header($startDate, $endDate);
            }
            $this->pdf->SetFont('Times', '', 11);
            $this->pdf->Cell($this->col1, 6, $record->student_id, $border, 0, 'L');
            $this->pdf->Cell($this->col2, 6, $record->last_name.', '.$record->first_name, $border, 0, 'L');
            $this->pdf->Cell($this->col3, 6, $record->class_id, $border, 0, '');
            $this->pdf->Cell($this->col4, 6,  date_create($record->created_at)->format('d-m-Y h:i:s a'), $border, 0, '');
            $this->pdf->Ln();
        }

        $this->pdf->Ln(10);
        $this->pdf->SetFont('Times', 'B', 11);
        $this->pdf->MultiCell(0, 6, 'Total :  '.sizeof($data));

        $this->footer();

        $this->pdf->Output('I', 'Student Failed Logins Logs.pdf');
        exit;
    }

    private function header ($startDate, $endDate)
    {
        $logo = public_path('/imgs/logo.png');        
        $school = config('app.school_name');        
        $address = config('app.school_address');
        $contact = config('app.school_contact');

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
        $this->pdf->MultiCell(0, 10, 'Failed Logins - Student', 'B', 'C');
       
        if($startDate && $endDate && $startDate == $endDate){
            $this->pdf->SetXY(166, $y);
            $this->pdf->SetFont('Times', 'IB', 12);
            $this->pdf->Cell(30, 10, date_create($startDate)->format('l d M, Y'), 0, 0, 'R');
            $this->pdf->Ln();
        }
        elseif ($startDate && $endDate) {
            $this->pdf->SetXY(166, $y);
            $this->pdf->SetFont('Times', 'IB', 12);
            $this->pdf->Cell(30, 10, date_create($startDate)->format('d M').' - '.date_create($endDate)->format('d M, Y'), 0, 0, 'R');
            $this->pdf->Ln();
        }

        $border = 'B';
        $this->pdf->SetFont('Times', 'B', 11);        

        $this->pdf->Cell($this->col1, 8, 'ID #', $border, 0, 'L');
        $this->pdf->Cell($this->col2, 8, 'Name', $border, 0, 'L');
        $this->pdf->Cell($this->col3, 8, 'Class', $border, 0, '');        
        $this->pdf->Cell($this->col4, 8, 'Date', $border, 0, 'L');

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

    private function data($startDate, $endDate) 
    {
        $from = date($startDate);
        $to = date($endDate);

        if($startDate && $endDate && $startDate == $endDate)
        {
            return AuditLoginStudent::join('students', 'students.id', 'audit_login_students.student_id')
            ->select(
                'student_id',
                'students.first_name',
                'students.last_name', 
                'students.class_id', 
                'audit_login_students.created_at' 
            )
            ->where('failed_login', 1)
            ->whereDate('audit_login_students.created_at', $startDate)
            ->orderBy('created_at', 'desc')
            ->get();
        }

        if($startDate && $endDate)
        {
            return AuditLoginStudent::join('students', 'students.id', 'audit_login_students.student_id')
            ->select(
                'student_id',
                'students.first_name',
                'students.last_name', 
                'students.class_id', 
                'audit_login_students.created_at' 
            )
            ->where('failed_login', 1)
            ->whereBetween(
                DB::raw('date(audit_login_students.created_at)'), 
                [
                    Carbon::createFromDate($startDate)->toDateString(), 
                    Carbon::createFromDate($endDate)->toDateString()
                ]
            )
            ->orderBy('audit_login_students.created_at', 'desc')
            ->get();
        }

        return AuditLoginStudent::join('students', 'students.id', 'audit_login_students.student_id')
        ->select(
            'student_id',
            'students.first_name',
            'students.last_name', 
            'students.class_id', 
            'audit_login_students.created_at' 
        )
        ->where('failed_login', 1)
        ->orderBy('audit_login_students.created_at', 'desc')
        ->get();
    }
}
