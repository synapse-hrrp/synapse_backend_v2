<?php

namespace Modules\Users\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffectationUser extends Model
{
    use HasFactory;

    protected $table = 'affectation_users';

    protected $fillable = [
        'date_debut',
        'date_fin',
        'user_id',
        'structure_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    // TODO Structure plus tard
    // public function structure(): BelongsTo { ... }
}
