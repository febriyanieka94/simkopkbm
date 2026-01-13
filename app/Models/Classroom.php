<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    protected $fillable = ['academic_year_id', 'level_id', 'name', 'homeroom_teacher_id'];

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function homeroomTeacher()
    {
        return $this->belongsTo(User::class, 'homeroom_teacher_id');
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
