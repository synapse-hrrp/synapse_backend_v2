<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReactifFournisseur extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nom', 'code', 'contact_nom', 'telephone',
        'email', 'adresse', 'pays', 'actif', 'notes',
    ];

    protected $casts = [
        'actif' => 'boolean',
    ];

    public function commandes()
    {
        return $this->hasMany(ReactifCommande::class, 'fournisseur_id');
    }
}