<?php

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationAgent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'affectation_agents';

    protected $fillable = [
        'date_debut',
        'date_fin',
        'agent_id',
        'structure_id',
        'active'
    ];

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class, 'agent_id', 'id');
    }

    // TODO Structure plus tard
    // public function structure(): BelongsTo { ... }
}
