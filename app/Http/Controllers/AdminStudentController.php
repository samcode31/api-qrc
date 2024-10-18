<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Throwable;
use App\Models\House;
use App\Models\Student;
use App\Models\UserStudent;
use App\Models\Employee;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicTerm;
use Illuminate\Support\Facades\URL;

class AdminStudentController extends Controller
{
    public function show ()
    {
        $data = array();

        $academicTermRecord = AcademicTerm::where('is_current', 1)->first();
        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        $students = DB::table('students')
        ->join('form_classes','students.form_class_id','form_classes.id')
        ->leftJoin('form_teachers', function ($join) use ($academicYearId) {
            $join->on('form_classes.id', '=', 'form_teachers.form_class_id')
            ->where('form_teachers.academic_year_id', $academicYearId);
        })
        ->leftJoin('employees', 'form_teachers.employee_id', 'employees.id')
        ->select(
            'students.id', 
            'students.first_name', 
            'students.last_name',
            'form_classes.class_level',
            'form_classes.class_group', 
            'students.form_class_id', 
            'birth_certificate_no', 
            'students.date_of_birth',
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
            'form_teachers.employee_id',
            'house_code',
            DB::raw('
                CASE
                    WHEN employees.id IS NOT NULL THEN CONCAT(form_classes.class_name, " - ", employees.first_name, " ", employees.last_name) 
                    WHEN form_classes.class_group IS NOT NULL THEN CONCAT(form_classes.class_name, " - GP",form_classes.class_group)
                    ELSE form_classes.class_name
                END AS class_name
            ')
        )
        ->orderBy('last_name')
        ->orderBy('first_name')               
        ->get();

        
        $studentsCurrent = $students->filter(function ($student) {
            return $student->class_level !== null;
        });

        $studentsArchive = $students->filter(function ($student) {
            return $student->class_level === null;
        });

        
        $studentsCurrentArray = [];
        $studentsCurrent->transform(function($student) use (&$studentsCurrentArray) {
            if($student->file_birth_certificate)
            $student->file_birth_certificate = Storage::url($student->file_birth_certificate);
            if($student->file_sea_slip)
            $student->file_sea_slip =Storage::url($student->file_sea_slip);
            if($student->file_immunization_card) 
            $student->file_immunization_card = Storage::url($student->file_immunization_card);
            if($student->picture)
            $student->picture = Storage::url($student->picture);
            $student->teacher = null;
            if($student->employee_id)
            {
                $employeeRecord = Employee::where('id', $student->employee_id)->first();
                $student->teacher = $employeeRecord ? $employeeRecord->first_name.' '.$employeeRecord->last_name : '';
            }
            $studentsCurrentArray[] = $student;
            return $student;
        });


        $data['current_students'] = $studentsCurrentArray;
        $data['current_students_count'] = sizeof($studentsCurrent);

        $studentsArchiveArray = [];
        foreach($studentsArchive as $student) { 
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
            $studentsArchiveArray[] = $student;
        }

        $data['archived_students'] = $studentsArchiveArray;
        $data['archived_students_count'] = sizeof($studentsArchive);


        return $data;
    }
}
