<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AssesmentCourse extends Model
{
    use HasFactory;

    protected $table = 'assesment_course';
    
    protected $guarded = [];
}
