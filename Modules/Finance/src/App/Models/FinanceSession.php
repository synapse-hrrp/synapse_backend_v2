<?php

namespace Modules\Finance\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceSession extends Model
{
    protected $table = 't_finance_sessions';

    protected $fillable = [
        'user_id',
        'poste',
        'module_scope',     // ✅ ajout
        'ouverte_le',
        'fermee_le',
        'nb_paiements',
        'total_montant',
        'cle_ouverture',
    ];

    protected $casts = [
        'ouverte_le'     => 'datetime',
        'fermee_le'      => 'datetime',
        'total_montant'  => 'decimal:2',
    ];

    public function paiements(): HasMany
    {
        return $this->hasMany(FinancePayment::class, 'session_id');
    }

    public function audits(): HasMany
    {
        return $this->hasMany(FinanceAudit::class, 'session_id');
    }

    public function estOuverte(): bool
    {
        return is_null($this->fermee_le);
    }

    public static function cleOuverture(int $userId, string $poste): string
    {
        // même logique que ton "open_key" : unique tant que session ouverte
        return sha1("user={$userId}|poste={$poste}");
    }

    public static function sessionOuverte(int $userId, string $poste): ?self
    {
        return self::query()
            ->where('user_id', $userId)
            ->where('poste', $poste)
            ->whereNull('fermee_le')
            ->first();
    }
}
