<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\FormClass;
use Illuminate\Support\Facades\DB;

class FormClassController extends Controller
{
    public function show()
    {
        return DB::table('form_classes')
        ->leftJoin(
            'form_teachers', 
            'form_teachers.form_class_id', 
            'form_classes.id'
        )
        ->leftJoin('employees', 'form_teachers.employee_id', 'employees.id')
        ->select(
            'form_classes.id',
            'form_classes.class_level',
            'form_classes.class_group',
            DB::raw('
                CASE
                    WHEN employees.id IS NOT NULL THEN CONCAT(form_classes.class_name, " - ", employees.first_name, " ", employees.last_name) 
                    WHEN form_classes.class_group IS NOT NULL THEN CONCAT(form_classes.class_name, " - GP",form_classes.class_group)
                    ELSE form_classes.class_name
                END AS class_name    
            ')
        )
        ->get();
        
        // return FormClass::leftJoin(
        //     'form_teachers',
        //     'form_teachers.form_class_id',
        //     'form_classes.id'
        // )
        // ->leftJoin(
        //     'employees',
        //     'form_teachers.employee_id',
        //     'employees.id'
        // )
        // ->select(
        //     'form_classes.id',
        //     'form_classes.class_level',
        //     'form_classes.class_name',
        //     'form_classes.class_group',
        //     DB::raw("CONCAT(employees.first_name, ' ', employees.last_name) as teacher")
        // )
        // ->get();
              
    }
}
