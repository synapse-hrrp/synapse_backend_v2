<?php

namespace Modules\Laboratoire\App\Listeners;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Finance\App\Events\WorklistUpdated;
use Modules\Laboratoire\App\Models\ExamenRequest;

class AuthorizeExamenRequest
{
    public function handle(BillableAuthorized $event): void
    {
        if ($event->sourceType !== 'examen_request') {
            return;
        }

        $request = ExamenRequest::find($event->sourceId);
        if (!$request) {
            return;
        }

        $request->update([
            'status'        => 'authorized',
            'authorized_at' => now(),
        ]);

        // Notifier la worklist du laboratoire
        broadcast(new WorklistUpdated(
            module:    'laboratoire',
            action:    'authorized',
            requestId: $request->id,
            patientId: $request->patient_id,
            isUrgent:  $request->is_urgent ?? false,
        ));
    }
}