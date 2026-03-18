<?php

namespace Modules\Pharmacie\App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use OwenIt\Auditing\Contracts\Auditable;

class Produit extends Model implements Auditable
{
    use HasFactory;
    use \OwenIt\Auditing\Auditable;

    protected $auditDriver = 'database';
    protected $auditModel = PharmacieAudit::class;

    protected $fillable = [
        // Identification
        'code',
        'code_barre',
        
        // Dénomination
        'nom',
        'nom_commercial',
        'molecule',
        'description',
        
        // Classification
        'forme',
        'dosage',
        'fabricant_id',
        'categorie_id',
        
        // Gestion stock automatique
        'commande_automatique',
        'delai_livraison_jours',
        'derniere_commande_auto_at',
        
        // Système
        'actif',
    ];

    protected $casts = [
        'commande_automatique' => 'boolean',
        'delai_livraison_jours' => 'integer',
        'derniere_commande_auto_at' => 'datetime',
        'actif' => 'boolean',
    ];

    // ========================================
    // RELATIONS
    // ========================================

    /**
     * Fabricant du produit
     */
    public function fabricant(): BelongsTo
    {
        return $this->belongsTo(Fabricant::class);
    }

    /**
     * Catégorie thérapeutique
     */
    public function categorie(): BelongsTo
    {
        return $this->belongsTo(Categorie::class);
    }

    /**
     * Stocks du produit
     */
    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    /**
     * Seuils stocks par dépôt
     */
    public function seuilStocks(): HasMany
    {
        return $this->hasMany(SeuilStock::class);
    }

    /**
     * Consommations historiques
     */
    public function consommations(): HasMany
    {
        return $this->hasMany(ConsommationProduit::class);
    }

    /**
     * Lignes de commandes
     */
    public function ligneCommandes(): HasMany
    {
        return $this->hasMany(LigneCommande::class);
    }

    /**
     * Lignes de réceptions
     */
    public function ligneReceptions(): HasMany
    {
        return $this->hasMany(LigneReception::class);
    }

    /**
     * Lignes de ventes
     */
    public function ligneVentes(): HasMany
    {
        return $this->hasMany(LigneVente::class);
    }

    // ========================================
    // SCOPES (FILTRES)
    // ========================================

    /**
     * Scope pour produits actifs uniquement
     */
    public function scopeActif($query)
    {
        return $query->where('actif', true);
    }

    /**
     * Scope pour produits avec commande automatique activée
     */
    public function scopeCommandeAuto($query)
    {
        return $query->where('commande_automatique', true)
                     ->where('actif', true);
    }

    /**
     * Scope pour recherche par code-barres
     */
    public function scopeByCodeBarre($query, string $codeBarre)
    {
        return $query->where('code_barre', $codeBarre);
    }

    /**
     * Scope pour recherche par fabricant
     */
    public function scopeByFabricant($query, int $fabricantId)
    {
        return $query->where('fabricant_id', $fabricantId);
    }

    /**
     * Scope pour recherche par catégorie
     */
    public function scopeByCategorie($query, int $categorieId)
    {
        return $query->where('categorie_id', $categorieId);
    }

    // ========================================
    // MÉTHODES UTILITAIRES
    // ========================================

    /**
     * Calculer stock total tous dépôts confondus (non périmés)
     */
    public function stockTotal(): int
    {
        return $this->stocks()
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->sum('quantite');
    }

    /**
     * Calculer stock total par dépôt (non périmés)
     */
    public function stockParDepot(int $depotId): int
    {
        return $this->stocks()
            ->where('depot_id', $depotId)
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->sum('quantite');
    }

    /**
     * Obtenir prix d'achat moyen depuis les stocks actuels
     */
    public function prixAchatMoyen(): ?float
    {
        return $this->stocks()
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->avg('prix_achat');
    }

    /**
     * Obtenir dernier prix de vente depuis la dernière réception
     */
    public function dernierPrixVente(): ?float
    {
        return $this->ligneReceptions()
            ->latest('created_at')
            ->value('prix_vente_unitaire');
    }

    /**
     * Obtenir dernier prix d'achat depuis la dernière réception
     */
    public function dernierPrixAchat(): ?float
    {
        return $this->ligneReceptions()
            ->latest('created_at')
            ->value('prix_achat_unitaire_ht');
    }

    /**
     * Vérifier si le produit est généralement taxable (basé sur dernières réceptions)
     */
    public function estGeneralementTaxable(): bool
    {
        // Prend la TVA applicable sur la dernière réception
        return $this->ligneReceptions()
            ->latest('created_at')
            ->value('tva_applicable') ?? true;
    }

    /**
     * Obtenir dernière date de réception
     */
    public function derniereDateReception(): ?string
    {
        $ligneReception = $this->ligneReceptions()
            ->with('reception')
            ->latest('created_at')
            ->first();

        return $ligneReception?->reception?->date_reception?->format('Y-m-d');
    }

    /**
     * Vérifier si le produit est périmé (tous les lots périmés)
     */
    public function estPerime(): bool
    {
        return $this->stocks()
            ->where('quantite', '>', 0)
            ->where('date_peremption', '>=', now())
            ->count() === 0;
    }

    /**
     * Vérifier si le produit est en rupture de stock
     */
    public function estEnRupture(): bool
    {
        return $this->stockTotal() === 0;
    }

    /**
     * Rechercher un produit par code-barres (méthode statique)
     */
    public static function findByCodeBarre(string $codeBarre): ?self
    {
        return self::where('code_barre', $codeBarre)->first();
    }

    /**
     * Rechercher un produit par code interne (méthode statique)
     */
    public static function findByCode(string $code): ?self
    {
        return self::where('code', $code)->first();
    }

    /**
     * Obtenir nom complet du produit (avec dosage et forme)
     */
    public function getNomCompletAttribute(): string
    {
        $parts = [$this->nom];

        if ($this->dosage) {
            $parts[] = $this->dosage;
        }

        if ($this->forme) {
            $parts[] = "({$this->forme})";
        }

        return implode(' ', $parts);
    }

    /**
     * Obtenir identifiant commercial (nom commercial ou nom + fabricant)
     */
    public function getIdentifiantCommercialAttribute(): string
    {
        if ($this->nom_commercial) {
            return $this->nom_commercial;
        }

        $nom = $this->nom;
        
        if ($this->fabricant) {
            $nom .= " - {$this->fabricant->nom}";
        }

        return $nom;
    }
}