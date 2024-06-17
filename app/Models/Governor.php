<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Governor extends Model
{
    use HasFactory;
    protected $table = "governors";
    protected $fillable = [
        'wallet_id',
        'image_transactions',
        'type',
        'amount',
    ];
    protected $hidden = ['created_at', 'updated_at'];

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
