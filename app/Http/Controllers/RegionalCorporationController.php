<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RegionalCorporation;

class RegionalCorporationController extends Controller
{
    public function show ()
    {
        return RegionalCorporation::all();
    }
}
