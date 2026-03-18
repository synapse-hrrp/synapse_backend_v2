<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KinesitherapieRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'registre_id',
        'tariff_item_id',
        'billing_request_id',
        'unit_price_applied',
        'type_reeducation',
        'status',
        'is_urgent',
        'motif',
        'notes',
        'agent_id',
        'authorized_at',
        'completed_at',
    ];

    protected $casts = [
        'is_urgent'     => 'boolean',
        'authorized_at' => 'datetime',
        'completed_at'  => 'datetime',
    ];

    public function kinesitherapie(): HasOne
    {
        return $this->hasOne(Kinesitherapie::class);
    }

    public function estAutorise(): bool
    {
        return $this->status === 'authorized';
    }

    public function scopeAutorises($query)
    {
        return $query->where('status', 'authorized');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', 'pending_payment');
    }
}