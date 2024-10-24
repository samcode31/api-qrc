<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AcademicTerm;
use App\Models\AssesmentCourse;
use App\Models\AssesmentEmployeeAssignment;
use App\Models\FormClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\TeacherLesson;
use App\Models\Term;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarkBookSpreadsheetController extends Controller
{
    public function spreadsheet ($year = null, $term = null, $formLevel = null, $formClassId = null, $subjectId = null)
    {
        date_default_timezone_set('America/Caracas');

        $response = new StreamedResponse(function () use($year, $term, $formLevel, $formClassId, $subjectId) {
            $complete = false;
            $spreadsheet = null; $formClasses = [];
            $subjectTitle = null; $termTitle = null;
            $progress = 0;

            $spreadsheet = new Spreadsheet();
            //get term name
            $termRecord = Term::where('id', $term)
            ->first();
            $termTitle = $termRecord ? $termRecord->title : $term;

            //get subject name if subject parameter exists
            $subjectRecord = Subject::where('id', $subjectId)
            ->first();
            $subjectTitle = $subjectRecord ? $subjectRecord->title : null;
                    
            //if class parameter exists place into an array
            $formClasses[0] = $formClassId;
        
            //if formLevel exists alone or formLevel and subjectId get all classes of level
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
            $progress += 10;
            $total = sizeof($formClasses)*10 + 30;
            $data["complete"] = $complete;
            $data["progress"] = $progress;
            $data["message"] = "Creating Spreadsheet";
            $data["total"] = $total;
            echo "data: ".json_encode($data)."\n\n";
            ob_flush();
            flush();
            sleep(1);
                    
            foreach($formClasses as $index=>$formClass){
                if($index != 0){
                    $spreadsheet->createSheet();
                }            

                $sheet = $spreadsheet->getSheet($index);
                $dataHeaders = $this->spreadsheetHeaders($year, $term, $formLevel, $formClass, $subjectId);
                $data = $this->spreadsheetData($year, $term, $formLevel, $formClass, $subjectId);

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
                
                $progress += 10;
                $data["complete"] = $complete;
                $data["progress"] = $progress;
                $data["message"] = "Sheet ".($index + 1)." created";
                $data["total"] = $total;
                echo "data: ".json_encode($data)."\n\n";
                ob_flush();
                flush();
            }
            
            $progress += 10;
            $data["complete"] = $complete;
            $data["progress"] = $progress;
            $data["message"] = "Workbook Complete";
            $data["total"] = $total;
            echo "data: ".json_encode($data)."\n\n";
            ob_flush();
            flush();
            sleep(1);
                    
            $file = $formClassId." Course Assesments ".date('Ymdhis').".xlsx";
            $filePath = storage_path('app/public/'.$file);
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);
            $complete = true;
            $progress += 10;
            $data["complete"] = $complete;
            $data["progress"] = $progress;
            $data["message"] = "File Created";
            $data["total"] = $total;
            $data["filePath"] = $filePath;
            // return response()->download($filePath, $file);
            echo "data: ".json_encode($data)."\n\n";
            ob_flush();
            flush();
            sleep(1);
            
        });

        $response->headers->set('Content-type', 'text/event-stream');
        $response->headers->set('X-Accel-Buffering', 'no');
        $response->headers->set('Cache-Control', 'no-cache');
        
        return $response;
        
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
        
        $mergeStart = $sheet->getCellByColumnAndRow($markColStart, 2)->getCoordinate(); 
        $mergeEnd = null;

        if($highestColumnIndex > $markColStart){
            for($row = 2; $row <= $highestRow; ++$row){           

                for($col = $markColStart; $col <= $highestColumnIndex; ++$col){
                    $value = $sheet->getCellByColumnAndRow($col, $row)->getValue();
                    
                    if($value == "(Unweighted)"){
                        $cellAddress = $sheet->getCellByColumnAndRow($col, $row)
                        ->getCoordinate();
                        $sheet->getStyle($cellAddress)->getFont()->setItalic(true);
                        $sheet->getStyle($cellAddress)->getFont()->setSize(9);
                    }
    
                    if($row == 2){
                        if($col != $markColStart && !$value){
                            $mergeEnd = $sheet->getCellByColumnAndRow($col, $row)
                            ->getCoordinate();
                        }
                        elseif($col != $markColStart && $value){
                            $sheet->mergeCells($mergeStart.':'.$mergeEnd);
                            // $sheet->setCellValueByColumnAndRow($col, 10, $mergeStart.':'.$mergeEnd);
                            $mergeStart = $sheet->getCellByColumnAndRow($col, $row)
                            ->getCoordinate();
                        }                  
                    }
    
                    if($value == "AVERAGE"){
                        $startCell = $sheet->getCellByColumnAndRow($col,6)
                        ->getCoordinate();
                        $endCell = $sheet->getCellByColumnAndRow($col,$highestRow)
                        ->getCoordinate();
                        $sheet->getStyle($startCell.':'.$endCell)->getNumberFormat()
                        ->setFormatCode('0.0');
    
                        $sheet->getStyle($sheet->getCellByColumnAndRow($col,$row)->getCoordinate())
                        ->getBorders()->getBottom()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
    
                        $sheet->getStyle($sheet->getCellByColumnAndRow($col,$row+1)->getCoordinate())
                        ->getBorders()->getTop()
                        ->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_NONE);
    
                        $startCell = $sheet->getCellByColumnAndRow($col,2)
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
    
    
    public function download (Request $request)
    {
        $filePath =  $request->input('filePath');
        $fileName = $request->input('fileName');
        return response()->download($filePath, $fileName);
    }
}
