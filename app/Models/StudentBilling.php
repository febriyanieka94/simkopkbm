<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentBilling extends Model
{
    protected $fillable = [
        'student_id', 
        'fee_category_id', 
        'academic_year_id', 
        'month', 
        'amount', 
        'paid_amount', 
        'due_date', 
        'status', 
        'notes'
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feeCategory()
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function getRemainingAmountAttribute()
    {
        return $this->amount - $this->paid_amount;
    }
}
