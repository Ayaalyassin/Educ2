<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LaravelLegends\EloquentFilter\Concerns\HasFilter;

class Ads extends Model
{
    use HasFactory,HasFilter;

    protected $fillable = [
        'title',//
        'price',//
        'description',//
        'file',//
        'number_students',//
        'profile_teacher_id',
        'status',
        'place',//
        'date',//
        //'offer'
        'active'
    ];

    public function profile_teacher()
    {
        return $this->belongsTo(ProfileTeacher::class, 'profile_teacher_id');
    }

    public function profile_students()
    {
        return $this->belongsToMany(
            ProfileStudent::class,
            'profile_student_ads',
            'ads_id',
            'profile_student_id'
        )->withPivot('id');
    }
}
