<?php

namespace App\Http\Controllers;

use App\Models\FormTeacher;
use App\Models\FormDean;
use Illuminate\Http\Request;
use App\Models\TeacherLesson;
use Illuminate\Support\Facades\DB;
use App\Models\FormClassSubject;
use App\Models\FormClass;

class FormTeacherController extends Controller
{
    public function show(Request $request)
    {
        $id = $request->id;
        $year = $request->year;

        return DB::table('form_teachers')
        ->join('form_classes', 'form_classes.id', 'form_teachers.form_class_id')
        ->join('employees', 'form_teachers.employee_id', 'employees.id')
        ->select(
            'form_teachers.form_class_id',
            DB::raw('CONCAT(form_classes.class_name, " - ", employees.first_name, " ", employees.last_name) as class_name'),
        )
        ->where([
            ['form_teachers.academic_year_id', $year ],
            ['employee_id', $id ]
        ])
        ->get();
        // return FormTeacher::join(
        //     'form_classes', 
        //     'form_classes.id', 
        //     'form_teachers.form_class_id'
        // )
        // ->select(
        //     'form_teachers.form_class_id',
        //     'form_classes.class_name',
        //     'form_classes.class_group'
        // )
        // ->where([
        //     ['form_teachers.academic_year_id', $year ],
        //     ['employee_id', $id ]
        // ])
        // ->get();

    }

    public function store(Request $request)
    {
        $data = array();
        $formClasses = $request->input('formClasses');
        $academicYearId = $request->input('academicYearId');
        $employeeId = $request->input('employeeId');

        $formTeacherAssignments = FormTeacher::where([
            ['academic_year_id', $academicYearId],
            ['employee_id', $employeeId]
        ])
        ->get();

        $databaseFormClasses = $formTeacherAssignments->pluck('form_class_id')->toArray();

        $newFormClasses  = array_diff($formClasses, $databaseFormClasses);

        $deleteFormClasses = array_diff($databaseFormClasses, $formClasses);

        foreach($formClasses as $formClass)
        {
            if(in_array($formClass, $newFormClasses))
            {
                $data[] = FormTeacher::create([
                    'employee_id' => $employeeId,
                    'academic_year_id' => $academicYearId,
                    'form_class_id' => $formClass,
                ]);

                $formClassRecord = FormClass::where('id', $formClass)->first();
                $formLevel = $formClassRecord ? $formClassRecord->form_level : null;

                $formClassSubjects = FormClassSubject::where('form_class_level', $formLevel)->get();

                foreach($formClassSubjects as $formClassSubject)
                {
                    TeacherLesson::create([
                        'employee_id' => $employeeId,
                        'academic_year_id' => $academicYearId,
                        'form_class_id' => $formClass,
                        'subject_id' => $formClassSubject->subject_id
                    ]);
                }


            }
        }

        foreach($databaseFormClasses as $formClass)
        {
            if(in_array($formClass, $deleteFormClasses))
            {
                FormTeacher::where([
                    ['employee_id', $employeeId],
                    ['academic_year_id', $academicYearId],
                    ['form_class_id', $formClass]
                ])
                ->delete();

                TeacherLesson::where([
                    ['employee_id', $employeeId],
                    ['academic_year_id', $academicYearId],
                    ['form_class_id', $formClass]
                ])
                ->forceDelete();

            }
        }

        return $data;


        // $formTeacherClass = ModelsFormTeacher::whereId($request->id)->first();
        // $class_id = $request->class_id;
        // $deanFormLevel = $request->input('dean_form_level');
        // $employeeId = $request->input('employee_id');
        // // $deanFormLevelAssigned = FormDean::where()

        // if(!$class_id && $formTeacherClass){
        //     $formTeacherClass->delete();
        //     return 'lesson deleted';
        // }
        
        // if($class_id && $formTeacherClass)
        // {            
        //     $formTeacherClass->class_id = $class_id;
        //     $formTeacherClass->save();
        //     return $formTeacherClass;
        // }

        // if(!$formTeacherClass){            
        //     $formTeacherClass = ModelsFormTeacher::create([
        //         'employee_id' => $request->employee_id,
        //         'class_id' => $class_id,
        //         'academic_year_id' => $request->academic_year_id
        //     ]);
        //     return $formTeacherClass;            
        // }       
        
    }
}
