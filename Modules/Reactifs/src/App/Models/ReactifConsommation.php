<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactifConsommation extends Model
{
    protected $fillable = [
        'reactif_id', 'examen_id', 'examen_type_id',
        'quantite_consommee', 'stock_avant', 'stock_apres',
        'mouvement_id', 'consomme_le',
    ];

    protected $casts = [
        'quantite_consommee' => 'decimal:3',
        'stock_avant'        => 'decimal:3',
        'stock_apres'        => 'decimal:3',
        'consomme_le'        => 'datetime',
    ];

    public function reactif()
    {
        return $this->belongsTo(Reactif::class);
    }

    public function mouvement()
    {
        return $this->belongsTo(ReactifStockMouvement::class, 'mouvement_id');
    }
}