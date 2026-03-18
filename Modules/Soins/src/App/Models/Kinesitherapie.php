<?php

namespace Modules\Soins\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kinesitherapie extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'kinesitherapie_request_id',
        'agent_id',
        'status',
        'observations',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at'  => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(KinesitherapieRequest::class, 'kinesitherapie_request_id');
    }
}