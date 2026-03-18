<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsommationProduit extends Model
{
    use HasFactory;

    protected $table = 'consommations_produits';

    protected $fillable = [
        'produit_id',
        'depot_id',
        'annee',
        'semaine',
        'mois',
        'quantite_vendue',
        'quantite_gratuite',
        'quantite_totale',
        'cmh_4_semaines',
        'cmm',
        'nb_ventes',
    ];

    protected $casts = [
        'annee' => 'integer',
        'semaine' => 'integer',
        'mois' => 'integer',
        'quantite_vendue' => 'integer',
        'quantite_gratuite' => 'integer',
        'quantite_totale' => 'integer',
        'cmh_4_semaines' => 'decimal:2',
        'cmm' => 'decimal:2',
        'nb_ventes' => 'integer',
    ];

    /**
     * Produit concerné
     */
    public function produit(): BelongsTo
    {
        return $this->belongsTo(Produit::class);
    }

    /**
     * Dépôt concerné
     */
    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    /**
     * Scope pour récupérer les 4 dernières semaines
     */
    public function scopeDernieresSemaines($query, int $produitId, int $depotId, int $nbSemaines = 4)
    {
        $anneeActuelle = now()->year;
        $semaineActuelle = now()->weekOfYear;

        return $query->where('produit_id', $produitId)
            ->where('depot_id', $depotId)
            ->where('annee', $anneeActuelle)
            ->where('semaine', '<=', $semaineActuelle)
            ->orderByDesc('semaine')
            ->limit($nbSemaines);
    }
}