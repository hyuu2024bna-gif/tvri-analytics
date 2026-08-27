<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $fillable = ['nama', 'slug'];

    public function contents()
    {
        return $this->hasMany(Content::class);
    }
}
