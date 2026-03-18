<?php

namespace Modules\Reception\App\Services;

use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\DailyRegisterEntryItem;
use Modules\Reception\App\Models\TariffItem;
use RuntimeException;

class RegisterEntryItemService
{
    public function __construct(
        private readonly TariffResolverService $tariffs,
    ) {}

    public function addItem(int $entryId, int $tariffItemId, int $qty, ?string $notes = null): DailyRegisterEntryItem
    {
        if ($qty <= 0) {
            throw new RuntimeException("La quantité doit être supérieure à 0.");
        }

        return DB::transaction(function () use ($entryId, $tariffItemId, $qty, $notes) {
            $entry = DailyRegisterEntry::query()->lockForUpdate()->findOrFail($entryId);

            if ($entry->isLocked()) {
                throw new RuntimeException("Entrée verrouillée (fermée ou annulée).");
            }

            if (!$entry->tariff_plan_id) {
                throw new RuntimeException("Plan tarifaire manquant sur l'entrée registre.");
            }

            $tariffItem = TariffItem::query()
                ->with('service')
                ->whereKey($tariffItemId)
                ->where('active', true)
                ->first();

            if (!$tariffItem || !$tariffItem->service || !$tariffItem->service->active) {
                throw new RuntimeException("Tarif introuvable/inactif ou service inactif.");
            }

            if ((int)$tariffItem->tariff_plan_id !== (int)$entry->tariff_plan_id) {
                throw new RuntimeException("Le tarif sélectionné ne correspond pas au plan de cette visite.");
            }

            [$ok, $message] = $entry->canAddService($tariffItem->service);
            if (!$ok) {
                throw new RuntimeException($message ?? "Impossible d'ajouter ce service.");
            }

            return DailyRegisterEntryItem::query()->create([
                'id_entree_journal'    => $entry->id,
                'billable_service_id'  => $tariffItem->billable_service_id,
                'tariff_item_id'       => $tariffItem->id,
                'quantite'             => $qty,
                'prix_unitaire'        => $tariffItem->prix_unitaire,
                'remarques'            => $notes,
                // total_ligne sera calculé automatiquement par booted()
            ]);
        });
    }

    public function addItemCompat(
        int $entryId,
        ?int $tariffItemId,
        ?int $billableServiceId,
        int $qty = 1,
        ?string $notes = null
    ): DailyRegisterEntryItem {
        if ($tariffItemId) {
            return $this->addItem($entryId, (int)$tariffItemId, $qty, $notes);
        }

        if (!$billableServiceId) {
            throw new RuntimeException("Il faut fournir tariff_item_id ou service_id.");
        }

        return DB::transaction(function () use ($entryId, $billableServiceId, $qty, $notes) {
            $entry = DailyRegisterEntry::query()->lockForUpdate()->findOrFail($entryId);

            if ($entry->isLocked()) {
                throw new RuntimeException("Entrée verrouillée (fermée ou annulée).");
            }

            if (!$entry->tariff_plan_id) {
                throw new RuntimeException("Plan tarifaire manquant sur l'entrée registre.");
            }

            $tariffItem = $this->tariffs->tryResolveTariffItem(
                planId: (int)$entry->tariff_plan_id,
                billableServiceId: (int)$billableServiceId
            );

            if (!$tariffItem) {
                throw new RuntimeException("Aucun tarif actif trouvé pour ce service dans ce plan.");
            }

            return $this->addItem($entryId, $tariffItem->id, $qty, $notes);
        });
    }
}