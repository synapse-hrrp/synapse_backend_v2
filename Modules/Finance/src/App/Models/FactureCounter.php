<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;

class FactureCounter extends Model
{
    protected $table = 'facture_counters';

    protected $fillable = ['annee', 'compteur'];
}