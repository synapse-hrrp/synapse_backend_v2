<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancePayment extends Model
{
    protected $table = 't_finance_paiements';

    protected $fillable = [
        'session_id',
        'encaisse_par_user_id',
        'module_source',
        'table_source',
        'source_id',
        'montant',
        'mode',
        'reference',
        'statut',
        'raison_annulation',
        'annule_le',
        'annule_par_user_id',
    ];

    protected $casts = [
        'montant'   => 'decimal:2',
        'annule_le' => 'datetime',
    ];

    // ✅ statuts paiement (caisse)
    public const STATUS_VALIDE = 'valide';
    public const STATUS_ANNULE = 'annule';

    /**
     * ✅ Auto-fill encaisseur/annuleur si user connecté
     * (sécurité supplémentaire, en plus du controller)
     */
    protected static function booted(): void
    {
        static::creating(function (self $payment) {

            // encaisseur auto si manquant
            if (empty($payment->encaisse_par_user_id)) {
                $user = auth()->user() ?: auth('sanctum')->user();
                if ($user) {
                    $payment->encaisse_par_user_id = (int) $user->id;
                }
            }

            // statut par défaut si manquant
            if (empty($payment->statut)) {
                $payment->statut = self::STATUS_VALIDE;
            }
        });

        static::updating(function (self $payment) {

            // Si on passe à "annule", remplir auto annule_par_user_id + annule_le
            if ($payment->isDirty('statut') && $payment->statut === self::STATUS_ANNULE) {

                if (empty($payment->annule_le)) {
                    $payment->annule_le = now();
                }

                if (empty($payment->annule_par_user_id)) {
                    $user = auth()->user() ?: auth('sanctum')->user();
                    if ($user) {
                        $payment->annule_par_user_id = (int) $user->id;
                    }
                }
            }
        });
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(FinanceSession::class, 'session_id');
    }

    public function estValide(): bool
    {
        return $this->statut === self::STATUS_VALIDE;
    }

    public function estAnnule(): bool
    {
        return $this->statut === self::STATUS_ANNULE;
    }

    public static function totalPaye(string $tableSource, int $sourceId): float
    {
        return (float) self::query()
            ->where('table_source', $tableSource)
            ->where('source_id', $sourceId)
            ->where('statut', self::STATUS_VALIDE)
            ->sum('montant');
    }
}