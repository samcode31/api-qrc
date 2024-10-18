<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TeacherLesson extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function subject()
    {
        return $this->belongsTo('App\Models\Subject');
    }

    public function formClass()
    {
        return $this->belongsTo('App\Models\FormClass');
    }
}
