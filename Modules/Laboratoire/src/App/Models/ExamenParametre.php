<?php

namespace Modules\Laboratoire\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamenParametre extends Model
{
    use HasFactory;

    protected $fillable = [
        'examen_type_id',
        'nom',
        'code',
        'unite',
        'normale_min',
        'normale_max',
        'type_valeur',
        'ordre',
        'active',
    ];

    protected $casts = [
        'normale_min' => 'decimal:2',
        'normale_max' => 'decimal:2',
        'active'      => 'boolean',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function examenType(): BelongsTo
    {
        return $this->belongsTo(ExamenType::class);
    }
}