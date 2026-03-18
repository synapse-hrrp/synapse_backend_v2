<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactifCommandeLigne extends Model
{
    protected $fillable = [
        'commande_id', 'reactif_id', 'quantite_commandee', 'quantite_recue',
        'prix_unitaire', 'montant_ligne', 'date_peremption',
        'numero_lot', 'statut', 'notes',
    ];

    protected $casts = [
        'quantite_commandee' => 'decimal:3',
        'quantite_recue'     => 'decimal:3',
        'prix_unitaire'      => 'decimal:2',
        'montant_ligne'      => 'decimal:2',
        'date_peremption'    => 'date',
    ];

    public function commande()
    {
        return $this->belongsTo(ReactifCommande::class, 'commande_id');
    }

    public function reactif()
    {
        return $this->belongsTo(Reactif::class);
    }
}