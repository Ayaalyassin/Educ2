<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TeachingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',//
        'type',//
        'description',//
        'file',//
        'status',
        'profile_teacher_id',
        'price',//
        'views'
    ];

    public function profile_teacher()
    {
        return $this->belongsTo(ProfileTeacher::class, 'profile_teacher_id');
    }


}
