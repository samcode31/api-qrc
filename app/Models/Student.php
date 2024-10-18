<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $guarded = [];

    // protected $fillable = ['*'];

    protected $hidden = [        
        'created_at',
        'updated_at',
    ];

    public function UserStudent()
    {
        return $this->belongsTo('App\UserStudent');
    }
}
