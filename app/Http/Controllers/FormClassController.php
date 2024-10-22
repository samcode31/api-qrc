<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class FormClassController extends Controller
{
    public function show()
    {
        return FormClass::all();        
    }

    public function upload(){
        $file = './files/form_classes.xlsx';
        $reader = new Xlsx();
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->getHighestDataRow();
        // return $rows;
        $records = 0;
        for($i = 2; $i <= $rows; $i++){            
            $id = $sheet->getCell("A$i")->getValue();
            $form_level = $sheet->getCell("B$i")->getValue();
            
            $studentSubject = FormClass::create([
                "id" => $id,
                "form_level" => $form_level
            ]);
            if($studentSubject->exists) $records++;
        }
        
        return $records;
    }

    public function showFormLevels()
    {
        return FormClass::select('form_level')
        ->where('form_level','<>', null)
        ->distinct()
        ->get();
    }
}
