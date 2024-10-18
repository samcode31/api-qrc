<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use DateTime;
use App\Models\AcademicTerm;

class PromotionController extends Controller
{
    public function store ()
    {
        $academicTermRecord = AcademicTerm::where('is_current', 1)->first();

        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        $date = new DateTime();

        $year = $date->format('y');       

        $distinctClassLevels = Student::join(
            'form_classes',
            'students.form_class_id',
            'form_classes.id'
        )
        ->select(
            'form_classes.class_level',
            'form_classes.class_group'
        )
        ->whereNotNull('class_level')
        ->distinct()
        ->orderBy('class_level', 'desc')
        ->get();


        foreach($distinctClassLevels as $record)
        {
            $classLevel = $record->class_level;
            switch ($classLevel) {
                case 7:
                    FormClass::firstOrcreate([
                        'class_name' => "G-$year",
                        'class_group' => $record->class_group
                    ]);
            
                    $graduateClass = FormClass::where([
                        ['class_name', 'G-'.$year],
                        ['class_group', $record->class_group]
                    ])->first();

                    $promotionClassId = $graduateClass->id;
                   
                    break;

                case 6:
                    $promotionClass = FormClass::where([
                        ['class_level', 7],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 5:
                    $promotionClass = FormClass::where([
                        ['class_level', 6],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 4:
                    $promotionClass = FormClass::where([
                        ['class_level', 5],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 3:
                    $promotionClass = FormClass::where([
                        ['class_level', 4],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 2:
                    $promotionClass = FormClass::where([
                        ['class_level', 3],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;

                case 1:
                    $promotionClass = FormClass::where([
                        ['class_level', 2],
                        ['class_group', $record->class_group],
                    ])->first();
                    
                    $promotionClassId = $promotionClass ? $promotionClass->id : null;
            
                    break;
                
                
            }

            Student::join(
                'form_classes',
                'students.form_class_id',
                'form_classes.id'
            )
            ->where([
                ['class_level', $classLevel],
                ['form_classes.class_group', $record->class_group],
            ])
            ->update(['form_class_id' => $promotionClassId]);
            
            
        }

        return DB::table('students')
        ->join('form_classes', 'students.form_class_id', 'form_classes.id')
        ->leftJoin('form_teachers', function($join) use ($academicYearId){
            $join->on('form_classes.id', '=', 'form_teachers.form_class_id')
            ->where('form_teachers.academic_year_id', $academicYearId);
        })
        ->leftJoin('employees', 'form_teachers.employee_id', 'employees.id')
        ->select(
            'students.id',
            'students.first_name',
            'students.last_name',
            DB::raw('
                CASE
                    WHEN employees.id IS NOT NULL THEN CONCAT(form_classes.class_name, " - ", employees.first_name, " ", employees.last_name) 
                    ELSE CONCAT(form_classes.class_name, " - GP",form_classes.class_group)
                END AS class_name    

            ')
        )
        ->where('form_classes.class_name', 'G-'.$year)
        ->orWhere(function($query) {
            $query->whereNotNull('form_classes.class_level')
            ->whereNotNull('form_classes.class_group');
        })
        ->get();
        
    }

    public function undo ()
    {
        
        
        $distinctClassLevels = Student::join(
            'form_classes',
            'students.form_class_id',
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
                'students.form_class_id',
                'form_classes.id'
            )
            ->where([
                ['class_level', $record->class_level],
                ['form_classes.class_group', $record->class_group],
            ])
            ->update(['form_class_id' => $previousClassId]);
        }

        $date = new DateTime();

        $year = $date->format('y');
        
        $graduateClasses = FormClass::where('class_name', 'G-'.$year)
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
                'students.form_class_id',
                'form_classes.id'
            )
            ->where([
                ['students.form_class_id', $graduateClass->id],
                ['form_classes.class_group', $graduateClass->class_group],
            ])
            ->update(['form_class_id' => $previousClassId]);
        }

    }
}
