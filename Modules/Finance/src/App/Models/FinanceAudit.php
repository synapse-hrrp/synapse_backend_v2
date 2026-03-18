<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceAudit extends Model
{
    protected $table = 't_finance_audits';

    public $timestamps = false;

    protected $fillable = [
        'evenement',
        'session_id',
        'user_id',
        'paiement_id',
        'table_source',
        'source_id',
        'payload',
        'cree_le',
    ];

    protected $casts = [
        'payload' => 'array',
        'cree_le' => 'datetime',
        'session_id' => 'integer',
        'user_id' => 'integer',
        'paiement_id' => 'integer',
        'source_id' => 'integer',
    ];

    /**
     * Helper statique pour écrire un audit immuable.
     * ✅ userId devient nullable car certains audits sont "système" (afterCommit / interne).
     */
    public static function log(
        string $evenement,
        ?int $userId = null,
        ?int $sessionId = null,
        ?int $paiementId = null,
        ?string $tableSource = null,
        ?int $sourceId = null,
        array $payload = []
    ): self {
        // ✅ fallback si audit "système"
        $resolvedUserId = $userId ?? auth()->id();

        // si toujours null, on met 0 (à défaut d'un user système)
        $resolvedUserId = $resolvedUserId ?? 0;

        return self::create([
            'evenement'    => $evenement,
            'user_id'      => $resolvedUserId,
            'session_id'   => $sessionId,
            'paiement_id'  => $paiementId,
            'table_source' => $tableSource,
            'source_id'    => $sourceId,
            'payload'      => $payload,
            'cree_le'      => now(),
        ]);
    }
}