<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $fillable = ['user_id', 'profileable_id', 'profileable_type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function profileable()
    {
        return $this->morphTo();
    }
}
