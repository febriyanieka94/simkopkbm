<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $fillable = ['classroom_id', 'academic_year_id', 'subject_id', 'teacher_id', 'date', 'notes'];

    protected function casts(): array
    {
        return [
            'date' => 'date',
        ];
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function items()
    {
        return $this->hasMany(AttendanceItem::class);
    }
}
