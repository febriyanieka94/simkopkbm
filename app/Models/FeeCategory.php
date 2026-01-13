<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeeCategory extends Model
{
    protected $fillable = ['name', 'code', 'description', 'default_amount'];

    public function billings()
    {
        return $this->hasMany(StudentBilling::class);
    }
}
