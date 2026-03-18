<?php

namespace Modules\Laboratoire\App\Models;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ExamenRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'registre_id',
        'examen_type_id',
        'tariff_item_id',
        'unit_price_applied',
        'billing_request_id',
        'status',
        'authorized_at',
        'completed_at',
        'notes',
        'is_urgent',
    ];

    protected $casts = [
        'unit_price_applied' => 'decimal:2',
        'is_urgent'          => 'boolean',
        'authorized_at'      => 'datetime',
        'completed_at'       => 'datetime',
    ];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function examenType(): BelongsTo
    {
        return $this->belongsTo(ExamenType::class, 'examen_type_id');
    }

    public function examen(): HasOne
    {
        return $this->hasOne(Examen::class);
    }

    public function scopeAutorises($query)
    {
        return $query->where('status', 'authorized');
    }

    public function scopeEnAttente($query)
    {
        return $query->where('status', 'pending_payment');
    }

    public function estAutorise(): bool
    {
        return $this->status === 'authorized';
    }

    public function estEnAttentePaiement(): bool
    {
        return $this->status === 'pending_payment';
    }
}