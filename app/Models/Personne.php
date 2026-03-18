<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Personne extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 't_personnes';

    protected $guarded = [];

    public function patient()
    {
        return $this->hasOne(Patient::class);
    }
}
