<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionLigne extends Model
{
    use HasFactory;

    protected $fillable = [
        'prescription_id',
        'produit_id',
        'medicament',
        'forme',
        'dosage',
        'frequence_par_jour',
        'duree_jours',
        'quantite',
        'instructions',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}