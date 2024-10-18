<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use App\Models\Town;
use App\Models\Student;

class TownController extends Controller
{
    public function upload(){
        $file = './files/towns.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){            
            $title = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            
            $record = Town::create([
                "town_name" => $title,
            ]);
            if($record->exists) $records++;
        }
        
        return $records;
    }

    public function showDistinct() 
    {
        $data = array();
        $towns = Student::whereBetween('form_class_id', ['1%', '7%'])
        ->select('address_line_2 as text')
        ->distinct()
        ->get();
        // ->whereNotNull('text');
        foreach($towns as $index => $town)
        {
            $classIds = Student::where('address_line_2', $town->text)
            ->whereBetween('form_class_id', ['1%', '7%'])
            ->select('form_class_id')
            ->distinct()
            ->pluck('form_class_id');

            $townClassIds = array();
            forEach($classIds as $classId)
            {
                $townClass = array();
                $count = Student::where([
                    ['address_line_2', $town->text],
                    ['form_class_id', $classId]
                ])
                ->get()
                ->count();
                $townClass['form_class_id'] = $classId;
                $townClass['count'] = $count;
                $townClassIds[] = $townClass;
            }

            $town->id = $index;
            $town->class_ids = $townClassIds;
            $data[] = $town;
        }
        return $data;
    }

    public function show() 
    {
        return Town::all();
    }

}
