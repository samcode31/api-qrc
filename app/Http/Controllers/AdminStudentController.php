<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Throwable;
use App\Models\House;
use App\Models\Student;
use App\Models\UserStudent;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

class AdminStudentController extends Controller
{
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
            'file_passport',
            'file_student_permit',
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
            'house_code',
            )
        ->where(function($query) {
            $query->where('students.class_id', 'LIKE', '1%')
                    ->orWhere('students.class_id', 'LIKE', '2%')
                    ->orWhere('students.class_id', 'LIKE', '3%')
                    ->orWhere('students.class_id', 'LIKE', '4%')
                    ->orWhere('students.class_id', 'LIKE', '5%')
                    ->orWhere('students.class_id', 'LIKE', '6%');
        })
        ->orderBy('last_name')
        ->orderBy('first_name')               
        ->get();

        foreach($students as $student) { 
            // $student->first_name =  ucwords(strtolower($student->first_name));          
            // $student->last_name =  ucwords(strtolower($student->last_name));          
            $student->first_name =  ucwords(strtolower($student->first_name));          
            $student->last_name =  ucwords(strtolower($student->last_name));          
            if($student->file_birth_certificate)
            $student->file_birth_certificate = Storage::url($student->file_birth_certificate);
            if($student->file_sea_slip)
            $student->file_sea_slip = Storage::url($student->file_sea_slip);
            if($student->file_immunization_card) 
            $student->file_immunization_card = Storage::url($student->file_immunization_card);
            if($student->picture)
            $student->picture = Storage::url($student->picture);
            if($student->file_passport)
            $student->file_passport = Storage::url($student->file_passport);
            if($student->file_student_permit)
            $student->file_student_permit = Storage::url($student->file_student_permit);
        }

        $data['current_students'] = $students;
        $data['current_students_count'] = sizeof($students);

        $students = Student::leftJoin(
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
            'file_passport',
            'file_student_permit',
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
            'house_code',
            )
        ->whereRaw("class_id NOT LIKE '1%'") 
        ->whereRaw("class_id NOT LIKE '2%'") 
        ->whereRaw("class_id NOT LIKE '3%'") 
        ->whereRaw("class_id NOT LIKE '4%'") 
        ->whereRaw("class_id NOT LIKE '5%'") 
        ->whereRaw("class_id NOT LIKE '6%'") 
        ->orderBy('last_name')
        ->orderBy('first_name')               
        ->get();

        foreach($students as $student) { 
            // $student->first_name =  ucwords(strtolower($student->first_name));          
            // $student->last_name =  ucwords(strtolower($student->last_name));          
            $student->first_name =  ucwords(strtolower($student->first_name));          
            $student->last_name =  ucwords(strtolower($student->last_name));          
            if($student->file_birth_certificate)
            $student->file_birth_certificate = Storage::url($student->file_birth_certificate);
            if($student->file_sea_slip)
            $student->file_sea_slip = Storage::url($student->file_sea_slip);
            if($student->file_immunization_card) 
            $student->file_immunization_card = Storage::url($student->file_immunization_card);
            if($student->picture)
            $student->picture = Storage::url($student->picture);
            if($student->file_passport)
            $student->file_passport = Storage::url($student->file_passport);
            if($student->file_student_permit)
            $student->file_student_permit = Storage::url($student->file_student_permit);
        }

        $data['archived_students'] = $students;
        $data['archived_students_count'] = sizeof($students);


        return $data;
    }
}
