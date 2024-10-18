<?php

namespace App\Http\Controllers;

use App\Models\TeacherLesson;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class TeacherLessonController extends Controller
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

    public function upload()
    {
        $file = './files/teacher_lessons.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        //return $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,2)->getValue();
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $classId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(28,2)->getValue();
        //return $rows;
        $records = 0;
        for($i = 2; $i < $rows; $i++){
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
            $formClass = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            $subjectId = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();            
            $lesson = TeacherLesson::updateOrCreate(
                [
                    'employee_id' => $id,
                    'academic_year_id' => 20202021,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClass
                ],
                [
                    'employee_id' => $id,
                    'academic_year_id' => 20202021,
                    'subject_id' => $subjectId,
                    'form_class_id' => $formClass,                    
                ]
            );
            if($lesson->exists) $records++;
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $records;
    }
   
}
