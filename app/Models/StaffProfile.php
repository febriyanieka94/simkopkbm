<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffProfile extends Model
{
    protected $fillable = ['nip', 'department', 'position', 'phone', 'address', 'level_id'];

    public function profile()
    {
        return $this->morphOne(Profile::class, 'profileable');
    }

    public function level()
    {
        return $this->belongsTo(Level::class);
    }
}
