<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPeriodicRecord extends Model
{
    protected $fillable = [
        'student_profile_id',
        'academic_year_id',
        'semester',
        'weight',
        'height',
        'head_circumference',
        'recorded_by',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'float',
            'height' => 'float',
            'head_circumference' => 'float',
            'semester' => 'integer',
        ];
    }

    public function studentProfile(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
