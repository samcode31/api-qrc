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
use App\Models\Weighting;
use App\Models\StudentWeeklyTest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Table2Controller extends Controller
{

    public function show(Request $request)
    {        
        $formClassId = $request->classId;
        $year = $request->year;
        $term = $request->term;
        $subjectId = $request->subjectId;
        $employeeId = $request->employeeId;

        $table2Records = [];
        $data = [];
        $total = 0;
        $registered = 0;
        $entered = 0;
        $courseMarkMax = 100;

        $formClassRecord = FormClass::where('id', $formClassId)
        ->first();
        $className = null;
        $classGroup = null;
        if($formClassRecord)
        {
            $className = $formClassRecord->class_name;
            $classGroup = $formClassRecord->class_group;
        }
        
        $classTotal = Table1::where([
            ['class_name', $className],
            ['class_group', $classGroup]
        ])
        ->where('year', $year)
        ->where('term', $term)
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
        ->where([
            ['table1.class_name', $className],
            ['table1.class_group', $classGroup],
            ['year', $year],
            ['term', $term]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        $total = $classTotal->count();            
        foreach($studentsRegistered as $student)
        {
            $registered++;                
            $studentId = $student->student_id;
            // $studentMarkRecord = Table2::where([
            //     ['student_id', $studentId],
            //     ['year', $year],
            //     ['term', $term],
            //     ['subject_id', $subjectId]
            // ])->first();

            $studentMarkRecord = Table2::firstOrNew(
                [
                    'student_id' => $studentId,
                    'year' => $year,
                    'term' => $term,
                    'subject_id' => $subjectId
                ],
                [
                    'exam_mark' => null,
                    'course_mark' => null,
                    'employee_id' => null,
                    'course_mark_max' => $courseMarkMax,
                    'coded_comment' => null,
                    'coded_comment_1' => null
                ]
            );

            if($studentMarkRecord->exists){
                $entered++;
                $courseMarkMax = $studentMarkRecord->course_mark_max;
            }
            
           
            $studentPicture = Student::whereId($studentId)->first()->picture;
            $studentMarkRecord->first_name = $student->first_name;
            $studentMarkRecord->last_name = $student->last_name;
            $studentMarkRecord->picture = $studentPicture;
            array_push($data, $studentMarkRecord);                   
        }            
      
       
        // usort($data, array($this, "cmp"));
        $table2Records['data'] = $data;
        $table2Records['total'] = $total;
        $table2Records['registered'] = $registered;
        $table2Records['entered'] = $entered;
        return $table2Records;    
    }    

    public function cmp($a, $b)
    {
        return strcmp($a->last_name, $b->last_name);
    }

    public function store(Request $request)
    {       

        $table2Record =  Table2::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
                'subject_id' =>$request->subject_id,
            ],
            [
                'exam_mark' => $request->exam_mark,
                'course_mark' => $request->course_mark,
                'course_mark_max' => $request->course_mark_max,
                'employee_id' => $request->employee_id
            ]
        );

        $this->updateMonthlyTestWeighted($request->student_id, $request->year, $request->term);

        $this->updateTermTestWeighted($request->student_id, $request->year, $request->term);

        return $table2Record;

    }

    private function updateMonthlyTestWeighted ($studentId, $year, $term)
    {
        $monthlyTestWeightRecord = Weighting::where([
            ['category', 'Monthly Test'],
            ['year', '>=', $year],
            ['term', '>=', $term],
        ])
        ->first();

        $monthlyTestWeight = $monthlyTestWeightRecord ? $monthlyTestWeightRecord->weight : 0;

        $table2Records = Table2::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])
        ->get();


        $totalCourseMarks = 0;
        $totalCourseMarkMax = 0;

        foreach($table2Records as $table2Record)
        {
            $totalCourseMarks += $table2Record->course_mark;
            $totalCourseMarkMax += $table2Record->course_mark_max;
        }

        $monthlyTestWeighted = $totalCourseMarkMax == 0 ? null : ($totalCourseMarks/$totalCourseMarkMax)*$monthlyTestWeight;

        Table1::updateOrCreate(
            [
                'student_id' => $studentId,
                'year' => $year,
                'term' => $term
            ],
            [
                'monthly_test' => $monthlyTestWeighted
            ]
        );
    }

    private function updateTermTestWeighted ($studentId, $year, $term)
    {
        $termTestWeightRecord = Weighting::where([
            ['category', 'Term Test'],
            ['year', '>=', $year],
            ['term', '>=', $term],
        ])
        ->first();

        $termTestWeight = $termTestWeightRecord ? $termTestWeightRecord->weight : 0;

        $table2Records = Table2::where([
            ['student_id', $studentId],
            ['year', $year],
            ['term', $term]
        ])
        ->get();    

        $totalExamMarks = 0;
        $totalExamMarkMax = 0;

        foreach($table2Records as $table2Record)
        {
            $totalExamMarks += is_numeric($table2Record->exam_mark) ? $table2Record->exam_mark : 0;
            $totalExamMarkMax += 100;
        }

        $termTestWeighted = $totalExamMarks == 0 ? null : number_format(( $totalExamMarks/$totalExamMarkMax)*$termTestWeight, 0, '.', '');

        return Table1::updateOrCreate(
            [
                'student_id' => $studentId,
                'year' => $year,
                'term' => $term
            ],
            [
                'term_test' => $termTestWeighted
            ]
        );
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
        $className = $request->class_name;
        $classGroup = $request->class_group;

        $data = [];
        $data['table2_records'] = Table2::join(
            'subjects', 
            'subjects.id', 
            'table2.subject_id'
        )
        ->select(
            'table2.*',
            'subjects.abbr', 
            'subjects.title'
        )
        ->where([
            ['student_id', $studentId],  
            ['year', $year],
            ['term', $term]            
        ])
        ->orderBy('subjects.abbr')
        ->get();

        $data['terms'] = Table1::join(
            'terms', 
            'terms.id', 
            'table1.term'
        )
        ->select(
            'table1.term', 
            'terms.title'
        )
        ->where([
            ['year', $year],
            ['class_name', $className],
            ['class_group', $classGroup],
        ])
        ->distinct()
        ->get();    
        
        $data['student_subjects'] = $this->studentSubjectsAssigned($year, $term, $className, $classGroup, $studentId);

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

    public function studentSubjectsAssigned($year, $term, $className, $classGroup, $id)
    {
        $data = [];

        $formClassRecord = FormClass::where([
            ['class_name', $className],
            ['class_group', $classGroup],
        ])
        ->first();

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
            ['form_class_id', $formClassRecord->id],
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
        
        if(sizeof($studentSubjects) == 0){
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
        $data = array();

        $data['table2_record'] =  Table2::where([
            ['student_id', $request->student_id],
            ['year', $request->year],
            ['term', $request->term],
            ['subject_id', $request->subject_id_old]
        ])->update([
            "subject_id" => $request->subject_id_new,
            "exam_mark" => $request->exam_mark,
            "course_mark" => $request->course_mark,
            'employee_id' => $request->employee_id
        ]);

        $this->updateMonthlyTestWeighted($request->student_id, $request->year, $request->term);

        $data['table1_record'] = $this->updateTermTestWeighted($request->student_id, $request->year, $request->term);

        return $data;
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

    public function importAssesments(Request $request)
    {
        $data = [];
        $employeeId = $request->employee_id;
        $formClassId = $request->form_class_id;
        $subjectId = $request->subject_id;

        // validate the input data
        
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required',
            'form_class_id' => 'required',
            'subject_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $currentAcademicTermRecord = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = $currentAcademicTermRecord ? $currentAcademicTermRecord->academic_year_id : null;
        $year = $academicYearId ? substr($academicYearId, 0, 4) : null;
        $term = $currentAcademicTermRecord ? $currentAcademicTermRecord->term : null;
        
        $startDate = $currentAcademicTermRecord ? $currentAcademicTermRecord->start_date : null;
        $endDate = $currentAcademicTermRecord ? $currentAcademicTermRecord->end_date : null;

        $weeklyTestTotals = DB::table('students')
        ->join('student_weekly_tests', 'students.id', 'student_weekly_tests.student_id')
        ->select(
            'students.id as student_id',
            DB::raw('SUM(student_weekly_tests.mark) AS total_mark'),
            DB::raw('SUM(student_weekly_tests.max_mark) AS total_max_mark')
        )
        ->where([
            ['students.form_class_id', $formClassId],
            ['student_weekly_tests.employee_id', $employeeId],
            ['student_weekly_tests.week_end_date', '>=', $startDate],
            ['student_weekly_tests.week_end_date', '<=', $endDate],
            ['student_weekly_tests.subject_id', $subjectId]
        ])
        ->groupBy('students.id')
        ->get();

        foreach($weeklyTestTotals as $weeklyTestTotal)
        {
            $data[] = Table2::updateOrCreate(
                [
                    'student_id' => $weeklyTestTotal->student_id,
                    'year' => $year,
                    'term' => $term,
                    'subject_id' => $subjectId
                ],
                [
                    'course_mark' => $weeklyTestTotal->total_mark,
                    'course_mark_max' => $weeklyTestTotal->total_max_mark,
                    'employee_id' => $employeeId
                ]
            );
        }

        return $data;
    }
}
