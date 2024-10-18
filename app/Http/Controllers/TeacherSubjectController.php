<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherLesson;

class TeacherSubjectController extends Controller
{
    public function show(Request $request)
    {
        $id = $request->employee_id;
        $year = $request->academic_year_id;
        $lessons = TeacherLesson::join(
            'form_classes',
            'teacher_lessons.form_class_id',
            'form_classes.id'
        )
        ->where([
            ['employee_id', $id],
            ['teacher_lessons.academic_year_id', $year]
        ])        
        ->orderBy('teacher_lessons.form_class_id')       
        ->get();

        $records = [];
        foreach($lessons as $lesson){
            $lesson->subject;
            $lesson->formClass;
            array_push($records, $lesson);
        }

        return $records;
    }
}
