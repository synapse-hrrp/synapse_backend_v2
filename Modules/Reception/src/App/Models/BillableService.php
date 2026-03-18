<?php

namespace Modules\Reception\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BillableService extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'libelle',
        'categorie',
        'description',
        'active',

        // ✅ Règles métier
        // RDV / médecin = service
        'rendez_vous_obligatoire',
        'necessite_medecin',

        // Optionnel : exception si certains actes doivent être payés avant quoi qu’il arrive
        'paiement_obligatoire_avant_prestation',
    ];

    protected $casts = [
        'active' => 'boolean',
        'rendez_vous_obligatoire' => 'boolean',
        'necessite_medecin' => 'boolean',
        'paiement_obligatoire_avant_prestation' => 'boolean',
    ];

    public function tariffItems(): HasMany
    {
        return $this->hasMany(TariffItem::class, 'billable_service_id');
    }

    // Scopes
    public function scopeActifs($query)
    {
        return $query->where('active', true);
    }

    public function scopeParCategorie($query, string $categorie)
    {
        return $query->where('categorie', $categorie);
    }

    public function scopeParCategorieActifs($query, string $categorie)
    {
        return $query->where('categorie', $categorie)->where('active', true);
    }

    // Accessors EN (compat API)
    public function getNameAttribute() { return $this->libelle; }
    public function getCategoryAttribute() { return $this->categorie; }

    public function getRequiresAppointmentAttribute()
    {
        return $this->rendez_vous_obligatoire;
    }

    public function getRequiresDoctorAttribute()
    {
        return $this->necessite_medecin;
    }

    // Exception (optionnelle)
    public function getPaymentRequiredBeforeServiceAttribute()
    {
        return $this->paiement_obligatoire_avant_prestation;
    }

    public function getIsActiveAttribute()
    {
        return $this->active;
    }
}