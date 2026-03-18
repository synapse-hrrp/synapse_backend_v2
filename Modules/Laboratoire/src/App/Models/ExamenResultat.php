<?php

namespace Modules\Laboratoire\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenResultat extends Model
{
    use HasFactory;

    protected $fillable = [
        'examen_id',
        'parametre',
        'valeur',
        'unite',
        'valeur_normale_min',
        'valeur_normale_max',
        'interpretation',
        'observations',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function examen(): BelongsTo
    {
        return $this->belongsTo(Examen::class);
    }
}