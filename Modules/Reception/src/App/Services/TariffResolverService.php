<?php

namespace Modules\Reception\App\Services;

use Illuminate\Support\Collection;
use Modules\Reception\App\Models\TariffItem;
use Modules\Reception\App\Models\TariffPlan;
use RuntimeException;

class TariffResolverService
{
    /**
     * ✅ Résoudre le TariffItem actif pour (planId + billableServiceId).
     * Lève une exception si introuvable.
     */
    public function resolveTariffItem(int $planId, int $billableServiceId): TariffItem
    {
        $tariffItem = TariffItem::query()
            ->where('tariff_plan_id', $planId)
            ->where('billable_service_id', $billableServiceId)
            ->where('active', true)
            ->whereHas('service', function ($q) {
                $q->where('active', true);
            })
            ->first();

        if (!$tariffItem) {
            throw new RuntimeException("Aucun tarif actif trouvé pour ce service dans ce plan.");
        }

        return $tariffItem;
    }

    /**
     * Variante: retourne null si introuvable.
     */
    public function tryResolveTariffItem(int $planId, int $billableServiceId): ?TariffItem
    {
        return TariffItem::query()
            ->where('tariff_plan_id', $planId)
            ->where('billable_service_id', $billableServiceId)
            ->where('active', true)
            ->whereHas('service', function ($q) {
                $q->where('active', true);
            })
            ->first();
    }

    /**
     * ✅ NOUVEAU : Résoudre un TariffItem actif pour (planId + categorie)
     * Utile si tu veux "un tarif par catégorie" (ex: CONSULTATION) au lieu d'un tarif par service.
     * Variante: retourne null si introuvable.
     */
    public function tryResolveTariffItemByCategory(int $planId, string $categorie): ?TariffItem
    {
        $cat = mb_strtolower(trim($categorie));

        return TariffItem::query()
            ->where('tariff_plan_id', $planId)
            ->where('active', true)
            ->whereHas('service', function ($q) use ($cat) {
                $q->where('active', true)
                  ->whereRaw('LOWER(categorie) = ?', [$cat]); // ✅ insensible à la casse
            })
            ->orderBy('id')
            ->first();
    }

    /**
     * ✅ NOUVEAU : idem mais throw si introuvable
     */
    public function resolveTariffItemByCategory(int $planId, string $categorie): TariffItem
    {
        $item = $this->tryResolveTariffItemByCategory($planId, $categorie);

        if (!$item) {
            throw new RuntimeException("Aucun tarif actif trouvé pour cette catégorie dans ce plan.");
        }

        return $item;
    }

    /**
     * ✅ Lister les services disponibles pour UNE catégorie + plan
     * GET /api/v1/reception/tariffs/services?categorie=...&plan_id=...
     */
    public function listServicesByCategoryAndPlan(string $categorie, int $planId): Collection
    {
        $cat = mb_strtolower(trim($categorie));

        return TariffItem::query()
            ->with('service:id,code,libelle,categorie,active,rendez_vous_obligatoire,necessite_medecin,paiement_obligatoire_avant_prestation')
            ->where('tariff_plan_id', $planId)
            ->where('active', true)
            ->whereHas('service', function ($q) use ($cat) {
                $q->where('active', true)
                  ->whereRaw('LOWER(categorie) = ?', [$cat]); // ✅ insensible à la casse
            })
            ->orderBy('id')
            ->get()
            ->map(fn (TariffItem $item) => $this->mapTariffItemForFront($item));
    }

    /**
     * ✅ Lister toutes les prestations (tariff_items) d'un plan,
     * avec filtre catégorie optionnel.
     *
     * GET /api/v1/reception/tariffs/services?plan_id=1
     * GET /api/v1/reception/tariffs/services?plan_id=1&categorie=laboratoire
     */
    public function listServicesByPlan(int $planId, ?string $categorie = null): Collection
    {
        $cat = !empty($categorie) ? mb_strtolower(trim((string) $categorie)) : null;

        return TariffItem::query()
            ->with('service:id,code,libelle,categorie,active,rendez_vous_obligatoire,necessite_medecin,paiement_obligatoire_avant_prestation')
            ->where('tariff_plan_id', $planId)
            ->where('active', true)
            ->whereHas('service', function ($q) use ($cat) {
                $q->where('active', true);
                if (!empty($cat)) {
                    $q->whereRaw('LOWER(categorie) = ?', [$cat]); // ✅ insensible à la casse
                }
            })
            ->orderBy('id')
            ->get()
            ->map(fn (TariffItem $item) => $this->mapTariffItemForFront($item));
    }

    /**
     * ✅ Lister les plans tarifaires actifs
     * GET /api/v1/reception/tariffs/plans
     */
    public function listPlans(): Collection
    {
        return TariffPlan::query()
            ->where('active', true)
            ->orderBy('nom')
            ->get(['id', 'nom', 'type', 'description', 'active', 'paiement_obligatoire'])
            ->map(fn ($p) => [
                'id' => $p->id,
                'nom' => $p->nom,
                'type' => $p->type,
                'description' => $p->description,
                'active' => (bool) $p->active,
                'paiement_obligatoire' => (bool) $p->paiement_obligatoire,
            ]);
    }

    /**
     * ✅ Vérifier qu'un tariff_item est actif (et que le service est actif).
     */
    public function isTariffItemActive(int $tariffItemId): bool
    {
        return TariffItem::query()
            ->whereKey($tariffItemId)
            ->where('active', true)
            ->whereHas('service', function ($q) {
                $q->where('active', true);
            })
            ->exists();
    }

    /**
     * Mapper un TariffItem vers le format front (cohérent RegisterController).
     */
    private function mapTariffItemForFront(TariffItem $item): array
    {
        $svc = $item->service;

        return [
            'tariff_item_id' => $item->id,
            'service_id'     => $item->billable_service_id,

            'code'           => $svc?->code,
            'libelle'        => $svc?->libelle,
            'name'           => $svc?->libelle,
            'categorie'      => $svc?->categorie,

            'prix_unitaire'  => $item->prix_unitaire,

            'requires_appointment' => (bool) ($svc?->rendez_vous_obligatoire ?? false),
            'requires_doctor'      => (bool) ($svc?->necessite_medecin ?? false),
            'pay_before_service'   => (bool) ($svc?->paiement_obligatoire_avant_prestation ?? false),
        ];
    }
}