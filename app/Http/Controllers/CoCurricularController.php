<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CoCurricular;

class CoCurricularController extends Controller
{
    public function show ()
    {
        return CoCurricular::all();
    }
}
