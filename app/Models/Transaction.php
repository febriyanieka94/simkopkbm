<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'student_billing_id',
        'user_id',
        'amount',
        'payment_date',
        'payment_method',
        'reference_number',
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function billing()
    {
        return $this->belongsTo(StudentBilling::class, 'student_billing_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
