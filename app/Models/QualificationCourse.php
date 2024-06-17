<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QualificationCourse extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'date',
        'count_subscribers',
        'price',
        'place',
        'remaining_number'
    ];

    public function user()
    {
        return $this->belongsToMany(
            User::class,
            'qualification_users',
            'qualification_id',
            'user_id',
        );
    }
}
