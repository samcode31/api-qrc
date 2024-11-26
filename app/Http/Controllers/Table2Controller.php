<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', '900');
use App\Http\Resources\Table2 as ResourcesTable2;
use App\Models\AcademicTerm;
use App\Models\AssesmentWeighting;
use Illuminate\Http\Request;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\Table1;
use App\Models\Table2;
use App\Models\StudentSubject;
use App\Models\TeacherLesson;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Models\AssesmentCourse;
use App\Models\AssesmentEmployeeAssignment;

class Table2Controller extends Controller
{
    public function show(Request $request)
    {        
        $classId = $request->class_id;
        $year = $request->year;
        $academicYearId = $year ? $year.($year + 1) : null;
        $term = $request->term;
        $subjectId = $request->subject_id;
        $employeeId = $request->employee_id;
        $formClasses = $request->form_classes;
        $formLevel = $request->form_level;

        $table2Records = [];
        $data = [];
        $total = 0;
        $registered = 0;
        $entered = 0;
        
        // $formClassRecord = FormClass::whereId($classId)->first();
        // $formLevel = $formClassRecord ? $formClassRecord->form_level : null;
        
        $classTotal = Table1::where([
            ['class_id', $classId],
            ['year', $year],
            ['term', $term]
        ])
        ->get();
        
        $studentsRegistered = Table1::join(
            'students',
            'students.id',
            'table1.student_id'
        )
        ->select(
            'students.first_name',
            'students.last_name',
            'table1.*'
        )
        ->whereIn('table1.class_id', $formClasses)
        ->where([
            ['year', $year],
            ['term', $term]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        if($formLevel >= 4 && $employeeId)
        {
            $studentsRegistered = Table1::join(
                'student_subjects',
                'student_subjects.student_id',
                'table1.student_id'
            )
            ->join(
                'students',
                'students.id',
                'table1.student_id'
            )
            ->select(
                'students.first_name',
                'students.last_name',
                'table1.*'
            )
            ->whereIn('table1.class_id', $formClasses)
            ->where([
                ['table1.year', $year],
                ['table1.term', $term],
                ['student_subjects.subject_id', $subjectId]
            ])
            ->where(function ($query) use ($employeeId) {
                $query->where('student_subjects.employee_id', $employeeId);
                    //   ->orWhereNull('student_subjects.employee_id');
            })
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
            
        }

        // Admin login
        if($formLevel >= 4 && !$employeeId)
        {
            $studentsRegistered = Table1::join(
                'student_subjects',
                'student_subjects.student_id',
                'table1.student_id'
            )
            ->join(
                'students',
                'students.id',
                'table1.student_id'
            )
            ->select(
                'students.first_name',
                'students.last_name',
                'table1.*'
            )
            ->whereIn('table1.class_id', $formClasses)
            ->where([
                ['table1.year', $year],
                ['table1.term', $term],
                ['student_subjects.subject_id', $subjectId]
            ])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();
        }

        //lower school
        $total = $classTotal->count();

        foreach($studentsRegistered as $student)
        {
            $registered++;                
           
            $studentId = $student->student_id;
            
            $studentMarkRecord = new Table2;
            $studentMarkRecord->student_id = $studentId;
            $studentMarkRecord->year = $year;
            $studentMarkRecord->term = $term;                    
            $studentMarkRecord->test = "End of Term";                    
            $studentMarkRecord->subject_id = $subjectId;                    
            $studentMarkRecord->exam_mark = null;                    
            $studentMarkRecord->course_mark = null;                                       
            $studentMarkRecord->late = 0;                    
            $studentMarkRecord->absent = 0;                    
            $studentMarkRecord->comment = null; 
            $studentMarkRecord->app = null; 
            $studentMarkRecord->con = null; 
            $studentMarkRecord->employee_id = null;
            $studentMarkRecord->class_id = $student->class_id;                    
            
            $table2Record = Table2::where([
                ['student_id', $studentId],
                ['year', $year],
                ['term', $term],
                ['subject_id', $subjectId]
            ])->first();
            
            if($table2Record)
            {
                $studentMarkRecord = $table2Record;
                $entered++;
            }

            // if($table2Record && strtotime($table2Record->updated_at) < $courseMarkRecord['updated_at'])
            // {
            //     $studentMarkRecord->course_mark = $courseMarkRecord['course_mark'];
            // }
            
            $studentPicture = Student::whereId($studentId)->first()->picture;
            $studentMarkRecord->first_name = $student->first_name;
            $studentMarkRecord->last_name = $student->last_name;
            $studentMarkRecord->picture = $studentPicture;
            array_push($data, $studentMarkRecord);                   
        } 
        
        $table2Records['data'] = $data;
        $table2Records['total'] = $total;
        $table2Records['registered'] = $registered;
        $table2Records['entered'] = $entered;
        return $table2Records;    
    }  
    
    private function getCourseMark($studentId, $academicYearId, $term, $subjectId, $employeeId , $formClassId)
    {
        $data = array();
        $courseMark = 0;
        $courseMarkTotal = 0;
        $courseAssessmentUpdatedAt = null;

        $assesments = AssesmentEmployeeAssignment::where([
            ['academic_year_id', $academicYearId],
            ['term', $term],
            ['subject_id', $subjectId],
            ['form_class_id', $formClassId],
            ['employee_id', $employeeId]
        ])->get();

        foreach($assesments as $assesment)
        {
            $courseMarkTotal += $assesment->total;
            $assesmentCourseRecord = AssesmentCourse::where([
                ['assesment_employee_assignment_id', $assesment->id],
                ['student_id', $studentId],
            ])
            ->first();

            if($assesmentCourseRecord){
                $courseMark += $assesmentCourseRecord->mark;
                $updatedAt = strtotime($assesmentCourseRecord->updated_at);

                if(!$courseAssessmentUpdatedAt || $courseAssessmentUpdatedAt < $updatedAt){
                    $courseAssessmentUpdatedAt = $updatedAt;
                }
            }
        }

        $courseMark = $courseMark ?  number_format(($courseMark/$courseMarkTotal)*100, 0) : null;
        $data['course_mark'] = $courseMark;
        $data['updated_at'] = $courseAssessmentUpdatedAt;
        return $data;
         
    }

    public function cmp($a, $b)
    {
        return strcmp($a->last_name, $b->last_name);
    }

    public function store(Request $request)
    {       

        $student_subject = StudentSubject::where([
            ['student_id',$request->student_id],
            ['subject_id',$request->subject_id]
        ])->first();

        if($student_subject){
            $student_subject->employee_id = $request->employee_id;
            $student_subject->save();
        } 
        
        $record = Table2::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
                'subject_id' =>$request->subject_id,
            ],
            [
                'exam_mark' => $request->exam_mark,
                'course_mark' => $request->course_mark,
                'app' => $request->app,
                'con' => $request->con,
                'late' => $request->late,
                'absent' => $request->absent,
                'comment' => $request->comment,
                'employee_id' => $request->employee_id
            ]
        );

        return $record;
    }

    public function storeBatch(Request $request)
    {       
        $data = array();
        $records = $request->all();
        
        foreach($records as $record)
        {
            $data['records'][] = Table2::create(
                [
                    'student_id' => $record['student_id'],
                    'year' => $record['year'],
                    'term' => $record['term'],
                    'subject_id' =>$record['subject_id'],
                    'exam_mark' => $record['exam_mark'],
                    'course_mark' => $record['course_mark'],
                    'app1' => $record['app1'],
                    'con1' => $record['con1'],
                    'coded_comment' => $record['coded_comment'],
                    'coded_comment_1' => $record['coded_comment_1'],
                ]
            );

        }
        $data['count'] = sizeof($data['records']);
        return $data;
    }

    public function upload()
    {
        ini_set('max_execution_time', '1500');
        $file = './files/Table2.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){
            try {
                //code...
                $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
                $year = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
                $term = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
                $subj_code = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
                $exam_mark = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
                $course_mark = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
                $app1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
                $con1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
                $coded_comment = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
                $coded_comment_1 = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
                
                $table1Record = Table2::create(
                    [
                        'student_id' => $id,
                        'year' => $year,
                        'term' => $term,
                        'subject_id' => $subj_code,
                        'exam_mark' => $exam_mark,
                        'course_mark' => $course_mark,                    
                        'app1' => $app1,
                        'con1' => $con1,
                        'coded_comment' => $coded_comment,
                        'coded_comment_1' => $coded_comment_1,
                    ]
                );
                //return $table1Record;
                if($table1Record->wasRecentlyCreated) $records++;
                //if($records == 10000) break;
            } catch (\Throwable $th) {
                //throw $th;
                return $th->getMessage();
            }
        }
        
        return $records;
    }

    public function studentRecords(Request $request)
    {
        $studentId = $request->student_id;
        $year = $request->year;
        $term = $request->term;
        $classId = $request->class_id;

        $data = [];
        $data['table2_records'] = Table2::join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('table2.*','subjects.abbr', 'subjects.title')
        ->where([
            ['student_id', $studentId],  
            ['year', $year],
            ['term', $term]            
        ])
        ->orderBy('subjects.abbr')
        ->get();

        $data['terms'] = Table1::join('terms', 'terms.id', 'table1.term')
        ->select('table1.term', 'terms.title')
        ->where([
            ['year', $year],
            ['class_id', $classId]
        ])
        ->distinct()
        ->get();    
        
        $data['student_subjects'] = $this->studentSubjectsAssigned($year, $term, $classId, $studentId);

        //$data['subject_weightings'] = $this->subjectWeightings ($year, $term, $studentId);

        return $data;
    }

    public function termRecords($year, $term)
    {
        $records = Table2::where([
            'year' => $year,
            'term' => $term
        ])->get();

        return $records;
    }

    public function studentSubjectsAssigned($year, $term, $classId, $id)
    {
        $data = [];

        $formClassRecord = FormClass::where('id', $classId)
        ->select('form_level')
        ->first();

        $form_level = $formClassRecord ? $formClassRecord->form_level : null;

        $academicTerm = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = $academicTerm ? $academicTerm->academic_year_id : null;

        $table2Records = Table2::join(
            'subjects', 
            'subjects.id', 
            'table2.subject_id'
        )
        ->select(
            'subject_id',
            'subjects.abbr', 
            'subjects.title'
        )
        ->where([
            ['year', $year],
            ['term', $term],
            ['student_id', $id]
        ])
        ->orderBy('abbr')
        ->get();        

        $teacherLessons = TeacherLesson::join(
            'subjects', 
            'subjects.id', 
            'teacher_lessons.subject_id'
        )        
        ->select(
            'subject_id', 
            'abbr', 
            'subjects.title'
        )
        ->where([
            ['form_class_id', $classId],
            ['academic_year_id', $academicYearId]
        ])
        ->distinct()
        ->orderBy('abbr')
        ->get(); 

        $studentSubjects = StudentSubject::join(
            'subjects', 
            'subjects.id', 
            'student_subjects.subject_id'
        )
        ->select(
            'subject_id', 
            'abbr', 
            'subjects.title'
        )
        ->where('student_id', $id)
        ->orderBy('abbr')
        ->get();
        
        if(sizeof($studentSubjects) == 0 && $form_level < 4){
            //return 'no subjects assigned';
            foreach($table2Records as $record){
                $record->entered = 1;
                array_push($data, $record);
            }

            foreach($teacherLessons as $record){
                $found = false;
                foreach($table2Records as $table2Record){
                    if($record->subject_id == $table2Record->subject_id){
                        $found = true;
                        break;
                    }
                }
                if(!$found){
                    $record->entered = 0;
                    array_push($data, $record);
                }                
            }
            return $data;
        }
        
        // return 'subjects assigned';
        foreach($table2Records as $record){
            $record->entered = 1;
            array_push($data, $record);
        }

        foreach($studentSubjects as $record){
            $found = false;
            foreach($table2Records as $table2Record){
                if($record->subject_id == $table2Record->subject_id){
                    $found = true;
                    break;
                }
            }
            if(!$found){
                $record->entered = 0;
                array_push($data, $record);
            } 
        }

        return $data;
        
    }

    public function subjectWeightings ($year, $term, $id)
    {
        $data = [];

        $table2Records = Table2::join('subjects', 'subjects.id', 'table2.subject_id')
        ->select('subject_id','subjects.abbr', 'subjects.title')
        ->where([
            ['year', $year],
            ['term', $term],
            ['student_id', $id]
        ])
        ->orderBy('abbr')
        ->get();
        
        foreach($table2Records as $record)
        {
            $subjectWeight = [];
            $subjectWeight['subject_id'] = $record->subject_id;
            $subjectWeight['subject'] = $record->title;
            $subjectWeight['abbr'] = $record->abbr;
            $subjectWeight['weightings'] = AssesmentWeighting::where('subject_id', $record->subject_id)
            ->get();
            array_push($data, $subjectWeight);
        }

        return $data;
    }

    public function update(Request $request)
    {        
        return Table2::where([
            ['student_id', $request->student_id],
            ['year', $request->year],
            ['term', $request->term],
            ['subject_id', $request->subject_id_old]
        ])->update([
            "subject_id" => $request->subject_id_new,
            "exam_mark" => $request->exam_mark,
            "course_mark" => $request->course_mark,
            "late" => $request->late,
            "absent" => $request->absent,
            "app" => $request->app,
            "con" => $request->con,
            "comment" => $request->comment,
        ]);
    }

    public function delete(Request $request)
    {
        return Table2::where([
            ['student_id', $request->student_id],
            ['year', $request->year],
            ['term', $request->term],
            ['subject_id', $request->subject_id]
        ])->delete();
    }
}
