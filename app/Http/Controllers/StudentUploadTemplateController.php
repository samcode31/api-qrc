<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Throwable;

class StudentUploadTemplateController extends Controller
{
    public function download()
    {
        try {
            $file = "Registration Template.xlsx";
            $filePath = './files/'.$file;
            return response()->download($filePath, 'Registration Template.xlsx');
        } catch (Throwable $e) {
            return $e;
        }
        
    }
}
