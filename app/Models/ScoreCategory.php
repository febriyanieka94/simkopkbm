<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoreCategory extends Model
{
    protected $fillable = ['name', 'weight'];

    public function scores()
    {
        return $this->hasMany(Score::class);
    }
}
