<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Religion;

class ReligionController extends Controller
{
    public function show () 
    {
        return Religion::all();
    }
}
