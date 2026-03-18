<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeclarationNaissance extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'declarations_naissance';

    protected $fillable = [
        'accouchement_id',
        'nom',
        'prenom',
        'sexe',
        'date_heure_naissance',
        'lieu_naissance',
        'poids_naissance',
        'taille_naissance',
        'mere_patient_id',
        'pere_nom',
        'pere_prenom',
        'pere_profession',
        'status',
        'numero_acte',
        'date_enregistrement',
        'agent_id',
        'observations',
    ];

    protected $casts = [
        'date_heure_naissance' => 'datetime',
        'date_enregistrement'  => 'date',
        'taille_naissance'     => 'decimal:1',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function accouchement(): BelongsTo
    {
        return $this->belongsTo(Accouchement::class);
    }
}