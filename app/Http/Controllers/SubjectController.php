<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class SubjectController extends Controller
{
    public function store(Request $request)
    {
        return Subject::updateOrCreate(
            ['id' => $request->id],
            [
                'title' => $request->title,
                'abbr' => $request->abbr
            ]
        );
    }

    public function delete(Request $request)
    {
        $data = [];

        $data['deleted_subject'] =  Subject::where('id', $request->id)->delete();

        return $data;
    }

    

    public function show(){
        return Subject::select('id', 'title', 'abbr')
        ->orderBy('title')
        ->get();
    }

    public function upload(){
        $file = './files/subjects.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $rows = $spreadsheet->getActiveSheet()->getHighestDataRow();
        //return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){            
            $subjCode = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1,$i)->getValue();
            $subject = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2,$i)->getValue();
            $abbr = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(3,$i)->getValue();
            $subject = Subject::create([
                "id" => $subjCode,
                "title" => $subject,
                "abbr" => $abbr
            ]);
                
            if($subject->exists) $records++;
            
        }
        
        return $records;
    }
}
