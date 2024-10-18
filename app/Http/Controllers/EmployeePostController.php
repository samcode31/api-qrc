<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EmployeePost;

class EmployeePostController extends Controller
{
    public function show () 
    {
        return EmployeePost::select(
            'id',
            'post',
            'rank'
        )
        ->orderBy('id')
        ->get();
    }
}
