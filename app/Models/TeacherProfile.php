<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TeacherProfile extends Model
{
    protected $fillable = ['nip', 'phone', 'address', 'education_level'];

    public function profile()
    {
        return $this->morphOne(Profile::class, 'profileable');
    }
}
