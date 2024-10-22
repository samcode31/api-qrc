<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use App\Models\AcademicTerm;

class SpreadsheetStudentDataController extends Controller
{
    public function download(Request $request)
    {

        $headers = $request->input('headers');

        $select = array();

        $classIds = array();

        $towns = array();

        $occupationsFather = array();

        $occupationsMother = array();

        $occupationsGuardian = array();

        $filters = json_decode($request->input('filters'));

        $formClass = false;

        $academicTermRecord = AcademicTerm::where('is_current', 1)->first();

        $academicYearId = $academicTermRecord ? $academicTermRecord->academic_year_id : null;


        // $query = Student::join(
        //     'form_classes',
        //     'students.class_id',
        //     'form_classes.id'
        // )
        // ->select(
        //     'students.id',
        //     'students.last_name',
        //     'students.first_name',
        // )
        // ->whereNotNull('class_level');

        $query = DB::table('students')
        ->join('form_classes', 'students.class_id', 'form_classes.id')
        ->leftJoin('houses', 'students.house_code', 'houses.id')
        ->select(
            'students.id',
            'students.last_name',
            'students.first_name',
        )
        ->whereNotNull('form_level');

        // ->whereBetween('class_id', ['1%', '7%']);
        // return $query->get();

        $arrayDataheaders = array();
       
        foreach($headers as $header)
        {
            $header = json_decode($header);
            
            if($header->value == 'actions') continue;

            if($header->value == 'id')
            {
                $arrayDataHeaders[] = $header->text;
                continue;
            }

            if($header->value == 'name')
            {
                // $query->addSelect(
                //     DB::raw("CONCAT(last_name, ', ', first_name) as  Name")
                // );
                $arrayDataHeaders[] = 'Last Name';
                $arrayDataHeaders[] = 'First Name';
                continue;
            }
            $arrayDataHeaders[] = $header->text;

            if($header->value == 'formatted_date_of_birth')
            {
                $query->addSelect('students.date_of_birth');
                continue;
            }

            if($header->value == 'class_id')
            {
                $query->addSelect('students.class_id');
                continue;
            }

            if($header->value == 'house_code')
            {
                $query->addSelect('houses.name');
                continue;
            }

            if($header->value == 'class_id') $formClass = true;

            $query->addSelect($header->value);
        }

        if($formClass) $query->orderBy('class_id');
        $query->orderBy('students.last_name');

        $students = $query->get();

        foreach($filters as $key => $filter)
        {
            switch ($key) {
                case 'class_id':
                    foreach($filter as $formClass)
                    {
                        $classIds[] = $formClass->id;
                    }
                    break;
                case 'address_line_2':
                    foreach($filter as $town)
                    {
                        $towns[] = $town->value;
                    }
                    break;
                case 'father_occupation':
                    foreach($filter as $occupation)
                    {
                        $occupationsFather[] = $occupation->value;
                    }
                    break;
                case 'mother_occupation':
                    foreach($filter as $occupation)
                    {
                        $occupationsMother[] = $occupation->value;
                    }
                    break;
                case 'guardian_occupation':
                    foreach($filter as $occupation)
                    {
                        $occupationsGuardian[] = $occupation->value;
                    }
                    break;
                default:
                    # code...
                    break;
            }
        }

        $data = $students;

        if($filters !== null && count((array)$filters) > 0)
        {
            $data = $students->filter(function ($student) use(
                    $classIds,
                    $towns,
                    $occupationsFather,
                    $occupationsMother,
                    $occupationsGuardian,
                ) {
                // return in_array($student->class_id, $classIds) &&
                // in_array($student->address_line_2, $towns) &&
                // in_array($student->father_occupation, $occupationsFather);
                return $this->inFilterArray($student->class_id, $classIds);
                // $this->inFilterArray($student->address_line_2, $towns) &&
                // $this->inFilterArray($student->father_occupation, $occupationsFather) &&
                // $this->inFilterArray($student->mother_occupation, $occupationsMother) &&
                // $this->inFilterArray($student->guardian_occupation, $occupationsGuardian);
            });
        }

        $formClass = false;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // return $arrayDataHeaders;
        $sheet->fromArray($arrayDataHeaders);
        // return $data->toArray();
        $data = $data->map(function($item){
            $arrayItem = json_decode(json_encode($item), true);
            // if(isset($arrayItem['class_id']))
            // {
            //     unset($arrayItem['class_id']);
            // }
            return array_values($arrayItem);
        })->toArray();

        $sheet->fromArray(
            $data,
            NULL,
            'A2'
        );

        $highestColumn = $sheet->getHighestDataColumn();
        $highestRow = $sheet->getHighestRow();
        $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

        for($col = 1; $col <= $highestColumnIndex; ++$col){
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        for($row = 1; $row <= $highestRow; ++$row){
            for($col = 1; $col <= $highestColumnIndex; ++$col){
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
                $headerValue = $sheet->getCell($column."1")->getValue();
                if(
                    $headerValue == 'ID' ||
                    $headerValue == 'Date of Birth' ||
                    $headerValue == 'Birth Certificate Pin'
                )
                {
                    $sheet->getStyle($column.$row)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
                }
            }
        }
        $styleArray = [
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => [
                    'argb' => 'FFBFBFBF'
                ]
            ]
        ];

        $sheet->getStyle('A1:'.$highestColumn.'1')->applyFromArray($styleArray);


        $sheet->freezePane('A2');

        $file = "Student Data.xlsx";
        $filePath = storage_path('app/public/'.$file);

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);
        return response()->download($filePath, $file);
    }

    private function inFilterArray ($item, $array)
    {
        if(empty($array)) return true;
        return in_array($item, $array);
    }
}
