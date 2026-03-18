<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use OwenIt\Auditing\Contracts\Auditable;

class Vente extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'numero',
        'depot_id',
        'user_id',
        'date_vente',
        'type',
        'statut',
        'montant_ttc',
        'observations',
    ];

    protected $casts = [
        'date_vente' => 'date',
        'montant_ttc' => 'decimal:2',
    ];

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    public function lignes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    public function mouvements(): HasMany
    {
        return $this->hasMany(MouvementStock::class);
    }
}