<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentRegistration;

class StudentRegistrationController extends Controller
{
    public function store(Request $request)
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

    public function show($form_level, $form_class_id = null)
    {
        return StudentRegistration::where([
            ['form_level', $form_level],
            ['form_class_id', $form_class_id]
        ])->first();
    }

    public function showAll()
    {
        return StudentRegistration::all();
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
