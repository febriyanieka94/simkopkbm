<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherAssignment extends Model
{
    protected $fillable = ['teacher_id', 'classroom_id', 'subject_id', 'academic_year_id', 'type'];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
