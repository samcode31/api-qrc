<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentSubject;
use App\Models\FormClass;

class StudentSubjectController extends Controller
{
    public function show (Request $request)
    {
        $subject_id = $request->subject_id;

        return StudentSubject::join('students', 'student_subjects.student_id', '=', 'students.id')
            ->select(
                'student_subjects.student_id',
                'students.first_name', 
                'students.last_name', 
                'students.class_id',
                'student_subjects.employee_id'           
            )
            ->where('student_subjects.subject_id', $subject_id)
            ->where(function ($query) {
                $query->where('students.class_id', 'LIKE', '1%')
                    ->orWhere('students.class_id', 'LIKE', '2%')
                    ->orWhere('students.class_id', 'LIKE', '3%')
                    ->orWhere('students.class_id', 'LIKE', '4%')
                    ->orWhere('students.class_id', 'LIKE', '5%')
                    ->orWhere('students.class_id', 'LIKE', '6%');
            })
            ->orderBy('students.last_name')
            ->get();

    }

    public function store (Request $request)
    {
        return StudentSubject::updateOrCreate(
            [
                'student_id'=>$request->student_id,
                'subject_id'=>$request->subject_id,
            ],
            [
                'student_id'=>$request->student_id,
                'subject_id'=>$request->subject_id,
                'employee_id'=>$request->employee_id
            ]
        );
    }

    public function storeBatch (Request $request)
    {
        // $data = [];
        // $form_level = $request->form_level;
        // $class_id = $request->class_id;
        $students = $request->students;
        $subject_id = $request->subject_id;
        $employee_id = $request->employee_id;

        // $students_assigned = ModelsStudentSubject::join(
        //     'students', 
        //     'students.class_id', 
        //     'student_subjects.student_id'
        // )
        // ->select('student_subjects.student_id')
        // ->where([
        //     ['subject_id', $subject_id],
        //     ['class_id', $class_id]
        // ])
        // ->get();

        // if(!$class_id)
        // {            
        //     $students_assigned = ModelsStudentSubject::join(
        //         'students', 
        //         'students.id', 
        //         'student_subjects.student_id'
        //     )
        //     ->select('student_subjects.student_id', 'students.class_id')        
        //     ->addSelect([
        //         'form_level' => FormClass::select('form_level')
        //         ->whereColumn('students.class_id', 'form_classes.id')                        
        //     ])
        //     ->where('subject_id', $subject_id)            
        //     ->get();
        // }

        // $students_assigned = $students_assigned->where('form_level', $form_level);
        // // return $students_assigned;
        // $student_ids = array_column($students, 'id');
        // $deleted = 0;
        // foreach($students_assigned as $student){
        //     if(!in_array($student->student_id, $student_ids)){
        //         $deleted += ModelsStudentSubject::where([
        //             ['student_id', $student->student_id],
        //             ['subject_id', $subject_id]
        //         ])->delete();
        //     }
        // }

        $subjectAssignments = [];
        // return $students;
        foreach($students as $student){
            $subject_student_assigned = StudentSubject::updateOrCreate(
                [
                    'student_id' => $student['id'],
                    'subject_id' => $subject_id
                ],
                [
                    'employee_id' => $employee_id
                ]
            );
            array_push($subjectAssignments, $subject_student_assigned);
        }

        // $data['deleted'] = $deleted;
        // $data['assigned'] = $subjectAssignments;

        return $subjectAssignments;        
    }

    public function delete(Request $request){
        // return $request->all();
        $deleted = 0;
        $students = $request->students;
        $subject_id = $request->subject_id;
        foreach($students as $student){
            $deleted += StudentSubject::where([
                ['student_id', $student['id']],
                ['subject_id', $subject_id]
            ])->delete();
        }
        // return ModelsStudentSubject::where([
        //     ['student_id', $request->student_id],
        //     ['subject_id', $request->subject_id],
        // ])->delete();
        return $deleted;
    }
}
