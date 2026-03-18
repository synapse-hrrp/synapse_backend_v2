<?php

namespace Modules\Users\App\Models;

use App\Models\Personne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Agent extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 't_agents';

    // ok pour démarrer vite
    protected $guarded = [];

    protected $hidden = [
        'deleted_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function personne(): BelongsTo
    {
        return $this->belongsTo(Personne::class, 'personne_id', 'id');
    }

    public function affectations(): HasMany
    {
        return $this->hasMany(AffectationAgent::class, 'agent_id', 'id');
    }

    public function activeAffectation()
    {
        return $this->affectations()->latest('date_debut')->first();
    }

    public function user(): HasOne
    {
        // ⚠️ assure-toi que ta colonne sur users est bien agents_id
        return $this->hasOne(User::class, 'agents_id', 'id');
    }
}
