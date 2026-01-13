<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Level extends Model
{
    protected $fillable = ['name', 'type'];

    public function classrooms()
    {
        return $this->hasMany(Classroom::class);
    }

    public function subjects()
    {
        return $this->hasMany(Subject::class);
    }

    public function isClassTeacherSystem(): bool
    {
        return $this->type === 'class_teacher';
    }
}
