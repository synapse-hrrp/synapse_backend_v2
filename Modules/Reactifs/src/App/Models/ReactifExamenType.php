<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactifExamenType extends Model
{
    protected $table = 'reactif_examen_type';

    protected $fillable = [
        'reactif_id', 'examen_type_id', 'quantite_utilisee', 'unite', 'actif', 'notes',
    ];

    protected $casts = [
        'quantite_utilisee' => 'decimal:3',
        'actif'             => 'boolean',
    ];

    public function reactif()
    {
        return $this->belongsTo(Reactif::class);
    }
}