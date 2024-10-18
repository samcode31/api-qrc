<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentWeeklyTest;
use App\Models\FormTeacher;
use App\Models\AcademicTerm;
use App\Models\Student;
use App\Models\TeacherLesson;
use App\Models\Subject;

class StudentWeeklyTestController extends Controller
{
    
    private $weeklyTestMaxMark = 50;

    public function show (Request $request)
    {
        $data = array();
        $subjectId = $request->subjectId;
        $employeeId = $request->employeeId;
        $weekEndDate = $request->weekEndDate;
        $maxMark = $request->maxMark;

        $academicTermRecord = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        $formTeacherRecord = FormTeacher::where([
            ['employee_id', $employeeId],
            ['academic_year_id', $academicYearId]
        ])
        ->first();

        if(!$formTeacherRecord) abort(500, "Teacher not assigned to class");

        $formClassId = $formTeacherRecord->form_class_id;

        $students = Student::where('form_class_id', $formClassId)
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();


        foreach($students as $key => $student)
        {
            $studentWeeklyTestRecord = StudentWeeklyTest::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'subject_id' => $subjectId,
                    'week_end_date' => $weekEndDate
                ]
            );
            
            if($studentWeeklyTestRecord->wasRecentlyCreated)
            {
                $studentWeeklyTestRecord = StudentWeeklyTest::where([
                    ['student_id', $student->id],
                    ['subject_id', $subjectId],
                    ['week_end_date', $weekEndDate]
                ])
                ->first();
            }

            $studentWeeklyTestRecord->name = $student->last_name.", ".$student->first_name;
            $studentWeeklyTestRecord->count = $key+1;


            $data[] = $studentWeeklyTestRecord;
        }

        return $data;
    }

    public function showSubjects(Request $request)
    {
        $weekEndDate = $request->weekEndDate;
        $formClassId = $request->formClassId;
        $employeeId = $request->employeeId;

        $academicTermRecord = AcademicTerm::where('is_current', 1)->first();
        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        $subjects = StudentWeeklyTest::join(
            'students',
            'student_weekly_tests.student_id',
            'students.id'
        )
        ->join(
            'subjects',
            'student_weekly_tests.subject_id',
            'subjects.id'
        )
        ->where([
            ['form_class_id', $formClassId],
            ['week_end_date', $weekEndDate]
        ])
        ->select(
            'student_weekly_tests.subject_id',
            'subjects.title',
        )
        ->distinct()
        ->orderBy('subjects.title')
        ->get();

        $teacherLessonSubjects = TeacherLesson::where([
            ['form_class_id', $formClassId],
            ['academic_year_id', $academicYearId],
            ['employee_id', $employeeId]
        ])
        ->pluck('subject_id');

        foreach($teacherLessonSubjects as $subjectId){
            if($subjects->contains('subject_id', $subjectId)) continue;
            $subjectRecord = Subject::where('id', $subjectId)->first();
            $subjectTitle = $subjectRecord ? $subjectRecord->title : null;
            
            $subjects->push([
                'subject_id' => $subjectId,
                'title' => $subjectTitle,
                'max_mark' => $this->weeklyTestMaxMark,

            ]);
        }

        return $subjects;
    }

    public function postSubjectWeeklyTest (Request $request)
    {
        $subjectId = $request->subject_id;
        $employeeId = $request->employee_id;
        $weekEndDate = $request->week_end_date;
        $maxMark = $request->max_mark;  
        $studentId = $request->student_id;
        $mark = $request->mark;

        return StudentWeeklyTest::updateOrCreate(
            [
                'student_id' => $studentId,
                'subject_id' => $subjectId,
                'week_end_date' => $weekEndDate
            ],
            [
                'mark' => $mark,
                'max_mark' => $maxMark,
                'employee_id' => $employeeId
            ]
        );
    }

    public function delete (Request $request)
    {
        $subjectId = $request->subjectId;
        $weekEndDate = $request->weekEndDate;

        return StudentWeeklyTest::where([
            ['subject_id', $subjectId],
            ['week_end_date', $weekEndDate]
        ])
        ->delete();
    }


}
