<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReactifCommande extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'numero', 'fournisseur_id', 'statut', 'date_commande',
        'date_livraison_prevue', 'date_livraison_reelle',
        'montant_total', 'created_by', 'notes',
    ];

    protected $casts = [
        'date_commande'          => 'date',
        'date_livraison_prevue'  => 'date',
        'date_livraison_reelle'  => 'date',
        'montant_total'          => 'decimal:2',
    ];

    public function fournisseur()
    {
        return $this->belongsTo(ReactifFournisseur::class, 'fournisseur_id');
    }

    public function lignes()
    {
        return $this->hasMany(ReactifCommandeLigne::class, 'commande_id');
    }

    // recalcule le montant total depuis les lignes
    public function recalculerMontant(): void
    {
        $this->update([
            'montant_total' => $this->lignes()->sum('montant_ligne'),
        ]);
    }
}