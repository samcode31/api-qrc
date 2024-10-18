<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentRegistration;
use App\Models\FormClass;

class StudentRegistrationController extends Controller
{
    public function store(Request $request)
    {
        return StudentRegistration::where([
            ['class_level', $request->class_level],
            ['form_class_id', $request->form_class_id]
        ])
        ->update([
            "open" => $request->open,
            "deadline" => $request->deadline
        ]);
    }

    public function show(Request $request)
    {
        $classLevel = $request->input('class_level');
        $formClassId = $request->input('form_class_id');
        return StudentRegistration::where([
            ['class_level', $classLevel],
            ['form_class_id', $formClassId]
        ])->first();
    }

    public function showAll()
    {
        $studentRegistrationRecords = StudentRegistration::all();
        foreach($studentRegistrationRecords as $key => $studentRegistrationRecord)
        {
            $formClassRecord = FormClass::where('class_level', $studentRegistrationRecord->class_level)->first();
            $className = $formClassRecord ? $formClassRecord->class_name : null;
            $studentRegistrationRecord->class_name = $className;
        }
        return $studentRegistrationRecords;
    }

    public function storeDeadline(Request $request)
    {
        return StudentRegistration::where([
            ['form_level', $request->form_level],
            ['form_class_id', $request->form_class_id]
        ])
        ->update([
            "open" => $request->open,
            "deadline" => $request->deadline
        ]);
    }
}
