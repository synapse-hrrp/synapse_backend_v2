<?php

namespace Modules\Laboratoire\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamenType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nom',
        'code',
        'categorie',
        'delai_heures',
        'instructions',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    // Un type d'examen a plusieurs demandes
    public function examenRequests(): HasMany
    {
        return $this->hasMany(ExamenRequest::class);
    }

    // Un type d'examen a plusieurs paramètres de référence
    public function parametres(): HasMany
    {
        return $this->hasMany(ExamenParametre::class);
    }

    public function scopeActifs($query)
    {
        return $query->where('active', true);
    }
}