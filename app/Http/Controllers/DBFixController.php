<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Table2;
use App\Models\AssesmentCourse;
use App\Models\AssesmentEmployeeAssignment;
use App\Models\Student;

class DBFixController extends Controller
{
    public function fixCourseMark()
    {
        $data = array();
        
        $students = Table2::select('student_id')->distinct()->get();  
        
        foreach($students as $student)
        {
            $student_id = $student->student_id;
            $student = Student::where('id', $student_id)->first();
            $form_class_id = $student ? $student->class_id : null;

            $subjects = Table2::where([
                ['student_id', $student_id],
            ])
            ->get();


            foreach($subjects as $subject){
                $subject_id = $subject->subject_id;
                $courseMarkTotal = 0;
                $courseMarkMax = 0;

                $courseMarks = AssesmentCourse::join(
                    'assesment_employee_assignments',
                    'assesment_employee_assignments.id',
                    'assesment_course.assesment_employee_assignment_id'
                )
                ->where([
                    ['assesment_employee_assignments.subject_id', $subject_id],
                    ['assesment_course.student_id', $student_id],
                    ['assesment_employee_assignments.form_class_id', $form_class_id]
                ])
                ->get();

                foreach($courseMarks as $courseMark)
                {
                    if($courseMark->mark === null) continue;

                    $courseMarkMax += $courseMark->total;

                    $courseMarkTotal += $courseMark->mark;

                }

                $subjectCourseMark = $courseMarkMax ? ($courseMarkTotal/$courseMarkMax) * 100 : null;

                $data[] = Table2::updateOrCreate(
                    [
                        'student_id' => $student_id,
                        'subject_id' => $subject_id,
                    ],
                    [
                        'course_mark' => $subjectCourseMark
                    ]
                );

            }

        }

        return $data;
    }
}
