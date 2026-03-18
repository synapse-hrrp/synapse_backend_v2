<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class LigneVenteStock extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        'ligne_vente_id',
        'stock_id',
        'quantite',
    ];

    protected $casts = [
        'quantite' => 'integer',
    ];

    public function ligneVente(): BelongsTo
    {
        return $this->belongsTo(LigneVente::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }
}