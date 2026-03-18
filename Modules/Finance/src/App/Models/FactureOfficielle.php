<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Reception\App\Models\BillingRequest;

class FactureOfficielle extends Model
{
    protected $table = 'factures_officielles';

    protected $fillable = [
        'numero_global',
        'module_source',
        'table_source',
        'source_id',

        // ✅ si la colonne existe en DB (sinon migration à ajouter)
        'billing_request_id',

        'total_ht',
        'total_tva',
        'total_ttc',
        'client_nom',
        'client_reference',
        'date_emission',
        'statut',
        'statut_paiement',
    ];

    protected $casts = [
        'total_ht' => 'decimal:2',
        'total_tva' => 'decimal:2',
        'total_ttc' => 'decimal:2',
        'date_emission' => 'date',
        'billing_request_id' => 'integer',
        'source_id' => 'integer',
    ];

    // ✅ DOIT matcher l’ENUM DB (migration): NON_PAYE / PARTIEL / PAYE
    public const PAY_UNPAID    = 'NON_PAYE';
    public const PAY_PARTIALLY = 'PARTIEL';
    public const PAY_PAID      = 'PAYE';

    public static function allowedPaymentStatuses(): array
    {
        return [
            self::PAY_UNPAID,
            self::PAY_PARTIALLY,
            self::PAY_PAID,
        ];
    }

    public function billingRequest(): BelongsTo
    {
        return $this->belongsTo(BillingRequest::class, 'billing_request_id', 'id');
    }
}