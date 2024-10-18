<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\EthnicGroup;

class EthnicGroupController extends Controller
{
    public function show ()
    {
        return EthnicGroup::all();
    }
}
