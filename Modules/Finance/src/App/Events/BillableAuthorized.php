<?php

namespace Modules\Finance\App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BillableAuthorized
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        // Type de la demande source
        // Ex: consultation_request, lab_request,
        //     accouchement_request, hospitalisation_request,
        //     acte_operatoire_request, imagerie_request
        public readonly string $sourceType,

        // ID de la demande source
        public readonly int $sourceId,

        // ID du patient concerné
        public readonly int $patientId,
    ) {}
}