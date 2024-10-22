<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\EthnicGroup;
use App\Models\EthnicGroups;
use App\Models\Religion;
use App\Models\Student;
use App\Models\House;
use Codedge\Fpdf\Fpdf\Fpdf;

class RegistrationFormController extends Controller
{
    private $fpdf;

    public function __construct()
    {
        
    }
    
    
    public function createPDF(Request $request)
    {
        $id = $request->input('student_id');
        $record = Student::whereId($id)->first();        
        $school = config('app.school_name');        
        $address = config('app.school_address_line_1');
        $contact = config('app.school_contact_line_1');
        //$entryDate = config('app.school_entry_date');
        //$declaration = config('app.school_declaration');
        $declaration = "I agree with and will abide by all the School's policies, ".
        "regulations and rules which are detailed in the School's Handbook and including ".
        "the Standards of Behaviour for Online learning. I will ensure my child ". 
        "conforms to the standards of work and conduct expected, both within ".
        "and outside the confines of the school while attending ".$school.
        ". Additionaly, I will make every effort to have my child uphold the Ministry's ".
        "School Code of conduct.";

        
        $religionArray = Religion::whereCode($record->religion_code)->get();
        $religion = (sizeof($religionArray) > 0) ? $religionArray[0]->religion : "";
             
        $ethnicGroupArray = EthnicGroup::whereId($record->ethnic_group_code)->get();
        $ethnicGroup = (sizeof($ethnicGroupArray) > 0) ? $ethnicGroupArray[0]->group_type : "";

        $imigrationPermit = ($record->immigration_permit == 0) ? "No" : "Yes";
        $dob = date_format(date_create($record->date_of_birth), 'd-M-Y');
        $entryDate = date_format(date_create($record->entry_date), 'd-M-Y');
        $birthCertificate = ($record->file_birth_certificate) ? 3 : "";
        $seaPlacementSlip = ($record->file_sea_slip) ? 3 : "";
        $immunizationCard = ($record->file_immunization_card) ? 3 : "";
        $pictureFile = $record->picture;
        $passportPhoto = $pictureFile ? 3 : null;        
        $photo = $pictureFile ?  public_path('/storage/'.$pictureFile) : null;
        $house = House::where('id', $record->house_code)->first();
        $house = $house ? $house->name : null;
        
        $r = config('app.school_color_red');
        $g = config('app.school_color_green');
        $b = config('app.school_color_blue');
        
        $border = 0;
        $cellBorder = 1;
        $logo = public_path('/imgs/logo.png');


        $this->fpdf = new Fpdf;
        $this->fpdf->AddPage("P", 'Legal');
        $this->fpdf->SetMargins(10, 8);
        $this->fpdf->SetDrawColor(220, 220, 220);
        
        $this->fpdf->Image($logo, 10, 6, 30);
        $this->fpdf->Rect(181, 6, 25, 30);
        $this->fpdf->SetFont('Times', '', '9');
        $x = $this->fpdf->GetX();
        $y = $this->fpdf->GetY();
        $this->fpdf->SetXY(181, 18);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->Cell(25, 6, 'Passport Photo', 0, 0, 'C');
        $this->fpdf->SetTextColor(0);
        $this->fpdf->SetXY($x, $y);
        if($photo)
        $this->fpdf->Image($photo, 181, 6, 25);
        $this->fpdf->SetFillColor(255, 255, 255);
        $this->fpdf->Rect(180, 38, 30, 2, 'F');
        
        $this->fpdf->SetFont('Times', 'B', '16');        
        $this->fpdf->MultiCell(0, 6, strtoupper($school), $border, 'C');
        
        $this->fpdf->SetFont('Times', 'I', 10);
        $this->fpdf->MultiCell(0, 5, $address, $border, 'C' );
        $this->fpdf->MultiCell(0, 5, $contact, $border, 'C' );
        //$this->fpdf->Line(10, 30, 206, 30);
        $this->fpdf->SetFont('Times', 'B', '14');
        $this->fpdf->Ln(6);
        $this->fpdf->MultiCell(0, 5, 'STUDENT REGISTRATION FORM', $border, 'C' );
        $this->fpdf->Ln();
        $x = $this->fpdf->GetX();
        $y = $this->fpdf->GetY() - 3;
        $this->fpdf->SetFillColor(240, 240, 240);
        $this->fpdf->Rect($x, $y, 196, 45, 'F');
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Student ID#', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Copy of Original Birth Certificate', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $birthCertificate, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 12);        
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Class', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $record->class_id, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Passport Photo', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $passportPhoto, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 12); 
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'Entry Date', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $entryDate, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'SEA Placement Slip', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $seaPlacementSlip, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 12); 
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(25, 6, 'House', 0, 0, 'L');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(30, 6, $house, $cellBorder, 0, 'C');
        $this->fpdf->Cell(55, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(60, 6, 'Copy of Immunization Card', 0, 0, 'R');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $immunizationCard, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 12); 
        $this->fpdf->Cell(5, 6, '', 0, 0, 'L');
        $this->fpdf->Ln(10);

        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'STUDENT INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(68, 6, $record->first_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(50, 6, $record->last_name, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, $record->middle_name, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(68, 6, 'First Name', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(50, 6, 'Last Name', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, 'Other Names', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(145, 6, $record->home_address, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, $record->address_line_2, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Address', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Town', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(45, 6, $record->regional_corporation, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(68, 6, $record->place_of_birth, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');        
        $this->fpdf->Cell(73, 6, $record->nationality, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(45, 6, 'Regional Corporation', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');                
        $this->fpdf->Cell(68, 6, 'Country of birth', 0, 0, 'C');                
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(73, 6, 'Nationality', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(8);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(64, 6, $this->formatNumber($record->phone_home), $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(85, 6, $record->email, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(37, 6, $record->blood_type, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(64, 6, 'Telphone', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(85, 6, 'Email', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(37, 6, 'Blood Type', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);          
        $this->fpdf->Ln(8);

        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(64, 6, $dob, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(64, 6, $record->birth_certificate_no, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, $record->sex, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(59, 6, 'Date of Birth', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(59, 6, 'Birth Certificate Pin', 0, 0, 'C');          
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(58, 6, 'Gender', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0); 
        $this->fpdf->Ln(8);

        
        
        // $this->fpdf->Ln(8);
        // $this->fpdf->SetFont('Times', '', '12');
        // $this->fpdf->Cell(64, 6, $imigrationPermit, $cellBorder, 0, 'C');
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(59, 6, $record->permit_issue_date, $cellBorder, 0, 'C');       
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(63, 6, $record->permit_expiry_date, $cellBorder, 0, 'C');       
        // $this->fpdf->Ln();
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(59, 6, 'Immigration Permit', 0, 0, 'C');
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(59, 6, 'Permit Issue Date', 0, 0, 'C');          
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(58, 6, 'Permit Expiry Date', 0, 0, 'C');
        // $this->fpdf->SetTextColor(0, 0, 0); 
        
        
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(140, 6, $record->previous_school, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(51, 6, $record->sea_no, $cellBorder, 0, 'C');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(140, 6, 'Previous School', 0, 0, 'C');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(46, 6, 'Sea Number', 0, 0, 'C'); 
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(8);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(108, 6, $religion, $cellBorder, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(83, 6, $ethnicGroup, $cellBorder, 0, 'C');       
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        //$this->fpdf->Cell(63, 6, $record->second_language, $cellBorder, 0, 'C');       
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(108, 6, 'Religion', 0, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(83, 6, 'Ethnic Group', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        //$this->fpdf->Cell(58, 6, 'Second Language', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'FAMILY INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        
        $this->fpdf->Ln(4);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, '', 0, 0, 'C');        
        $this->fpdf->Cell(53, 6, 'Father', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(53, 6, 'Mother', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(53, 6, 'Guardian', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Name', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->father_name)), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->mother_name)), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, ucwords(strtolower($record->guardian_name)), 1, 0, 'C');        
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Marital Status', 0, 0, 'L');       
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_marital_status, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_marital_status, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->guardian_marital_status, 1, 0, 'C'); 

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Home Phone', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->father_phone_home), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->mother_phone_home), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->home_phone_guardian), 1, 0, 'C');        

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Work Phone', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->father_business_phone), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->mother_business_phone), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->business_phone_guardian), 1, 0, 'C');        

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Mobile Phone', 0, 0, 'L');       
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->mobile_phone_father), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->mobile_phone_mother), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $this->formatNumber($record->mobile_phone_guardian), 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'ID Card #', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(54, 6, $record->id_card_father, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->id_card_mother, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->id_card_guardian, 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Occupation', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_occupation, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_occupation, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->guardian_occupation, 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Employer', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(54, 6, $record->father_business_place, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->mother_business_place, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(54, 6, $record->guardian_business_place, 1, 0, 'C');  

        $this->fpdf->Ln(12);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(130, 6, 'Father Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, 'Father Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(130, 6, $record->father_home_address, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, $record->email_father, 1, 0, 'L');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(130, 6, 'Mother Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, 'Mother Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();

        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(130, 6, $record->mother_home_address, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, $record->email_mother, 1, 0, 'L');
                     
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(130, 6, 'Guardian Address', 0, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, 'Guardian Email', 0, 0, 'L');        
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln();

        $this->fpdf->SetTextColor(0);
        $this->fpdf->Cell(130, 6, $record->home_address_guardian, 1, 0, 'L');
        $this->fpdf->Cell(6, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 6, $record->email_guardian, 1, 0, 'L');        

        $this->fpdf->AddPage("P", 'Legal');
        $this->fpdf->SetMargins(10, 8);

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');        
        $this->fpdf->Cell(62, 6, 'Number of Children in Family', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Number of Children at Home', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Place in Family', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);        
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $record->no_in_family, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->no_at_home, 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->place_in_family, 1, 0, 'C');
        
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(60, 6, 'Living Status', 0, 0, 'L');        
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');        
        $this->fpdf->Cell(126, 6, 'Emergency Contact / Address', 0, 0, 'L');        
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(0);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Cell(60, 6, $record->living_status, 1, 0, 'L');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(126, 6, $record->emergency_contact, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');        
        $this->fpdf->Cell(62, 6, 'Telephone Number', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Relation to Child', 0, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, 'Workplace / Telephone Number', 0, 0, 'C');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);        
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(62, 6, $this->formatNumber($record->emergency_home_phone), 1, 0, 'C');          
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        $this->fpdf->Cell(62, 6, $record->relation_to_child, 1, 0, 'C');
        $this->fpdf->Cell(5, 6, '', 0, 0, 'C');  
        $this->fpdf->Cell(62, 6, $record->relative_in_school, 1, 0, 'C');        
        // $this->fpdf->Cell(5, 6, '', 0, 0, 'C');
        // $this->fpdf->Cell(62, 6, $record->emergency_work_phone, 1, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'HEALTH HISTORY', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln(5);

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->Cell(0, 6, 'Immunization Records', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '10');        
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Hepatitis', 0, 0, 'L');
        $check = ($record->hepatitis == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Polio', 0, 0, 'L');
        $check = ($record->polio == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diptheria', 0, 0, 'L');
        $check = ($record->diphtheria == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Tetanus', 0, 0, 'L');        
        $check = ($record->tetanus == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Yellow Fever', 0, 0, 'L');
        $check = ($record->yellow_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Measles', 0, 0, 'L');
        $check = ($record->measles == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'TB', 0, 0, 'L');
        $check = ($record->tb == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Chicken Pox', 0, 0, 'L');
        $check = ($record->chicken_pox == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Typhoid', 0, 0, 'L');
        $check = ($record->typhoid == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(9, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(27, 6, 'Rheumatic Fever', 0, 0, 'L');
        $check = ($record->rheumatic_fever == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->Cell(0, 6, 'Health Conditions', 0, 0, 'L');
        $this->fpdf->SetFont('Times', '', '10');        
        $this->fpdf->SetTextColor(0, 0, 0);

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Poor Eyesight', 0, 0, 'L');
        $check = ($record->poor_eyesight == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');


        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Poor Hearing', 0, 0, 'L');
        $check = ($record->poor_hearing == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Diabetes', 0, 0, 'L');
        $check = ($record->diabetes == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(24, 6, 'Asthma', 0, 0, 'L');
        $check = ($record->asthma == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(25, 6, 'Epilepsy', 0, 0, 'L');
        $check = ($record->epilepsy == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', 'B', '10');
        // $this->fpdf->Cell(0, 6, 'Learning Challenges', 0, 0, 'L');
        // $this->fpdf->SetFont('Times', '', '10');        
        // $this->fpdf->SetTextColor(0, 0, 0);

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(25, 6, 'Dyslexia', 0, 0, 'L');
        // $check = ($record->dyslexia == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'Dyscalculia', 0, 0, 'L');
        // $check = ($record->dyscalculia == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'Dysgraphia', 0, 0, 'L');
        // $check = ($record->dysgraphia == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'Non Verbal', 0, 0, 'L');        
        // $check = ($record->non_verbal == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(25, 6, 'Apraxia', 0, 0, 'L');
        // $check = ($record->apraxia == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(25, 6, 'ADD', 0, 0, 'L');
        // $check = ($record->attention_deficit == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'ADHD', 0, 0, 'L');
        // $check = ($record->adhd == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'Autism', 0, 0, 'L');
        // $check = ($record->autism == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(11, 6, '', 0, 0, 'C');

        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(24, 6, 'Cerebral Palsy', 0, 0, 'L');
        // $check = ($record->cerebral_palsy == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $check, 1, 0, 'C');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(9, 6, '', 0, 0, 'C');        

        $this->fpdf->Ln(12);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Other Health Condition', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->medical_history, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Other Illness', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->other_illness, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Allergies', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->allergy, 1, 0, 'L');        

        $this->fpdf->Ln(10);
        $this->fpdf->SetFillColor($r, $g, $b);
        $this->fpdf->SetTextColor(255, 255, 255);
        $this->fpdf->SetFont('Times', 'B', '10');
        $this->fpdf->MultiCell(196, 6, 'OTHER INFORMATION', 0, 'C', true);
        $this->fpdf->SetFont('Times', '', '12');
        $this->fpdf->SetTextColor(0, 0, 0);        

        
        // $this->fpdf->Ln(5);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(95, 6, 'Date of Baptism', 0, 0, 'L');        
        // $this->fpdf->Cell(6, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(95, 6, 'Date of First Communion', 0, 0, 'L');          
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Ln();
        // $this->fpdf->SetFont('Times', '', '11');
        // $this->fpdf->SetTextColor(0);
        // $dateOfBaptism = $record->date_of_baptism ? date_format(date_create($record->date_of_baptism), 'd-M-Y') : null;
        // $this->fpdf->Cell(55, 6, $dateOfBaptism, 1, 0, 'L');
        // $this->fpdf->Cell(46, 6, '', 0, 0, 'L');
        // $dateOfFirstCommunion = $record->date_of_first_communion ? date_format(date_create($record->date_of_first_communion), 'd-M-Y') : null;
        // $this->fpdf->Cell(55, 6, $dateOfFirstCommunion, 1, 0, 'L');
        
        $this->fpdf->Ln(8);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Special Considerations', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->special_consideration, 1, 0, 'L');
        
        $this->fpdf->Ln(8);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(0, 6, 'Extra-Curricular Interests', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFont('Times', '', '11');
        $this->fpdf->Ln();
        $this->fpdf->Cell(0, 6, $record->achievements, 1, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Would your child require School Feeding Meals?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->school_feeding == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');        
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->school_feeding == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        // $this->fpdf->Ln(10);
        // $this->fpdf->SetTextColor(64, 64, 64);
        // $this->fpdf->SetFont('Times', '', '10');
        // $this->fpdf->Cell(80, 6, 'Does your child access Social Welfare Grant?', 0, 0, 'L');        
        // $this->fpdf->SetTextColor(0, 0, 0);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        // $checkYes = ($record->social_welfare == 1) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        // $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        // $checkNo = ($record->social_welfare == 0) ? "3" : "";
        // $this->fpdf->SetFont('ZapfDingbats', '', 13);
        // $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        // $this->fpdf->SetFont('Times', '', 10);
        // $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does your child access School Transport?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->transport == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->transport == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(80, 6, 'Does your child have internet access?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'Yes', 0, 0, 'L');
        $checkYes = ($record->internet_access == 1) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkYes, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(10, 6, 'No', 0, 0, 'L');
        $checkNo = ($record->internet_access == 0) ? "3" : "";
        $this->fpdf->SetFont('ZapfDingbats', '', 13);
        $this->fpdf->Cell(6, 6, $checkNo, 1, 0, 'L');
        $this->fpdf->SetFont('Times', '', 10);
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');

        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Cell(60, 6, 'What type of device does your child have access to?', 0, 0, 'L');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->Cell(30, 6, '', 0, 0, 'L');
        $this->fpdf->Cell(42, 6, $record->device_type, 1, 0, 'L');
        $this->fpdf->Cell(10, 6, '', 0, 0, 'L');        

        $this->fpdf->Ln(15);
        $this->fpdf->SetFont('Times', 'BU', '10');
        $this->fpdf->Cell(0, 6, 'Declaration', 0, 0, 'L');        
        $this->fpdf->SetFont('Times', '', '10');
        $this->fpdf->Ln(10);
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->MultiCell(0, 6, $declaration, 0, 'L');
        
        $this->fpdf->Ln();
        $this->fpdf->SetFillColor(240, 240, 240);
        $this->fpdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 10, '', 'B', 0, 'C', true);
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(40, 10, '', 'B', 0, 'C', true);
        
        $this->fpdf->Ln();
        $this->fpdf->SetTextColor(64, 64, 64);
        $this->fpdf->SetFont('Times', 'I', '10');
        $this->fpdf->Cell(60, 10, 'Student\'s Signature', 0, 0, 'C');        
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(60, 10, 'Parent\'s / Guardian\'s Signature', 0, 0, 'C');
        $this->fpdf->Cell(18, 10, '', 0, 0, 'C');
        $this->fpdf->Cell(40, 10, 'Date', 0, 0, 'C');        
        $this->fpdf->SetTextColor(0, 0, 0);
        $this->fpdf->SetFillColor(220, 220, 220);       


        $this->fpdf->Ln();
        


        $this->fpdf->Output();
        exit;
        // $student = Student::whereId($id)->get();
        // $studentFirstName = $student[0]->first_name;
        // return $studentFirstName;
    }

    public function record($id){
        return Student::whereId($id)->get();
    }

    private function formatNumber ($number) 
    {
        return "(868) ".substr($number, 0, 3)."-".substr($number, -4);
    }
}
