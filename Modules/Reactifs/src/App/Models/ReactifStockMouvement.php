<?php

namespace Modules\Reactifs\App\Models;

use Illuminate\Database\Eloquent\Model;

class ReactifStockMouvement extends Model
{
    protected $fillable = [
        'reactif_id', 'type', 'quantite', 'stock_avant',
        'stock_apres', 'reference', 'user_id', 'motif', 'date_mouvement',
    ];

    protected $casts = [
        'quantite'        => 'decimal:3',
        'stock_avant'     => 'decimal:3',
        'stock_apres'     => 'decimal:3',
        'date_mouvement'  => 'datetime',
    ];

    public function reactif()
    {
        return $this->belongsTo(Reactif::class);
    }

    public function consommation()
    {
        return $this->hasOne(ReactifConsommation::class, 'mouvement_id');
    }
}