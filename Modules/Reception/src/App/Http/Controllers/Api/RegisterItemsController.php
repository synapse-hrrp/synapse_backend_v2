<?php

namespace Modules\Reception\App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Reception\App\Models\DailyRegisterEntryItem;
use Modules\Reception\App\Services\RegisterEntryItemService;

class RegisterItemsController extends Controller
{
    public function __construct(
        private readonly RegisterEntryItemService $items,
    ) {}

    public function store(Request $request, int $entryId)
    {
        $data = $request->validate([
            'tariff_item_id' => ['required', 'integer', 'exists:tariff_items,id'],
            'qty' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = DailyRegisterEntry::query()->findOrFail($entryId);

        if ($entry->isLocked()) {
            return response()->json(['message' => 'Entrée verrouillée (fermée/annulée).'], 422);
        }

        $item = DB::transaction(function () use ($entry, $data) {
            $item = $this->items->addItem(
                entryId: $entry->id,
                tariffItemId: (int) $data['tariff_item_id'],
                qty: (int) ($data['qty'] ?? 1),
                notes: $data['notes'] ?? null,
            );

            // ✅ Après ajout prestation: resync statut registre (au cas où total facture change)
            $entry->syncBillingAndStatus();

            return $item;
        });

        return response()->json([
            'message' => 'Prestation ajoutée',
            'data' => $item->fresh(['billableService', 'tariffItem']),
        ], 201);
    }

    public function destroy(int $entryId, int $itemId)
    {
        $entry = DailyRegisterEntry::query()->findOrFail($entryId);

        if ($entry->isLocked()) {
            return response()->json(['message' => 'Entrée verrouillée (fermée/annulée).'], 422);
        }

        DB::transaction(function () use ($entry, $entryId, $itemId) {
            $item = DailyRegisterEntryItem::query()
                ->where('id', $itemId)
                ->where('id_entree_journal', $entryId)
                ->firstOrFail();

            $item->delete();

            // ✅ Après suppression prestation: resync statut registre
            $entry->syncBillingAndStatus();
        });

        return response()->json([
            'message' => 'Prestation supprimée',
            'data' => ['id' => $itemId],
        ]);
    }
}