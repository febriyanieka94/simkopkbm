<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = [
        'nis',
        'nisn',
        'phone',
        'address',
        'dob',
        'pob',
        'photo',
        'father_name',
        'mother_name',
        'guardian_name',
        'guardian_phone',
        'parent_id',
        'classroom_id',
        'birth_order',
        'total_siblings',
        'previous_school',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'dob' => 'date',
            'birth_order' => 'integer',
            'total_siblings' => 'integer',
        ];
    }

    public function profile()
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function parent()
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function classroom()
    {
        return $this->belongsTo(Classroom::class);
    }

    public function periodicRecords()
    {
        return $this->hasMany(StudentPeriodicRecord::class);
    }
}
