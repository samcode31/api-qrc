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

        //check if teacher assigned to a class
        $formTeacherRecord = FormTeacher::join(
            'form_classes',
            'form_teachers.form_class_id',
            'form_classes.id'
        )
        ->select(
            'form_class_id',
            'class_level as form_class_level',
        )
        ->where([
            ['employee_id', $id],
            ['form_teachers.academic_year_id', $year]
        ])
        ->first();


        if($formTeacherRecord)
        {
            //teacher assigned to class
            $formClassId = $formTeacherRecord->form_class_id;

            // TeacherLesson::where([
            //     ['employee_id', $id],
            //     ['form_class_id', '<>', $formClassId]
            // ])->forceDelete();

            $formClassSubjects = FormClassSubject::join(
                'subjects',
                'form_class_subjects.subject_id',
                'subjects.id'
            )
            ->whereNull('form_class_level')
            ->select(
                'subject_id',
                'title as subject_title'
            )
            ->get();

            $formClassSubjectsCustom = FormClassSubject::join(
                'subjects',
                'form_class_subjects.subject_id',
                'subjects.id'
            )
            ->where('form_class_level', $formTeacherRecord->form_class_level)
            ->select(
                'subject_id',
                'title as subject_title'
            )
            ->get();

            if($formClassSubjectsCustom->count() > 0) $formClassSubjects = $formClassSubjectsCustom;

            foreach($formClassSubjects as $formClassSubject)
            {
                $teacherLesson = TeacherLesson::withTrashed()
                ->firstOrNew(
                    [
                        'employee_id' => $id,
                        'academic_year_id' => $year,
                        'subject_id' => $formClassSubject->subject_id,
                        'form_class_id' => $formClassId
                    ]
                );

                if($teacherLesson->trashed()) continue;

                $teacherLesson->save();
            }
        }

        //get unique subject ids as a teacher may have multiple classes with the same subject
        $uniqueSubjectIds = TeacherLesson::where([
            ['employee_id', $id],
            ['academic_year_id', $year]
        ])
        ->select('subject_id')
        ->distinct()
        ->pluck('subject_id');

        foreach($uniqueSubjectIds as $subjectId)
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

        $databaseSubjectClasses = TeacherLesson::withTrashed()
        ->where([
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
            $lesson = TeacherLesson::withTrashed()
            ->where([
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

            if($lesson->trashed())
            {
                //if lesson was deleted previously restore
                $lesson->restore();
            }

            //update existing lesson
            // $teacherLesson = TeacherLesson::where([
            //     ['employee_id', $employeeId],
            //     ['academic_year_id', $academicYearId],
            //     ['subject_id', $subjectId],
            //     ['form_class_id', $formClassId]
            // ])
            // ->first();

            $lesson->subject_id = $newSubjectId;
            $lesson->save();
            $data['updated'] = $lesson;
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
        $lesson = $request->input('lesson');
        $employeeId = $lesson['employeeId'];
        $academicYearId = $lesson['academicYearId'];
        $formClasses = $lesson['formClasses'];
        $subjectId = $lesson['subjectId'];
        
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
