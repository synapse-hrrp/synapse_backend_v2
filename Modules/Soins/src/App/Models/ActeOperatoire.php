<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActeOperatoire extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'actes_operatoires';
    protected $fillable = [
        'acte_operatoire_request_id',
        'agent_id',
        'status',
        'type_operation',
        'type_anesthesie',
        'salle',
        'compte_rendu',
        'incidents',
        'suites_operatoires',
        'complications',
        'details_complications',
        'debut_at',
        'fin_at',
        'reveil_at',
    ];

    protected $casts = [
        'complications' => 'boolean',
        'debut_at'      => 'datetime',
        'fin_at'        => 'datetime',
        'reveil_at'     => 'datetime',
    ];

    // ── Relations ──────────────────────────────────────────────
    public function request(): BelongsTo
    {
        return $this->belongsTo(ActeOperatoireRequest::class, 'acte_operatoire_request_id');
    }
}