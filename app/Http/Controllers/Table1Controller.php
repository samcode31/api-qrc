<?php

namespace App\Http\Controllers;
ini_set('max_execution_time', '900');
use App\Http\Resources\Table1 as ResourcesTable1;
use App\Models\AcademicTerm;
use App\Models\FlagTermRegistration;
use App\Models\Student;
use App\Models\Table1;
use App\Models\FormClass;
use App\Models\TermReport;
use App\Models\Table2;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class Table1Controller extends Controller
{
    public function register(Request $request)
    {

        $classId = $request->class_id;
        $currentPeriod = AcademicTerm::whereIsCurrent(1)->first();
        $year = substr($currentPeriod->academic_year_id, 0, 4);
        $term = $currentPeriod->term;
        $newTermBeginning = $currentPeriod->new_term_beginning;
        $possibleAttendance = $currentPeriod->possible_attendance;

        if(!$classId){
            $students = Student::select('id', 'class_id')
            ->where('class_id', 'LIKE', '1%')
            ->orWhere('class_id', 'LIKE', '2%')
            ->orWhere('class_id', 'LIKE', '3%')
            ->orWhere('class_id', 'LIKE', '4%')
            ->orWhere('class_id', 'LIKE', '5%')
            ->orWhere('class_id', 'LIKE', '6%');
            // ->get();
        }
        else{
            $students = Student::select('id', 'class_id')
            ->where('class_id', $classId);
            // ->get();
        }

        $flagTable1 = FlagTermRegistration::where([
            ['year', $year],
            ['term', $term]
        ])
        ->orderBy('created_at', 'DESC')
        ->first();

        if(!$flagTable1 || $flagTable1->completed == 1){
            FlagTermRegistration::create([
                'year' => $year,
                'term' => $term,
                'total_rows' => $students->count(),
                'rows_processed' => 0,
                'next_chunk' => 1
            ]);

            $flagTable1 = FlagTermRegistration::where([
                ['year', $year],
                ['term', $term]
            ])
            ->orderBy('created_at', 'DESC')
            ->first();

        }

        $nextChunk = $flagTable1->next_chunk;
        $chunkCounter = 0;

        $students->chunk(100, function ($students) use($year, $term, $newTermBeginning, $possibleAttendance, $nextChunk, &$chunkCounter) {
            ++$chunkCounter;
            $counter = 0;

            if($chunkCounter == $nextChunk){

                foreach($students as $student){
                    ++$counter;
                    $studentId = $student->id;
                    $classId = $student->class_id;
                    Table1::updateOrCreate(
                        [
                            'student_id' => $studentId,
                            'year' => $year,
                            'term' => $term,
                        ],
                        [
                            'class_id' => $classId,
                            'new_term_beginning' => $newTermBeginning,
                            'possible_attendance' => $possibleAttendance
                        ]
                    );
                }

                $flagTable1 = FlagTermRegistration::where([
                    ['year', $year],
                    ['term', $term]
                ])
                ->orderBy('created_at', 'DESC')
                ->first();

                if($flagTable1->total_rows == $flagTable1->rows_processed + $counter){
                    $flagTable1 = $flagTable1->replicate()->fill([
                        'rows_processed' => $flagTable1->rows_processed + $counter,
                        'next_chunk' => $chunkCounter + 1,
                        'completed' => 1
                    ]);
                }
                else{
                    $flagTable1 = $flagTable1->replicate()->fill([
                        'rows_processed' => $flagTable1->rows_processed + $counter,
                        'next_chunk' => $chunkCounter + 1,
                    ]);
                }

                $flagTable1->save();

                return;
            }

        });

        return FlagTermRegistration::where([
            ['year', $year],
            ['term', $term]
        ])
        ->orderBy('created_at', 'DESC')
        ->first();

    }

    public function clearFlags ()
    {
        FlagTermRegistration::truncate();
    }

    public function show(Request $request)
    {
        $year = $request->year;
        $term = $request->term;
        $class_id = $request->class_id;

        $records = Table1::join('students', 'students.id', 'table1.student_id')
        ->join('form_classes', 'table1.class_id', 'form_classes.id' )
        ->select('table1.*','students.first_name', 'students.last_name', 'students.picture', 'form_classes.form_level')
        ->where([
            ['year', $year],
            ['term', $term],
            ['table1.class_id', $class_id]
        ])
        ->orderBy('last_name')
        ->orderBy('first_name')
        ->get();

        return ResourcesTable1::collection($records);
    }

    public function cmp($a, $b)
    {
        return strcmp($a->last_name, $b->last_name);
    }

    public function formClasses()
    {
        $currentPeriod = AcademicTerm::whereIsCurrent(1)->first();
        $year = substr($currentPeriod->academic_year_id, 0, 4);
        $term = $currentPeriod->term;
        $formClasses = Table1::select('class_id')
        ->where([
            ['year', $year],
            ['term', $term],
        ])->distinct()->addSelect(['form_level' => FormClass::select('form_level')
        ->whereColumn('id', 'table1.class_id')])
        ->orderBy('form_level')
        ->get();

        return $formClasses;
    }

    public function store(Request $request)
    {
        $form_class_id = $request->class_id;
        $form_level = null;
        $form_class = FormClass::where('id', $form_class_id)
        ->first();

        if($form_class){
            $form_level = $form_class->form_level;
        }

        $record = Table1::updateOrCreate(
            [
                'student_id' => $request->student_id,
                'year' => $request->year,
                'term' => $request->term,
            ],
            [
                'class_id' => $request->class_id,
                'new_term_beginning' => $request->new_term_beginning,
                'possible_attendance' => $request->possible_attendance,
                'times_absent' => $request->times_absent,
                'times_late' => $request->times_late,
                'comments' => $request->comments,
                'dcomments' => $request->dcomments,
                'auth' => $request->auth,
                'resp' => $request->resp,
                'coop' => $request->coop,
                'cocurricular' => $request->cocurricular,
                'mlate' => $request->mlate,
                'mabs' => $request->mabs,
                'mgrade' => $request->mgrade,
                'mapp' => $request->mapp,
                'mcon' => $request->mcon,
                'mcomments' => $request->mcomments,
            ]
        );

        return $record;
    }

    public function upload()
    {
        ini_set('max_execution_time', '1500');
        $file = './files/Table1.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        // return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){
            $id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $year = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            $term = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            $test = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(4,$i)->getValue();
            $class_id = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(5,$i)->getValue();
            $new_term_beginning = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(6,$i)->getValue();
            if($new_term_beginning && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $new_term_beginning)){
                $new_term_beginning = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($new_term_beginning);
                $new_term_beginning = date_format($new_term_beginning, "Y-m-d");
            }
            $possible_attendance = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(7,$i)->getValue();
            $times_absent = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(8,$i)->getValue();
            $times_late = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(9,$i)->getValue();
            $comments = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(10,$i)->getValue();
            $app = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(11,$i)->getValue();
            $con = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(12,$i)->getValue();
            $mmark = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(13,$i)->getValue();
            $mapp = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(14,$i)->getValue();
            $mcon = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(15,$i)->getValue();
            $mcomm = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(16,$i)->getValue();
            try {
                //code...
                $table1Record = Table1::create(
                    [
                        'student_id' => $id,
                        'year' => $year,
                        'term' => $term,
                        'class_id' => $class_id,
                        'new_term_beginning' => $new_term_beginning,
                        'possible_attendance' => $possible_attendance,
                        'times_absent' => $times_absent,
                        'times_late' => $times_late,
                        'comments' => $comments,
                        'app' => $app,
                        'con' => $con,
                        'mmark' => $mmark,
                        'mapp' => $mapp,
                        'mcomm' => $mcomm,
                    ]
                );
                //return $table1Record;
                if($table1Record->wasRecentlyCreated) $records++;
            } catch (\Throwable $th) {
                //throw $th;
                return $th->getMessage();
            }
        }
        //return $spreadsheet->getActiveSheet()->getHighestDataRow();
        return $records;
    }

    public function termRecords($year, $term)
    {
        $records = Table1::where([
            'year' => $year,
            'term' => $term
        ])->get();

        return $records;
    }

    public function showReportTerms(Request $request)
    {
        $studentId = $request->student_id;
        $report_terms = [];
        $terms = Table1::join('form_classes', 'form_classes.id', 'table1.class_id')
        ->select('table1.year', 'table1.term', 'table1.class_id', 'form_classes.form_level')
        ->where('student_id', $studentId)
        ->orderBy('year', 'desc')
        ->orderBy('term', 'desc')
        ->get();

        foreach($terms as $term){
            $term_record = [];
            $term_record['year'] = $term->year;
            $term_record['term'] = $term->term;
            $term_record['class_id'] = $term->class_id;
            $term_record['form_level'] = $term->form_level;
            $term_report = TermReport::where([
                ['year', $term->year],
                ['term', $term->term],
            ])->first();
            $term_record['posted'] = $term_report->posted;
            $term_record['date_posted'] = $term_report->date_posted;
            array_push($report_terms, $term_record);
        }

        return $report_terms;
    }

    public function changeClass(Request $request)
    {
        $data = [];

        $academic_term = AcademicTerm::where('is_current', 1)->first();
        $current_term = $academic_term->term;
        $current_year = substr($academic_term->academic_year_id, 0, 4);

        $table1_record = Table1::where([
            ['year', $current_year],
            ['term', $current_term],
            ['student_id', $request->student_id]
        ])->first();

        $table1_record->class_id = $request->class_id;

        $table1_record->save();

        $data['table1'] = $table1_record;

        $student_record = Student::where('id', $request->student_id)->first();

        $student_record->class_id = $request->class_id;

        $student_record->save();

        $data['student'] = $student_record;

        return $data;
    }

    public function storePossibleAttendance(Request $request)
    {
        if($request->class_id){
           return Table1::where([
                ['year', $request->year],
                ['term', $request->term],
                ['class_id', $request->class_id]
            ])->update(['possible_attendance' => $request->possible_attendance]);

        }
        else{
            return Table1::where([
                ['year', $request->year],
                ['term', $request->term]
            ])->update(['possible_attendance' => $request->possible_attendance]);
        };
    }

    public function storeNewTermBeginning(Request $request)
    {
        if($request->class_id){
            return Table1::where([
                ['year', $request->year],
                ['term', $request->term],
                ['class_id', $request->class_id]
            ])->update(['new_term_beginning' => $request->new_term_beginning]);
        }
        else{
            return Table1::where([
                ['year', $request->year],
                ['term', $request->term]
            ])->update(['new_term_beginning' => $request->new_term_beginning]);
        }
    }

    public function delete(Request $request)
    {
        $table2Records = Table2::where([
            ['student_id', $request->student_id],
            ['year', $request->year],
            ['term', $request->term]
        ])->get();

        if(sizeof($table2Records) != 0) abort('500', 'Table2 contains records');

        return Table1::where([
            ['student_id', $request->student_id],
            ['year', $request->year],
            ['term', $request->term]
        ])->delete();
    }

    public function getStudentsRegistered ()
    {
        $data = array();

        $academicTermRecord = AcademicTerm::where('is_current', 1)
        ->first();

        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;
        $year = $academicYearId ? substr($academicYearId, 0, 4) : null;
        $term = $academicTermRecord ? $academicTermRecord->term : null;
        
        $table1 = Table1::select('student_id')
        ->where([
            ['year', $year],
            ['term', $term]
        ])
        ->get();

        $data['students'] = $table1;
        $data['total'] = $table1->count();

        return $data;
    }
}
