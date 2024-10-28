<?php

namespace App\Http\Controllers;

use App\Http\Resources\Student as ResourcesStudent;
use App\Models\House;
use App\Models\Student;
use App\Models\UserStudent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\URL;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Throwable;

class StudentController extends Controller
{
    public function index ($id)
    {               
        return Student::whereId($id)->get();
    }

    public function store (Request $request)
    {        
        $id = $request->input('id');
        
        $studentRecord = Student::updateOrCreate(
            ['id' => $id],
            $request->except('name', 'form_level')
        );        
        
        return $studentRecord;
        
    }

    public function show ()
    {
        $data = array();
        $students = Student::join(
            'form_classes', 
            'form_classes.id', 
            'students.class_id')
        ->select(
            'students.id', 
            'first_name', 
            'last_name',
            'form_level', 
            'class_id', 
            'birth_certificate_no', 
            'date_of_birth',
            'file_birth_certificate',
            'file_sea_slip',
            'file_immunization_card',
            'picture', 
            'students.updated_at',
            'home_address',
            'address_line_2',
            'phone_home',
            'phone_cell',
            'email',
            'father_name',
            'father_home_address',
            'father_phone_home',
            'father_occupation',
            'father_business_place',
            'father_business_address',
            'father_business_phone',
            'email_father',
            'mobile_father',
            'mobile_phone_father',
            'mother_name',
            'mother_home_address',
            'mother_phone_home',
            'mother_occupation',
            'mother_business_place',
            'mother_business_address',
            'mother_business_phone',
            'mobile_phone_mother',
            'email_mother',
            'mobile_mother',
            'guardian_name',
            'guardian_business_place',
            'guardian_business_address',
            'business_phone_guardian',
            'mobile_phone_guardian',
            'email_guardian',
            'mobile_guardian',
            'guardian_occupation',
            'guardian_phone',
            )
        ->whereBetween('class_id',['0%', '7%']) 
        ->orderBy('last_name')
        ->orderBy('first_name')               
        ->get();

        foreach($students as $student) { 
            // $student->first_name =  ucwords(strtolower($student->first_name));          
            // $student->last_name =  ucwords(strtolower($student->last_name));          
            if($student->file_birth_certificate)
            $student->file_birth_certificate = URL::asset('storage/'.$student->file_birth_certificate);
            if($student->file_sea_slip)
            $student->file_sea_slip = URL::asset('storage/'.$student->file_sea_slip);
            if($student->file_immunization_card) 
            $student->file_immunization_card = URL::asset('storage/'.$student->file_immunization_card);
            if($student->picture)
            $student->picture = URL::asset('storage/'.$student->picture);
            if($student->file_passport)
            $student->file_passport = URL::asset('storage/'.$student->file_passport);
            if($student->file_student_permit)
            $student->file_student_permit = URL::asset('storage/'.$student->file_student_permit);
        }

        return $students;
    }

    public function data($form = null)
    {
        $data = [];
        $students = Student::whereBetween('class_id',['0%','7%'])
        ->orderBy('id')
        ->get();

        if($form){
            $students = Student::where('class_id','like', $form.'%')->get();            
        }
        foreach($students as $student){
            $student->sex = $student->sex[0];
            array_push($data, $student);
        }
        return $data;
    }

    public function upload ()
    {
        ini_set('max_execution_time', '1500');
        $file = './files/students.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        // return $rows;
        $records = 0;
        $updates = 0;
        $studentData = array();
        $userRecords = array();
        $studentRecords = array();
        $errorRecords = array();
        $data = array();


        for($i = 2; $i <= $rows; $i++){
            
            try {
               $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
               $lastName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
               $firstName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
               $middleName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();            
               $dob = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
               if($dob && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)){
                   $dob = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dob);
                   $dob = date_format($dob, "Y-m-d");
               }
               $homeAddress = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
               $addressLine2 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
               $telephoneH = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
               $phoneCell = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
               $emergencyContact = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
               $guardianOccupation = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
               $businessAddressG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
               $telephoneG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
               $fatherName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
               $homeAddressF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
               $homePhoneF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
               $fatherOccupation = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(17,$i)->getValue();
               $businessAddressF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(18,$i)->getValue();
               $businessPhoneF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(19,$i)->getValue();
               $motherName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(20,$i)->getValue();
               $homeAddressM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(21,$i)->getValue();
               $homePhoneM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(22,$i)->getValue();
               $motherOccupation = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(23,$i)->getValue();
               $businessAddressM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(24,$i)->getValue();
               $businessPhoneM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(25,$i)->getValue();
               $medicalHistory = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(26,$i)->getValue();
               $picture = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(27,$i)->getValue();
               $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,$i)->getValue();
               $entryDate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(29,$i)->getValue();
               if($entryDate && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $entryDate)){
                   $entryDate = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($entryDate);
                   $entryDate = date_format($entryDate, "Y-m-d");
               }
               $details = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(30,$i)->getValue();
               $previousSchool = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(31,$i)->getValue();
               $religionCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(32,$i)->getValue();
            //    if($religionCode && !is_numeric($religionCode)){
            //     $errorRecords[] = array($id, $religionCode);
            //     continue;
            //    }
               $ethnicGroupCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(33,$i)->getValue();
               $placeOfBirth = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(34,$i)->getValue();
               if(!$placeOfBirth) $placeOfBirth = 'Trinidad and Tobago';
               $nationality = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(35,$i)->getValue();
               if(!$nationality) $nationality = 'Trinidadian';
               $hobbies = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(36,$i)->getValue();
               $sex = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(37,$i)->getValue();
               $seaNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(38,$i)->getValue();
               $businessPlaceF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(39,$i)->getValue();
               $businessPlaceM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(40,$i)->getValue();
               $businessPlaceG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(41,$i)->getValue();
               $houseCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(42,$i)->getValue();
            //    if($houseCode & !is_numeric($houseCode)){
            //     $errorRecords[] = array($id, $houseCode);
            //     continue;
            //    }
               $guardianName = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(43,$i)->getValue();
               $achievements = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(44,$i)->getValue();
               $transport = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(45,$i)->getValue();
            //    if($transport && !is_numeric($transport)){
            //     $errorRecords[] = array($id, $transport);
            //     continue;
            //    }
               $schoolFeeding = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(46,$i)->getValue();
               if($schoolFeeding && !is_numeric($schoolFeeding)){
                $errorRecords[] = array($id, $schoolFeeding);
                continue;
               }
               $birthCertNo = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(47,$i)->getValue();
               $mobileF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(48,$i)->getValue();
               $mobileM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(49,$i)->getValue();
               $mobileG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(50,$i)->getValue();
               $dateOfLeaving = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(51,$i)->getValue();
               if($dateOfLeaving && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateOfLeaving)){
                   $dateOfLeaving = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dateOfLeaving);
                   $dateOfLeaving = date_format($dateOfLeaving, "Y-m-d");
               }
               $activities = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(52,$i)->getValue();
               $email = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(53,$i)->getValue();
               $repeater = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(54,$i)->getValue();
               if($repeater && !is_numeric($repeater)){
                $errorRecords[] = array($id, $repeater);
                continue;
               }
               $transferIn = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(55,$i)->getValue();
               if($transferIn && !is_numeric($transferIn)){
                $errorRecords[] = array($id, $transferIn);
                continue;
               }
               $idNumberF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(56,$i)->getValue();
               $idNumberM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(57,$i)->getValue();
               $idNumberG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(58,$i)->getValue();
               $livingStatus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(59,$i)->getValue();
               $fileBirthCertificate = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(60,$i)->getValue();
               $fileImmunizationCard = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(61,$i)->getValue();
               $fileSeaSlip = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(62,$i)->getValue();
               $classGroup = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(63,$i)->getValue();
               $internetAccess = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(64,$i)->getValue();
               $deviceType = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(65,$i)->getValue();
               $bloodType = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(66,$i)->getValue();
               $regionalCorporation = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(67,$i)->getValue();
               $barCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(68,$i)->getValue();
               $ncseCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(69,$i)->getValue();
               $hepatitis = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(70,$i)->getValue();
               $polio = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(71,$i)->getValue();
               $diphtheria = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(72,$i)->getValue();
               $tetanus = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(73,$i)->getValue();
               $yellowFever = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(74,$i)->getValue();
               $measles = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(75,$i)->getValue();
               $tb = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(76,$i)->getValue();
               $chickenPox = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(77,$i)->getValue();
               $typhoid = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(78,$i)->getValue();
               $rheumaticFever = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(79,$i)->getValue();
               $poorEyesight = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(80,$i)->getValue();
               $poorHearing = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(81,$i)->getValue();
               $diabetes = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(82,$i)->getValue();
               $asthma = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(83,$i)->getValue();
               $epilepsy = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(84,$i)->getValue();
               $allergy = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(85,$i)->getValue();
               $specialConsideration = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(86,$i)->getValue();
               $relativesInSchool = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(87,$i)->getValue();            
               $homePhoneE = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(88,$i)->getValue();
               $relationToChild = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(89,$i)->getValue();
               $placeInFamily = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(90,$i)->getValue();
               $noInFamily = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(91,$i)->getValue();
               $noAtHome = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(92,$i)->getValue();
               $maritalStatusM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(93,$i)->getValue();
               $maritalStatusF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(94,$i)->getValue();
               $maritalStatusG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(95,$i)->getValue();
               $otherIllness = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(96,$i)->getValue();
               $emailF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(97,$i)->getValue();
               $emailM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(98,$i)->getValue();
               $emailG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(99,$i)->getValue();
               $businessPhoneG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(100,$i)->getValue();
               $homeAddressG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(101,$i)->getValue();
               $homePhoneG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(102,$i)->getValue();
               $mobilePhoneF = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(103,$i)->getValue();
               $mobilePhoneG = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(104,$i)->getValue();
               $mobilePhoneM = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(105,$i)->getValue();
            //code...
                if(!$lastName || !$firstName) continue;
                $studentRecord = Student::where('id', $id)
                ->first();
                if($studentRecord) continue;
                $studentRecords[] = Student::create(
                    [
                        'id' => $id,
                        'last_name' => $lastName, 
                        'first_name' => $firstName,
                        'middle_name' => $middleName,
                        'date_of_birth' => $dob,
                        'home_address' =>  mb_convert_encoding($homeAddress, 'UTF-8', 'Windows-1252'),
                        'address_line_2' => $addressLine2,
                        'phone_home' => preg_replace('/[(]|[)]|[-]|\s+/', '',$telephoneH),
                        'phone_cell' => preg_replace('/[(]|[)]|[-]|\s+/', '',$phoneCell),
                        'emergency_contact' => $emergencyContact,
                        'guardian_occupation' => $guardianOccupation,
                        'guardian_business_address' => $businessAddressG,
                        'guardian_phone' => preg_replace('/[(]|[)]|[-]|\s+/', '',$telephoneG),
                        'father_name' => $fatherName,
                        'father_home_address' => mb_convert_encoding($homeAddressF, 'UTF-8', 'Windows-1252'),
                        'father_phone_home' => preg_replace('/[(]|[)]|[-]|\s+/', '',$homePhoneF),
                        'father_occupation' => $fatherOccupation,
                        'father_business_address' => $businessAddressF,
                        'father_business_phone' => preg_replace('/[(]|[)]|[-]|\s+/', '',$businessPhoneF),
                        'mother_name' => $motherName,
                        'mother_home_address' => mb_convert_encoding($homeAddressM, 'UTF-8', 'Windows-1252'),
                        'mother_phone_home' => preg_replace('/[(]|[)]|[-]|\s+/', '',$homePhoneM),
                        'mother_occupation' => $motherOccupation,
                        'mother_business_address' => $businessAddressM,
                        'mother_business_phone' => preg_replace('/[(]|[)]|[-]|\s+/', '',$businessPhoneM),
                        'medical_history' => $medicalHistory,
                        // 'picture' => $picture,
                        'class_id' => $classId,
                        'entry_date' => $entryDate,
                        'details' => $details,
                        'previous_school' => $previousSchool,
                        'religion_code' => $religionCode,
                        'ethnic_group_code' => $ethnicGroupCode,
                        'place_of_birth' => str_replace('_x000d_', '', $placeOfBirth),
                        'nationality' => $nationality,
                        'hobbies' => $hobbies,
                        'sex' => $sex,
                        'sea_no' => $seaNo,
                        'father_business_place' => $businessPlaceF,
                        'mother_business_place' => $businessPlaceM,
                        'guardian_business_place' => $businessPlaceG,
                        'house_code' => $houseCode,
                        'guardian_name' => $guardianName,
                        'achievements' => $achievements,
                        'transport' => $transport,
                        'school_feeding' => $schoolFeeding,
                        'birth_certificate_no' => $birthCertNo,
                        'mobile_phone_father' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobileF),
                        'mobile_phone_mother' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobileM),
                        'mobile_phone_guardian' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobileG),
                        'date_of_leaving' => $dateOfLeaving,
                        'activities' => $activities,
                        'email' => $email,
                        'repeater' => $repeater,
                        'transfer_in' => $transferIn,
                        'id_card_father' => $idNumberF,
                        'id_card_mother' => $idNumberM,
                        'id_card_guardian' => $idNumberG,
                        'living_status' => $livingStatus,
                        'file_birth_certificate' => $fileBirthCertificate,
                        'file_immunization_card' => $fileImmunizationCard,
                        'file_sea_slip' => $fileSeaSlip,
                        'class_group' => $classGroup,
                        'internet_access' => $internetAccess,
                        'device_type' => $deviceType,
                        'blood_type' => $bloodType,
                        'regional_corporation' => $regionalCorporation,
                        'bar_code' => $barCode,
                        'ncse_code' => $ncseCode,
                        'hepatitis' => $hepatitis,
                        'polio' => $polio,
                        'diphtheria' => $diphtheria,
                        'tetanus' => $tetanus,
                        'yellow_fever' => $yellowFever,
                        'measles' => $measles,
                        'tb' => $tb,
                        'chicken_pox' => $chickenPox,
                        'typhoid' => $typhoid,
                        'rheumatic_fever' => $rheumaticFever,
                        'poor_eyesight' => $poorEyesight,
                        'poor_hearing' => $poorHearing,
                        'diabetes' => $diabetes,
                        'asthma' => $asthma,
                        'epilepsy' => $epilepsy,
                        'allergy' => $allergy,
                        'special_consideration' => $specialConsideration,
                        'relative_in_school' => $relativesInSchool,
                        'emergency_home_phone' => preg_replace('/[(]|[)]|[-]|\s+/', '',$homePhoneE),
                        'relation_to_child' => $relationToChild,
                        'place_in_family' => $placeInFamily,
                        'no_in_family' => $noInFamily,
                        'no_at_home' => $noAtHome,
                        'mother_marital_status' => str_replace("_x000D_" , "", $maritalStatusM),
                        'father_marital_status' => $maritalStatusF,
                        'guardian_marital_status' => $maritalStatusG,
                        'other_illness' => $otherIllness,
                        'email_father' => $emailF,
                        'email_mother' => $emailM,
                        'email_guardian' => $emailG,
                        'business_phone_guardian' => preg_replace('/[(]|[)]|[-]|\s+/', '',$businessPhoneG),
                        'home_address_guardian' => $homeAddressG,
                        'home_phone_guardian' => preg_replace('/[(]|[)]|[-]|\s+/', '',$homePhoneG),
                        'mobile_father' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobilePhoneF),
                        'mobile_guardian' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobilePhoneG),
                        'mobile_mother' => preg_replace('/[(]|[)]|[-]|\s+/', '',$mobilePhoneM),
                    ]
                );

                $userRecords[] = UserStudent::firstOrcreate(
                    ['student_id' => $id],
                    [
                        'name' => $firstName.' '.$lastName,
                        'password' => Hash::make($birthCertNo)
                    ]
                );

           } catch (\Throwable $th) {
                $studentRecord = new Student;
                $studentRecord->first_name = $firstName;
                $studentRecord->last_name = $lastName;
                $studentRecord->id = $id;
                $studentRecord->error = $th->getMessage();
                $errorRecords[] = $studentRecord;
                return $th->getMessage();
           }


        }

        return $errorRecords;

        $data['Students'] = $studentRecords;
        $data['Student Records'] = sizeof($studentRecords);
        $data['Users'] = $userRecords;
        $data['User Records'] = sizeof ($userRecords);
        $data['Errors'] = $errorRecords;
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $data;
    }

    public function uploadNewStudents(Request $request)
    {  
        $data = [];
        $students = json_decode($request->data);
        foreach($students as $student){
            $studentId = $student->id;
            $house = House::where('name', $student->house_name)->first();
            $house_id = $house ? $house->id : null;
            try{
                Student::updateOrCreate(
                    [
                        'id' => $studentId
                    ],
                    [                        
                        'first_name' => trim(ucwords(strtolower($student->first_name))),
                        'last_name' => trim(ucwords(strtolower($student->last_name))),
                        'sea_no' => $student->sea_no,
                        'class_id' => trim($student->class_id),
                        'date_of_birth' => $student->date_of_birth,
                        'house_code' => $house_id,
                        'entry_date' => $student->entry_date,
                        'birth_certificate_no' => $student->sea_no,
                        // 'email' => trim(strtolower($student->first_name)).'.'.trim(strtolower($student->last_name)).'@diegosec.edu.tt'
                    ]
                );
                
                $studentRecord = Student::leftJoin(
                    'houses',
                    'houses.id',
                    'students.house_code'
                )
                ->where('students.id', $studentId)
                ->select(
                    'students.id', 
                    'first_name', 
                    'last_name', 
                    'sea_no', 
                    'class_id', 
                    'houses.name as house_name',
                    'date_of_birth',
                    'entry_date', 
                )
                ->first();

                // $password = date_format(date_create($student->date_of_birth),"Ymd");
                $password = $student->sea_no;

                UserStudent::updateOrcreate(
                    ['student_id' => $studentId],
                    [
                        'name' => $student->first_name.' '.$student->last_name,
                        'student_id' => $studentId,
                        'password' => Hash::make($password)
                    ]
                );

                $studentUser = UserStudent::where('student_id', $studentId)
                ->first();

                $studentRecord->error = 0;
                $studentRecord->user = $studentUser;

            } catch (Throwable $e) {
                $studentRecord = new Student;
                $studentRecord->id = $student->id;
                $studentRecord->last_name = $student->last_name;
                $studentRecord->first_name = $student->first_name;
                $studentRecord->sea_no = $student->sea_no;
                $studentRecord->class_id = $student->class_id;
                $studentRecord->error = 1;
                $studentRecord->error_message = $e->getMessage();
                $studentRecord->user = null;
            }

            array_push($data, $studentRecord);

        }

        return $data;
    }

    public function promote (Request $request)
    {
        $records = $request->all();
        $updates = []; 
        foreach($records as $record){ 
            
            $student = Student::select('id', 'class_id')
            ->where('id', $record['id'])
            ->first();

            if($student){                
                $prev_class_id = $student->class_id;
                $student->class_id = $record['class_id'];
                $student->save();
                if($student->wasChanged('class_id')){
                    $student->prev_class_id = $prev_class_id;
                    array_push($updates, $student);
                }
            }
        }

        return $updates;
        
    }

    public function getClassAssignments()
    {
        $students = Student::select('id', 'class_id')->get();

        return $students;        
    }
   
}
