<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use OwenIt\Auditing\Contracts\Auditable;

class Commande extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'numero',
        'fournisseur_id',
        'depot_id',
        'date_commande',
        'statut',
        'observations',
        'type',
        'declencheur',
        'priorite',
        'stock_actuel_declenchement',
        'cmh_au_declenchement',
        'validee_par_user_id',
        'validee_at',
        'envoyee_at',
        'date_livraison_prevue',
        'montant_total',
        'fichier_export_path',
        'format_export',
        'exporte_at',
        'exporte_par_user_id',
    ];

    protected $casts = [
        'date_commande' => 'date',
        'validee_at' => 'datetime',
        'envoyee_at' => 'datetime',
        'date_livraison_prevue' => 'date',
        'exporte_at' => 'datetime',
        'montant_total' => 'decimal:2',
        'stock_actuel_declenchement' => 'integer',
        'cmh_au_declenchement' => 'decimal:2',
    ];

    /**
     * Fournisseur
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Dépôt
     */
    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    /**
     * Lignes de commande
     */
    public function lignes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    /**
     * User qui a validé
     */
    public function validateurUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'validee_par_user_id');
    }

    /**
     * User qui a exporté
     */
    public function exporteurUser(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'exporte_par_user_id');
    }

    /**
     * Scope commandes automatiques
     */
    public function scopeAutomatiques(Builder $query): Builder
    {
        return $query->whereIn('type', ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE']);
    }

    /**
     * Scope commandes manuelles
     */
    public function scopeManuelles(Builder $query): Builder
    {
        return $query->where('type', 'MANUELLE');
    }

    /**
     * Scope commandes en attente de validation
     */
    public function scopeEnAttenteValidation(Builder $query): Builder
    {
        return $query->where('statut', 'EN_ATTENTE_VALIDATION');
    }

    /**
     * Scope commandes urgentes
     */
    public function scopeUrgentes(Builder $query): Builder
    {
        return $query->whereIn('priorite', ['CRITIQUE', 'URGENTE']);
    }

    /**
     * Scope commandes validées
     */
    public function scopeValidees(Builder $query): Builder
    {
        return $query->where('statut', 'VALIDEE');
    }

    /**
     * Vérifier si commande est automatique
     */
    public function estAutomatique(): bool
    {
        return in_array($this->type, ['AUTO_SEUIL_MIN', 'AUTO_SURCONSO', 'URGENCE_RUPTURE']);
    }

    /**
     * Vérifier si commande est validée
     */
    public function estValidee(): bool
    {
        return $this->statut === 'VALIDEE';
    }

    /**
     * Vérifier si commande est envoyée
     */
    public function estEnvoyee(): bool
    {
        return !is_null($this->envoyee_at);
    }
}