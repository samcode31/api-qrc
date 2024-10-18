<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table2 extends Model
{
    use HasFactory;

    protected $table = 'table2';
    protected $guarded = [];

    public function student(){
        return $this->belongsTo('App\Models\Student');
    }

    public function subject(){
        return $this->belongsTo('App\Models\Subject');
    }
}
