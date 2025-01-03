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
        return FormTeacher::where([
            ['academic_year_id', $request->year ],
            ['employee_id', $request->id ]
        ])
        ->pluck('form_class_id')
        ->toArray();

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
            }
        }

        return $data;
        
    }
}
