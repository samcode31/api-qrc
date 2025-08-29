<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Models\AcademicTerm;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    public function store ()
    {
        $academicTermRecord = AcademicTerm::where('is_current', 1)->first();

        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        $date = new DateTime();

        $year = $date->format('y');       

        $distinctClassRecords = Student::join(
            'form_classes',
            'students.class_id',
            'form_classes.id'
        )
        ->select(
            'form_classes.id',
            'form_classes.form_level',
        )
        ->whereNotNull('form_level')
        ->distinct()
        ->orderBy('form_level', 'desc')
        ->get();


        foreach($distinctClassRecords as $record)
        {
            $formClassId = $record->id;
            $formClassSuffix = substr($formClassId, -1);

            switch ($record->form_level) 
            {
                case 7:
                    FormClass::firstOrcreate([
                        'id' => "G$year-$record->id",
                    ]);
            
                    $graduateClass = FormClass::where([
                        ['id', "G$year-$record->id"],
                    ])->first();

                    $promotionClassId = $graduateClass->id;
                   
                    break;

                case 6:
                    $promotionClass = FormClass::where([
                        ['form_level', 7],
                        ['id', 'LIKE', '%'.$formClassSuffix]
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 5:
                    FormClass::firstOrcreate([
                        'id' => "G$year-$record->id",
                    ]);
            
                    $graduateClass = FormClass::where([
                        ['id', "G$year-$record->id"],
                    ])->first();

                    $promotionClassId = $graduateClass->id;
            
                    break;

                case 4:
                    $promotionClass = FormClass::where([
                        ['form_level', 5],
                        ['id', 'LIKE', '%'.$formClassSuffix]
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 3:
                    $promotionClass = FormClass::where([
                        ['form_level', 4],
                        ['id', 'LIKE', '%'.$formClassSuffix]
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 2:
                    $promotionClass = FormClass::where([
                        ['form_level', 3],
                        ['id', 'LIKE', '%'.$formClassSuffix]
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 1:
                    $promotionClass = FormClass::where([
                        ['form_level', 2],
                        ['id', 'LIKE', '%'.$formClassSuffix]
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;
                
                
            }

            if(!isset($promotionClassId) || !$promotionClassId) continue;

            Student::where('class_id', $formClassId)
            ->update(['class_id' => $promotionClassId]);
            
        }


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
        ->where(function($query) {
            $query->where('students.class_id', 'LIKE', '0%')
                    ->orWhere('students.class_id', 'LIKE', '1%')
                    ->orWhere('students.class_id', 'LIKE', '2%')
                    ->orWhere('students.class_id', 'LIKE', '3%')
                    ->orWhere('students.class_id', 'LIKE', '4%')
                    ->orWhere('students.class_id', 'LIKE', '5%')
                    ->orWhere('students.class_id', 'LIKE', '6%')
                    ->orWhere('students.class_id', 'LIKE', '7%');
        })
        ->orderBy('last_name')
        ->orderBy('first_name')               
        ->get();

        foreach($students as $student) { 
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

        return $students;
        
    }

    public function undo ()
    {
        
        
        $distinctClassLevels = Student::join(
            'form_classes',
            'students.class_id',
            'form_classes.id'
        )
        ->select(
            'form_classes.class_level', 
            'form_classes.class_group'
        )
        ->whereNotNull('class_level')
        ->distinct()
        ->get();


        foreach($distinctClassLevels as $record)
        {
            switch ($record->class_level) {
                case 7:
                    $previousClass = FormClass::where([
                        ['class_level', 6],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $previousClassId = $previousClass ? $previousClass->id : null;
                    
                    break;

                case 6:
                    $previousClass = FormClass::where([
                        ['class_level', 5],
                        ['class_group', $record->class_group],
                    ])->first();

                    $previousClassId = $previousClass ? $previousClass->id : null;
                    break;

                case 5:
                    $previousClass = FormClass::where([
                        ['class_level', 4],
                        ['class_group', $record->class_group],
                    ])->first();

                    $previousClassId = $previousClass ? $previousClass->id : null;
                    break;

                case 4:
                    $previousClass = FormClass::where([
                        ['class_level', 3],
                        ['class_group', $record->class_group],
                    ])->first();

                    $previousClassId = $previousClass ? $previousClass->id : null;
                    break;

                case 3:
                    $previousClass = FormClass::where([
                        ['class_level', 2],
                        ['class_group', $record->class_group],
                    ])->first();

                    $previousClassId = $previousClass ? $previousClass->id : null;
                    break;

                case 2:
                    $previousClass = FormClass::where([
                        ['class_level', 1],
                        ['class_group', $record->class_group],
                    ])->first();

                    $previousClassId = $previousClass ? $previousClass->id : null;
                    break;
                
                
            }

            if(!isset($previousClassId) || !$previousClassId) continue;

            Student::join(
                'form_classes',
                'students.class_id',
                'form_classes.id'
            )
            ->where([
                ['class_level', $record->class_level],
                ['form_classes.class_group', $record->class_group],
            ])
            ->update(['class_id' => $previousClassId]);
        }

        $date = new DateTime();

        $year = $date->format('y');
        
        $graduateClasses = FormClass::where('id', 'G-'.$year)
        ->get();

        foreach($graduateClasses as $graduateClass)
        {
            $previousClass = FormClass::where([
                ['class_level', 7],
                ['class_group', $graduateClass->class_group],
            ])->first();

            $previousClassId = $previousClass ? $previousClass->id : null;
            
            Student::join(
                'form_classes',
                'students.class_id',
                'form_classes.id'
            )
            ->where([
                ['students.class_id', $graduateClass->id],
                ['form_classes.class_group', $graduateClass->class_group],
            ])
            ->update(['class_id' => $previousClassId]);
        }

    }
}
