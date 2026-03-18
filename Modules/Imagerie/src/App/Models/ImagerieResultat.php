<?php

namespace Modules\Imagerie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagerieResultat extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'imagerie_id',
        'compte_rendu',
        'conclusion',
        'chemin_images',
        'format_images',
        'recommandations',
        'status',
        'agent_id',
        'valide_le',
    ];

    protected $casts = [
        'valide_le' => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function imagerie(): BelongsTo
    {
        return $this->belongsTo(Imagerie::class);
    }
}