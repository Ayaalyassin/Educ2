<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialReport extends Model
{
    use HasFactory;

    protected $table = "financial_reports";
    protected $fillable = [
        'type',
        'name',
        'value',
        'profitRatio'
    ];
    protected $hidden = ['created_at', 'updated_at'];
}
