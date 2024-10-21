<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TeacherLesson;
use App\Models\Subject;
use App\Models\FormClassSubject;
use App\Models\FormTeacher;
use Illuminate\Support\Collection;

class AdminTeacherLessonController extends Controller
{
    public function show(Request $request)
    {
        $data = array();
        $year = $request->input('year');
        $id = $request->input('id');

        $subjectIds = TeacherLesson::where([
            ['employee_id', $id],
            ['academic_year_id', $year]
        ])
        ->select('subject_id')
        ->distinct()
        ->pluck('subject_id');

        foreach($subjectIds as $subjectId)
        {
            $subjectFormClasses = TeacherLesson::where([
                ['employee_id', $id],
                ['academic_year_id', $year],
                ['subject_id', $subjectId]
            ])
            ->pluck('form_class_id');

            $subjectRecord = Subject::where('id', $subjectId)
            ->first();

            $lessonRecord = array();
            $lessonRecord['employee_id'] = $id;
            $lessonRecord['subject_id'] = $subjectId;
            $lessonRecord['subject_title'] = $subjectRecord->title;
            $lessonRecord['form_classes'] = $subjectFormClasses;
            $data[] = $lessonRecord;
        } 
        
        return $data;

    }

    public function store(Request $request)
    {
        $data = [];
        $employeeId = $request->input('employeeId');
        $academicYearId = $request->input('academicYearId');
        $subjectId = $request->input('subjectId');
        $newSubjectId = $request->input('newSubjectId');
        $formClasses = $request->input('formClasses');

        if(!$subjectId) {
            //new lesson added
            $subjectId = $newSubjectId;
        }

        $databaseSubjectClasses = TeacherLesson::where([
            ['employee_id', $employeeId],
            ['academic_year_id', $academicYearId],
            ['subject_id', $subjectId]
        ])
        ->get()
        ->pluck('form_class_id')
        ->toArray();

        $deleteSubjectClasses = array_diff($databaseSubjectClasses, $formClasses);

        foreach($formClasses as $formClassId)
        {
            $lesson = TeacherLesson::where([
                ['employee_id', $employeeId],
                ['academic_year_id', $academicYearId],
                ['subject_id', $subjectId],
                ['form_class_id', $formClassId]
            ])
            ->first();

            //new lesson
            if(!$lesson)
            {
                $data['inserted'] = TeacherLesson::create([
                    'employee_id' => $employeeId,
                    'academic_year_id' => $academicYearId,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClassId
                ]);
                continue;
            }

            //update existing lesson
            $teacherLesson = TeacherLesson::where([
                ['employee_id', $employeeId],
                ['academic_year_id', $academicYearId],
                ['subject_id', $subjectId],
                ['form_class_id', $formClassId]
            ])
            ->first();

            $teacherLesson->subject_id = $newSubjectId;
            $teacherLesson->save();
            $data['updated'] = $teacherLesson;
        }

        foreach($databaseSubjectClasses as $formClassId)
        {
            if(in_array($formClassId, $deleteSubjectClasses))
            {
                TeacherLesson::where([
                    ['employee_id', $employeeId],
                    ['academic_year_id', $academicYearId],
                    ['subject_id', $subjectId],
                    ['form_class_id', $formClassId]
                ])
                ->forceDelete();
            }
        }

        return $data;
    }

    public function delete(Request $request)
    { 
        $employeeId = $request->input('employeeId');
        $academicYearId = $request->input('academicYearId');
        $formClasses = $request->input('formClasses');
        $subjectId = $request->input('subjectId');

        $deleted = array();

        foreach($formClasses as $formClassId)
        {
            $deleted[] = TeacherLesson::where([
                ['employee_id', $employeeId],
                ['academic_year_id', $academicYearId],
                ['subject_id', $subjectId],
                ['form_class_id', $formClassId]
            ])->forceDelete();
        }
        return $deleted;
    }

}
