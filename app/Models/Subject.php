<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = ['name', 'code', 'level_id'];

    public function level()
    {
        return $this->belongsTo(Level::class);
    }

    public function teacherAssignments()
    {
        return $this->hasMany(TeacherAssignment::class);
    }
}
