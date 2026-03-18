<?php

namespace Modules\Reception\App\Observers;

use Modules\Reception\App\Models\DailyRegisterEntry;
use Modules\Users\App\Models\Agent;

// ✅ Labo
use Modules\Laboratoire\App\Services\LabRequestFactory;

// ✅ Soins
use Modules\Soins\App\Services\CareRequestFactory;

// ✅ Imagerie (AJOUT)
use Modules\Imagerie\App\Services\ImagerieRequestFactory;

class DailyRegisterEntryObserver
{
    public function creating(DailyRegisterEntry $entry): void
    {
        // Si déjà fourni, ne touche pas
        if (!empty($entry->id_agent_createur)) {
            return;
        }

        // Si aucun user connecté (ex: seed/cron), ne touche pas
        $user = auth()->user();
        if (!$user) {
            return;
        }

        $agentId = Agent::query()
            ->where('user_id', $user->id)
            ->value('id');

        if ($agentId) {
            $entry->id_agent_createur = $agentId;
        }
    }

    // ✅ dès que le registre est lié à la BillingRequest, créer les demandes (labo + soins + imagerie) en pending_payment
    public function updated(DailyRegisterEntry $entry): void
    {
        if ($entry->wasChanged('id_demande_paiement') && !empty($entry->id_demande_paiement)) {

            // 1) Labo
            app(LabRequestFactory::class)->createPendingForEntry($entry);

            // 2) Soins
            app(CareRequestFactory::class)->createPendingForEntry($entry);

            // 3) Imagerie (AJOUT)
            app(ImagerieRequestFactory::class)->createPendingForEntry($entry);
        }
    }
}