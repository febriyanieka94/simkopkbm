<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Score extends Model
{
    protected $fillable = ['student_id', 'subject_id', 'classroom_id', 'academic_year_id', 'score_category_id', 'score', 'notes'];

    protected function casts(): array
    {
        return [
            'score' => 'decimal:2',
        ];
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function category()
    {
        return $this->belongsTo(ScoreCategory::class, 'score_category_id');
    }
}
