<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormDean;

class FormDeanController extends Controller
{
    public function show (Request $request)
    {
        $academicYearId = $request->input('academicYearId');
        $employeeId = $request->input('employeeId');
        
        return FormDean::where([
            ['academic_year_id', $academicYearId],
            ['employee_id', $employeeId]
        ])
        ->pluck('form_class_id')
        ->toArray();
    }

    public function store (Request $request)
    {
        $academicYearId = $request->input('academicYearId');
        $employeeId = $request->input('employeeId');
        $formClasses = $request->input('formClasses');

        $formDeanAssignments = FormDean::where([
            ['academic_year_id', $academicYearId],
            ['employee_id', $employeeId]
        ])->get();

        $databaseFormClasses = $formDeanAssignments->pluck('form_class_id')->toArray();

        $newFormClasses = array_diff($formClasses, $databaseFormClasses);

        $deleteFormClasses = array_diff($databaseFormClasses, $formClasses);

        $data = array();

        foreach($formClasses as $formClass)
        {
            if(in_array($formClass, $newFormClasses)){
                $data[] = FormDean::create([
                    'employee_id' => $employeeId,
                    'academic_year_id' => $academicYearId,
                    'form_class_id' => $formClass,
                    'form_level' => $formClass[0]
                ]);
            }

            
        }

        foreach($databaseFormClasses as $formClass)
        {
            if(in_array($formClass, $deleteFormClasses)){
                FormDean::where([
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
