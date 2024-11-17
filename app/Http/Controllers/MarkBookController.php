<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\AssesmentCourse;
use App\Models\AssesmentEmployeeAssignment;
use App\Models\Employee;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherLesson;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Table2;

class MarkBookController extends Controller
{
    public function show (Request $request)
    {
        $academicYearId = $request->academic_year_id;
        $term = $request->term;
        $subjectId = $request->subject_id;
        $formLevel = $request->form_level;
        $formClassId = $request->form_class_id;
        $employeeId = $request->employee_id;    

        $data = []; 
        
        $students = [];

        $defaultAssignments = 5;

        $academicTermRecord = AcademicTerm::where('is_current', 1)
        ->first();

        $currentAcademicTerm = $academicTermRecord ? $academicTermRecord->term : null;

        $currentAcademicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;
        
        $assesments = AssesmentEmployeeAssignment::where([
            ['academic_year_id', $academicYearId],
            ['term', $term],
            ['subject_id', $subjectId],
            ['form_class_id', $formClassId],
            ['employee_id', $employeeId]
        ])->get();

        if(sizeof($assesments) === 0){
            //no assesments exist for term create first one
            $assesments[0] = AssesmentEmployeeAssignment::firstOrCreate(
                [
                    'academic_year_id' => $academicYearId,
                    'term' => $term,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClassId,
                    'employee_id' => $employeeId,
                    'assesment_number' => 1
                ]
            );
        }
        else{
            foreach($assesments as $assesment){
                if($assesment->assesment_number > $defaultAssignments){
                    $defaultAssignments = $assesment->assesment_number;
                }
            }
        }

        $data['assesments'] = $assesments;

        if($formLevel < 4){
            if(
                $term == $currentAcademicTerm && 
                $academicYearId == $currentAcademicYearId
            ){
                $students = Student::where('class_id', $formClassId)
                ->select(
                    'id',
                    'first_name',
                    'last_name'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
            else{
                //previous term course marks
            }            
        }
        elseif($formLevel < 6){
            if(
                $term == $currentAcademicTerm && 
                $academicYearId == $currentAcademicYearId
            ){
                $students = Student::join(
                    'student_subjects',
                    'student_subjects.student_id',
                    'students.id'
                )
                ->where([
                    ['class_id', $formClassId],
                    ['subject_id', $subjectId]
                ])
                ->where(function($query) use ($employeeId) {
                    return $query->whereNull('employee_id');
                                // ->orWhere('employee_id', $employeeId);
                })
                ->select(
                    'students.id',
                    'first_name',
                    'last_name'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
            else{
                //previous term course marks
            }        
        }
        else{
            if(
                $term == $currentAcademicTerm && 
                $academicYearId == $currentAcademicYearId
            ){
                $students = Student::join(
                    'student_subjects',
                    'student_subjects.student_id',
                    'students.id'
                )
                ->where([
                    ['class_id', 'like', $formClassId.'%'],
                    ['subject_id', $subjectId]
                ])
                ->where(function($query) use ($employeeId) {
                    return $query->whereNull('employee_id')
                                ->orWhere('employee_id', $employeeId);
                })
                ->select(
                    'students.id',
                    'first_name',
                    'last_name'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
            else{
                //previous term course marks
            }     
        }

        foreach($students as $student){
            AssesmentCourse::firstOrCreate(
                [
                    'student_id' => $student->id,
                    'assesment_employee_assignment_id' => $assesments[0]->id,
                ],
            );

            $marks = [];

            for($i = 1; $i <=$defaultAssignments; $i++){

                $courseAssesment = AssesmentCourse::join(
                    'assesment_employee_assignments',
                    'assesment_employee_assignments.id',
                    'assesment_course.assesment_employee_assignment_id'
                )
                ->where([
                    ['student_id', $student->id],
                    ['form_class_id', $formClassId],
                    ['academic_year_id', $academicYearId],
                    ['term', $term],
                    ['subject_id', $subjectId],
                    ['employee_id', $employeeId],
                    ['assesment_number', $i]
                ])
                ->first();

                $marks[$i] = $courseAssesment ? $courseAssesment->mark : null;
                
            }

            $student->marks = $marks;
        }
        
        $data['students'] = $students;

        return $data;

    }

    public function store (Request $request)
    {
        $assesment = AssesmentEmployeeAssignment::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'academic_year_id' => $request->academic_year_id,
                'term' => $request->term,
                'subject_id' => $request->subject_id,
                'assesment_number' => $request->assesment_number,
                'form_class_id' => $request->form_class_id,
            ],
            [
                'date' => $request->date,
                'topic'=> $request->topic,
                'total' => $request->total,
                'weighting' => $request->weighting
            ]
        );

        $assementEmployeeAssignmentId = $assesment->id;

        $assesmentCourse = AssesmentCourse::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'assesment_employee_assignment_id' => $assementEmployeeAssignmentId
            ],
            [
                'mark' => $request->mark,                
            ]
        );

        $this->setCourseMark(
            $request->student_id, 
            $request->academic_year_id, 
            $request->term, 
            $request->subject_id, 
            $request->employee_id , 
            $request->form_class_id
        );

        return $assesmentCourse;
    }

    private function setCourseMark(
        $studentId, 
        $academicYearId, 
        $term, 
        $subjectId, 
        $employeeId, 
        $formClassId
    )
    {
        $data = array();
        $courseMark = 0;
        $courseMarkTotal = 0;

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

            $courseMark += $assesmentCourseRecord ? $assesmentCourseRecord->mark : 0;
            
        }

        $courseMark = $courseMark ?  number_format(($courseMark/$courseMarkTotal)*100, 0) : null;

        Table2::updateOrCreate(
            [
                'student_id' => $studentId,
                'year' => substr($academicYearId, 0, 4),
                'term' => $term,
                'subject_id' => $subjectId,
            ],
            [
                'course_mark' => $courseMark
            ]
        );
         
    }

    public function storeAssesment (Request $request)
    {
        return AssesmentEmployeeAssignment::updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'academic_year_id' => $request->academic_year_id,
                'term' => $request->term,
                'subject_id' => $request->subject_id,
                'assesment_number' => $request->assesment_number,
                'form_class_id' => $request->form_class_id,
            ],
            [
                'date' => $request->date,
                'topic' => $request->topic,
                'total' => $request->total,
                'weighting' => $request->weighting
            ]
        );
    }

    public function download (Request $request)
    {
        $year = $request->year;
        $term = $request->term;
        $formClassId = $request->form_class_id;
        $subjectId = $request->subject_id;
        $employeeId = $request->employee_id;
        date_default_timezone_set('America/Caracas'); 
        $data = $this->data($year, $term, $formClassId, $subjectId, $employeeId);
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        $subjectRecord = Subject::where('id', $subjectId)
        ->first();
        $subject = $subjectRecord ? $subjectRecord->title : null;
        
        $employeeRecord = Employee::where('id', $employeeId)
        ->first();
        $employee = $employeeRecord ? $employeeRecord->first_name[0].'. '.$employeeRecord->last_name : null;

        $formClassRecord = FormClass::where('id', $formClassId)
        ->first();
        $formLevel = $formClassRecord ? $formClassRecord->form_level : null;

        $termRecord = Term::where('id', $term)
        ->first();

        $termTitle = $termRecord ? $termRecord->title : null;
        
        $sheet->setCellValue(
            'A1',$formClassId.' '.$subject." \n".substr($year, 0, 4).'-'.substr($year, 4). ' '.$termTitle
        );

        $sheet->getStyle('A1')->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(16);
        $sheet->getRowDimension('1')->setRowHeight(30);
        $sheet->getStyle('A1')->getAlignment()->setWrapText(true);       

        // $arrayDataHeaders = $this->dataHeaders($year, $term, $formClassId, $subjectId, $employeeId);
        $arrayDataHeaders = $this->spreadsheetHeaders($year, $term, $formLevel, $formClassId, $subjectId);
        
        $sheet->fromArray($arrayDataHeaders, NULL, 'A2');
        $sheet->fromArray($data, NULL, 'A6');

        $this->spreadsheetStyle($sheet);

        // $sheet->getColumnDimension('A')->setWidth(12);
        // $sheet->getColumnDimension('B')->setAutoSize(true);
        // $sheet->getColumnDimension('C')->setAutoSize(true);
        // $sheet->getColumnDimension('D')->setAutoSize(true);
        // $sheet->getColumnDimension('E')->setAutoSize(true);
        // $sheet->getColumnDimension('F')->setAutoSize(true);
        // $sheet->getColumnDimension('D')->setWidth(12);
        // $sheet->getColumnDimension('E')->setWidth(12);
        // $sheet->getColumnDimension('F')->setWidth(12);
        // $sheet->getColumnDimension('G')->setWidth(12);
        // $sheet->getColumnDimension('H')->setWidth(12);
        // $sheet->getColumnDimension('I')->setWidth(12);
        // $sheet->getColumnDimension('J')->setWidth(12);

        // $highestColumn = $sheet->getHighestColumn();
        // $sheet->mergeCells('A1:'.$highestColumn.'1');
        
        // $sheet->mergeCells('A2:A4');
        // $sheet->getStyle('A2:A4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->mergeCells('B2:B4');
        // $sheet->getStyle('B2:B4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->mergeCells('C2:C4');
        // $sheet->getStyle('C2:C4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // // $sheet->mergeCells('D2:D4');
        // $sheet->getStyle('D2:D4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // // $sheet->mergeCells('E2:E4');
        // $sheet->getStyle('E2:E4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // // $sheet->mergeCells('F2:F4');
        // $sheet->getStyle('F2:F4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->getStyle('G2:G4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->getStyle('H2:H4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->mergeCells('I2:I3');
        // $sheet->getStyle('I2:I3')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // // $sheet->mergeCells('J2:J3');
        // $sheet->getStyle('J2:J3')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
        // $sheet->getStyle('J2:J4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->getStyle('K2:K4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->mergeCells('L2:L4');
        // $sheet->getStyle('L2:L4')->getAlignment()
        // ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        // $sheet->getStyle('G3:K3')->getNumberFormat()
        // ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DMYSLASH);

        // $styleArray = [
        //     'borders'=> [
        //         'outline' => [
        //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        //             'color' => [
        //                 'argb' => 'FF808080'
        //             ]
        //         ],
        //         'inside' => [
        //             'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        //         ],
                
        //     ]
        // ];

        // $sheet->getStyle('A2:I4')->applyFromArray($styleArray);    

        // $sheet->getStyle('A2:J4')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        // ->getStartColor()->setARGB('FFD4D4D4');

        // $sheet->getStyle('J2:J3')->getBorders()
        // ->getOutline()
        // ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // $sheet->getStyle('J4')->getBorders()
        // ->getOutline()
        // ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);

        // $sheet->getStyle('J3')->getFont()->setItalic(true);
        // $sheet->getStyle('J3')->getFont()->setSize(9);

        // $highestRow = $sheet->getHighestRow();

        // $sheet->getStyle('J5:J'.$highestRow)->getNumberFormat()
        // ->setFormatCode('0.0');

        // $sheet->getStyle('A2:A'.$highestRow)->getAlignment()
        // ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // $sheet->getStyle('D2:J'.$highestRow)->getAlignment()
        // ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // $sheet->freezePane('A5', 'A5');
        $sheet->setTitle($formClassId);
        $sheet->setSelectedCell('D6');

        $file = $formClassId." Course Marks ".date('Ymdhis').".xlsx";
        $filePath = storage_path('app/public/'.$file);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

    private function data ($academicYearId, $term, $formClassId, $subjectId, $employeeId)
    {
        $data = []; $assesmentIds = []; $defaultAssesments = 5;
        $employeeAssesments = AssesmentEmployeeAssignment::where([
            ['academic_year_id', $academicYearId],
            ['term', $term],
            ['form_class_id', $formClassId],
            ['subject_id', $subjectId],
            ['employee_id', $employeeId]
        ])->get();

        foreach($employeeAssesments as $assesment){
            array_push($assesmentIds, $assesment->id);
            if($assesment->assesment_number > $defaultAssesments){
                $defaultAssesments = $assesment->assesment_number;
            }
        }

        // return $defaultAssesments;

        $students = AssesmentCourse::join(
            'students',
            'students.id',
            'assesment_course.student_id'
        )
        ->select(
            'students.id',
            'students.first_name',
            'students.last_name'
        )
        ->whereIn(
            'assesment_employee_assignment_id',
            $assesmentIds
        )
        ->distinct()
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        foreach($students as $student){
            $courseTotal = 0; $assesmentTotal = 0; $record = [];

            array_push(
                $record, 
                $student->id,
                $student->first_name,
                $student->last_name,
                // $formClassId,
                // substr($academicYearId, 0, 4).'-'.substr($academicYearId, 4),
                // $term,                
            );

            for($i = 1; $i <= $defaultAssesments; $i++){
                $courseMark = AssesmentCourse::join(
                    'assesment_employee_assignments',
                    'assesment_employee_assignments.id',
                    'assesment_course.assesment_employee_assignment_id'
                )
                ->select(
                    'mark',
                    'total',
                )
                ->where([
                    ['student_id', $student->id],
                    ['academic_year_id', $academicYearId],
                    ['term', $term],
                    ['form_class_id', $formClassId],
                    ['subject_id', $subjectId],
                    ['employee_id', $employeeId],
                    ['assesment_number', $i]
                ])->first();

                $mark = null;
                
                if($courseMark){
                    $mark = $courseMark->mark;
                    $courseTotal += $courseMark->mark;
                    $assesmentTotal += $courseMark->total;
                }
                
                array_push($record, $mark);
               
            }
            $totalPercentage = null;

            if($assesmentTotal){
                $totalPercentage = ($courseTotal / $assesmentTotal) * 100;
            }
            array_push($record, $courseTotal);
            array_push($record, $totalPercentage);
            array_push($data, $record);
        }

        return $data;
    }

    public function spreadsheet ($year = null, $term = null, $formLevel = null, $formClassId = null, $subjectId = null)
    {
        date_default_timezone_set('America/Caracas');

        $spreadsheet = new Spreadsheet();

        $termRecord = Term::where('id', $term)
        ->first();
        $termTitle = $termRecord ? $termRecord->title : $term;

        $subjectRecord = Subject::where('id', $subjectId)
        ->first();

        $subjectTitle = $subjectRecord ? $subjectRecord->title : null;
        

        $formClasses[0] = $formClassId;
        
        if(
            ($formLevel && !$formClassId && $subjectId) ||
            ($formLevel && !$formClassId && !$subjectId)
        ){
            $formClasses = [];
            $formClassRecords = FormClass::where('form_level', $formLevel)
            ->get();
            
            foreach($formClassRecords as $record){
                array_push($formClasses, $record->id);
            }
        }

       

        foreach($formClasses as $index=>$formClass){
            if($index != 0){
                $spreadsheet->createSheet();
            }            
            // $sheet = $spreadsheet->getActiveSheet();
            $sheet = $spreadsheet->getSheet($index);
            $dataHeaders = $this->spreadsheetHeaders($year, $term, $formLevel, $formClass, $subjectId);
            $data = $this->spreadsheetData($year, $term, $formLevel, $formClass, $subjectId);
            // return $data;
            $sheet->setCellValue(
                "A1",$formClass." Course Assesments \n".substr($year, 0, 4).'-'.substr($year, 4). " ".$termTitle
            );

            if($subjectId){
                $sheet->setCellValue(
                    "A1",$subjectTitle." Course Assesments \n".substr($year, 0, 4).'-'.substr($year, 4). " ".$termTitle
                );
            }
            
            $sheet->getStyle('A1')->getAlignment()->setWrapText(true);

            $sheet->fromArray($dataHeaders, NULL, 'A2');
            $sheet->fromArray($data, NULL, 'A6');

            $this->spreadsheetStyle($sheet);

            $sheet->setTitle($formClass);
            $sheet->setSelectedCell('D6');
        }
        

        $file = $formClass." Course Assesments ".date('Ymdhis').".xlsx";
        $filePath = storage_path('app/public/'.$file);
        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

    private function spreadsheetData ($year, $term, $formLevel, $formClass, $subjectId)
    {
        $data = []; $students = []; $defaultAssesments = 5;
        $academicTermRecord = AcademicTerm::where('is_current', 1)
        ->first();
        $currentTerm = $academicTermRecord ? $academicTermRecord->term : null;
        $currentYear = $academicTermRecord ? $academicTermRecord->academic_year_id : null;

        

        if($formClass && !$subjectId){
            $students = AssesmentCourse::join(
                'assesment_employee_assignments',
                'assesment_employee_assignments.id',
                'assesment_course.assesment_employee_assignment_id'
            )
            ->join(
                'students',
                'students.id',
                'assesment_course.student_id'
            )
            ->select(
                'students.id',
                'students.first_name',
                'students.last_name'
            )
            ->where([
                ['academic_year_id', $year],
                ['term', $term],
                ['form_class_id', $formClass]
            ])
            ->distinct()
            ->get();

            if(
                sizeof($students) == 0 && 
                $term == $currentTerm &&
                $year == $currentYear
            ){
                $students = Student::where('class_id', $formClass)
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'class_id'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
        }
        elseif($formClass && $subjectId && $formLevel < 4){
            $students = AssesmentCourse::join(
                'assesment_employee_assignments',
                'assesment_employee_assignments.id',
                'assesment_course.assesment_employee_assignment_id'
            )
            ->join(
                'students',
                'students.id',
                'assesment_course.student_id'
            )
            ->select(
                'students.id',
                'students.first_name',
                'students.last_name'
            )
            ->where([
                ['academic_year_id', $year],
                ['term', $term],
                ['subject_id', $subjectId],
                ['form_class_id', $formClass]
            ])
            ->distinct()
            ->get();

            if(
                sizeof($students) == 0 && 
                $term == $currentTerm &&
                $year == $currentYear
            ){
                $students = Student::where([
                    ['class_id', $formClass]
                ])
                ->select(
                    'students.id',
                    'first_name',
                    'last_name',
                    'class_id'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
        }
        elseif($formClass && $subjectId && $formLevel > 3){
            $students = AssesmentCourse::join(
                'assesment_employee_assignments',
                'assesment_employee_assignments.id',
                'assesment_course.assesment_employee_assignment_id'
            )
            ->join(
                'students',
                'students.id',
                'assesment_course.student_id'
            )
            ->select(
                'students.id',
                'students.first_name',
                'students.last_name'
            )
            ->where([
                ['academic_year_id', $year],
                ['term', $term],
                ['subject_id', $subjectId],
                ['form_class_id', $formClass]
            ])
            ->distinct()
            ->get();

            if(
                sizeof($students) == 0 && 
                $term == $currentTerm &&
                $year == $currentYear
            ){
                $students = Student::join(
                    'student_subjects',
                    'student_subjects.student_id',
                    'students.id'
                )
                ->where([
                    ['class_id', $formClass]
                ])
                ->select(
                    'students.id',
                    'first_name',
                    'last_name',
                    'class_id'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
        }
        elseif($formClass && $subjectId && $formLevel > 3){
            $students = AssesmentCourse::join(
                'assesment_employee_assignments',
                'assesment_employee_assignments.id',
                'assesment_course.assesment_employee_assignment_id'
            )
            ->join(
                'students',
                'students.id',
                'assesment_course.student_id'
            )
            ->select(
                'students.id',
                'students.first_name',
                'students.last_name'
            )
            ->where([
                ['academic_year_id', $year],
                ['term', $term],
                ['subject_id', $subjectId],
                ['form_class_id', $formClass]
            ])
            ->distinct()
            ->get();

            if(
                sizeof($students) == 0 && 
                $term == $currentTerm &&
                $year == $currentYear
            ){
                $students = Student::where([
                    ['class_id', $formClass],
                    ['subject_id', $subjectId]
                ])
                ->select(
                    'id',
                    'first_name',
                    'last_name',
                    'class_id'
                )
                ->orderBy('last_name')
                ->orderBy('first_name')
                ->get();
            }
        }
        
        $subjects = TeacherLesson::where([
            ['academic_year_id', $year],
            ['form_class_id', $formClass]
        ])
        ->join(
            'subjects',
            'subjects.id',
            'teacher_lessons.subject_id'
        )
        ->select(
            'employee_id',
            'subject_id',
            'form_class_id',
            'title'
        )
        ->orderBy('title')
        ->get();
            
        // $subjects = DB::table('teacher_lessons')->where([
        //     ['academic_year_id', $year],
        //     ['form_class_id', $formClass]
        // ])
        // ->join(
        //     'subjects',
        //     'subjects.id',
        //     'teacher_lessons.subject_id'
        // )
        // ->select(
        //     'employee_id',
        //     'subject_id',
        //     'form_class_id',
        //     'title'
        // )
        // ->orderBy('title')
        // ->get();

        if($subjectId){
            $subjects = TeacherLesson::where([
                ['academic_year_id', $year],
                ['form_class_id', $formClass],
                ['subject_id', $subjectId]
            ])
            ->join(
                'subjects',
                'subjects.id',
                'teacher_lessons.subject_id'
            )
            ->select(
                'employee_id',
                'subject_id',
                'form_class_id',
                'title'
            )
            ->orderBy('title')
            ->get();
        }

        foreach($students as $student){
            $record = [];
            array_push(
                $record, 
                $student->id, 
                $student->first_name, 
                $student->last_name
            );

            foreach($subjects as $subject){
                $courseTotal = 0; $assesmentTotal = 0;
                $assessmentsCount = $defaultAssesments;
                $employeeAssesments = AssesmentEmployeeAssignment::where([
                    ['academic_year_id', $year],
                    ['term', $term],
                    ['form_class_id', $formClass],
                    ['subject_id', $subject->subject_id],
                    ['employee_id', $subject->employee_id]
                ])->get();
        
                foreach($employeeAssesments as $assesment){
                    if($assesment->assesment_number > $defaultAssesments){
                        $assessmentsCount = $assesment->assesment_number;
                    }
                }

                for($i = 1; $i <= $assessmentsCount; $i++){
                    $courseMark = AssesmentCourse::join(
                        'assesment_employee_assignments',
                        'assesment_employee_assignments.id',
                        'assesment_course.assesment_employee_assignment_id'
                    )
                    ->select(
                        'mark',
                        'total',
                    )
                    ->where([
                        ['student_id', $student->id],
                        ['academic_year_id', $year],
                        ['term', $term],
                        ['form_class_id', $formClass],
                        ['subject_id', $subject->subject_id],
                        ['employee_id', $subject->employee_id],
                        ['assesment_number', $i]
                    ])->first();

                    $mark = null;
                    
                    if($courseMark){
                        $mark = $courseMark->mark;
                        $courseTotal += $courseMark->mark;
                        $assesmentTotal += $courseMark->total;
                    }

                    array_push($record, $mark);
                }
                $average = $assesmentTotal ? ($courseTotal/$assesmentTotal)*100 : null;
                $courseTotal = $courseTotal ? $courseTotal : null;
                array_push($record, $courseTotal, $average);

            }

            array_push($data, $record);
        }
        
        return $data;
    }

    private function spreadsheetStyle ($sheet)
    {
        $markColStart = 4;
        
        $sheet->freezePane('D6');
        
        $highestRow = $sheet->getHighestRow();

        $sheet->getStyle('A1')->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->getStyle('A1')->getFont()->setBold(true);
        $sheet->getStyle('A1')->getFont()->setSize(16);
        $sheet->getRowDimension('1')->setRowHeight(40); 

        $sheet->getColumnDimension('A')->setWidth(12);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);

        foreach($sheet->getColumnIterator() as $col){
            // $sheet->getColumnDimension($col->getColumnIndex())->setWidth(12);
            if(
                \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($col->getColumnIndex()) > 3
            ){
                $sheet->getColumnDimension($col->getColumnIndex())->setWidth(12);
                $sheet->getStyle($col->getColumnIndex().'3:'.$col->getColumnIndex().'5')->getAlignment()
                ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);
            }
        }

        $highestColumn = $sheet->getHighestColumn();
        $sheet->mergeCells('A1:'.$highestColumn.'1');

        $sheet->mergeCells('A2:A5');
        $sheet->getStyle('A2:A5')->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('B2:B5');
        $sheet->getStyle('B2:B5')->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $sheet->mergeCells('C2:C5');
        $sheet->getStyle('C2:C5')->getAlignment()
        ->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER);

        $styleArray = [
            'borders'=> [
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => [
                        'argb' => 'FF808080'
                    ]
                ],
                'inside' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                ],
                
            ]
        ];

        $sheet->getStyle('A2:'.$highestColumn.'5')->applyFromArray($styleArray);

        $sheet->getStyle('A2:'.$highestColumn.'5')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
        ->getStartColor()->setARGB('FFD4D4D4');

        $sheet->getStyle('A2:A'.$highestRow)->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $sheet->getStyle('D2:'.$highestColumn.$highestRow)->getAlignment()
        ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);
        
        $mergeStart = $sheet->getCell([$markColStart, 2])->getCoordinate(); 
        $mergeEnd = null;

        if($highestColumnIndex > $markColStart){
            for($row = 2; $row <= $highestRow; ++$row){           

                for($col = $markColStart; $col <= $highestColumnIndex; ++$col){
                    $value = $sheet->getCell([$col, $row])->getValue();
                    
                    if($value == "(Unweighted)"){
                        $cellAddress = $sheet->getCell([$col, $row])
                        ->getCoordinate();
                        $sheet->getStyle($cellAddress)->getFont()->setItalic(true);
                        $sheet->getStyle($cellAddress)->getFont()->setSize(9);
                    }
    
                    if($row == 2){
                        if($col != $markColStart && !$value){
                            $mergeEnd = $sheet->getCell([$col, $row])
                            ->getCoordinate();
                        }
                        elseif($col != $markColStart && $value){
                            $sheet->mergeCells($mergeStart.':'.$mergeEnd);
                            // $sheet->setCellValueByColumnAndRow($col, 10, $mergeStart.':'.$mergeEnd);
                            $mergeStart = $sheet->getCell([$col, $row])
                            ->getCoordinate();
                        }                  
                    }
    
                    if($value == "AVERAGE"){
                        $startCell = $sheet->getCell([$col,6])
                        ->getCoordinate();
                        $endCell = $sheet->getCell([$col,$highestRow])
                        ->getCoordinate();
                        $sheet->getStyle($startCell.':'.$endCell)->getNumberFormat()
                        ->setFormatCode('0.0');
    
                        $sheet->getStyle($sheet->getCell([$col,$row])->getCoordinate())
                        ->getBorders()->getBottom()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
    
                        $sheet->getStyle($sheet->getCell([$col,$row+1])->getCoordinate())
                        ->getBorders()->getTop()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
    
                        $startCell = $sheet->getCell([$col,2])
                        ->getCoordinate();
                        $sheet->getStyle($startCell.':'.$endCell)->getBorders()
                        ->getRight()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK);
    
                    }
                }
                $sheet->mergeCells($mergeStart.':'.$highestColumn.'2');
            }
        }

    }

    private function spreadsheetHeaders ($year, $term, $formLevel, $formClass, $subjectId) 
    {
        $data = []; $row1 = []; $row2 = []; $row3 = []; $row4 = [];
        $defaultAssesments = 5;
        
        $subjects = TeacherLesson::where([
            ['academic_year_id', $year],
            ['form_class_id', $formClass]
        ])
        ->join(
            'subjects',
            'subjects.id',
            'teacher_lessons.subject_id'
        )
        ->join(
            'employees',
            'employees.id',
            'teacher_lessons.employee_id'
        )
        ->select(
            'employee_id',
            'first_name',
            'last_name',
            'subject_id',
            'form_class_id',
            'title'
        )
        ->orderBy('title')
        ->get();

        if($subjectId){
            $subjects = TeacherLesson::where([
                ['academic_year_id', $year],
                ['form_class_id', $formClass],
                ['subject_id', $subjectId]
            ])
            ->join(
                'subjects',
                'subjects.id',
                'teacher_lessons.subject_id'
            )
            ->join(
                'employees',
                'employees.id',
                'teacher_lessons.employee_id'
            )
            ->select(
                'employee_id',
                'first_name',
                'last_name',
                'subject_id',
                'form_class_id',
                'title'
            )
            ->get();
        }

        array_push($row1, 'Student ID', 'First Name', 'Last Name');

        array_push($row2, null, null, null);
        array_push($row3, null, null, null);
        array_push($row4, null, null, null);

        foreach($subjects as $subject){
            $assesmentMaxTotal = 0; 
            $courseAssesmentRecords = AssesmentEmployeeAssignment::where([
                ['academic_year_id', $year],
                ['term', $term],
                ['subject_id', $subject->subject_id],
                ['form_class_id', $formClass]
            ])
            ->get();

            $courseAssesments = sizeof($courseAssesmentRecords);
            
            foreach($courseAssesmentRecords as $record){
                if($record->assesment_number > $defaultAssesments){
                    $defaultAssesments = $record->assesment_number;
                }
            }
            
            array_push($row1, $subject->title.' - '.$subject->first_name[0].'. '.$subject->last_name);
            
            if($courseAssesments < $defaultAssesments){
                $courseAssesments = $defaultAssesments;
            }

            for($i = 1; $i <= $courseAssesments; $i++){
                if($i != $courseAssesments){
                    array_push($row1, null);
                }
                
                $courseAssesment = AssesmentEmployeeAssignment::where([
                    ['academic_year_id', $year],
                    ['term', $term],
                    ['subject_id', $subject->subject_id],
                    ['form_class_id', $formClass],
                    ['assesment_number', $i]
                ])->first();

                $assesmentTopic = 'CW #'.$i;
                $assesmentDate = null;
                $assesmentMax = null;

                if($courseAssesment){
                    $assesmentTopic = $courseAssesment->topic ? $courseAssesment->topic : 'CW #'.$i;
                    $assesmentDate = $courseAssesment->date;
                    $assesmentMax = $courseAssesment->total;
                    if($assesmentMax){
                        $assesmentMaxTotal += $assesmentMax;
                    }
                }

                array_push($row2, $assesmentTopic);
                array_push($row3, $assesmentDate);
                array_push($row4, $assesmentMax);
            }

            array_push($row1, null, null);
            array_push($row2, 'TOTAL', 'AVERAGE');
            array_push($row3, null, '(Unweighted)');
            array_push($row4, $assesmentMaxTotal, '100%');
        }

        array_push($data, $row1, $row2, $row3, $row4);

        return $data;
    }

    public function deleteCourseAssesment (Request $request)
    {
        $deleted = -1;
        
        $assesment = AssesmentEmployeeAssignment::where('id', $request->id)
        ->first();

        if($assesment){
            $deletedAssesmentNumber = $assesment->assesment_number;
            $deleted = $assesment->delete();
            $allAssesments = AssesmentEmployeeAssignment::where([
                ['employee_id', $assesment->employee_id],
                ['academic_year_id', $assesment->academic_year_id],
                ['term', $assesment->term],
                ['subject_id', $assesment->subject_id],
                ['form_class_id', $assesment->form_class_id]
            ])->orderBy('assesment_number')
            ->get();

            foreach($allAssesments as $record){
                $currentAssesmentNumber = $record->assesment_number;
                if( $deletedAssesmentNumber && ($currentAssesmentNumber > $deletedAssesmentNumber)){
                    $record->assesment_number = $deletedAssesmentNumber;
                    $record->save();
                    $deletedAssesmentNumber = $currentAssesmentNumber;
                }
            }
        }
        // return AssesmentEmployeeAssignment::where('id', $request->id)
        // ->delete();
        return $deleted;
    }
}
