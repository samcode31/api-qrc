<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Table1 extends Model
{
    use HasFactory;

    protected $table = 'table1';
    
    protected $guarded = [];

    protected $hidden = [            
        'created_at',
        'updated_at',
    ];

    public function student()
    {
        return $this->belongsTo('App\Models\Student');
    }

    public function formLevel()
    {
        return $this->belongsTo('App\Models\FormClass');
    }
}
