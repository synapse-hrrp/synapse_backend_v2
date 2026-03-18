<?php

namespace Modules\Soins\App\Listeners;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Soins\App\Models\ConsultationRequest;
use Modules\Finance\App\Events\WorklistUpdated;

class AuthorizeConsultationRequest
{
    public function handle(BillableAuthorized $event): void
    {
        if ($event->sourceType !== 'consultation_request') {
            return;
        }

        $request = ConsultationRequest::find($event->sourceId);

        if (!$request) {
            return;
        }

        $request->update([
            'status'        => 'authorized',
            'authorized_at' => now(),
        ]);


        broadcast(new WorklistUpdated(
            module:    'soins',
            action:    'authorized',
            requestId: $request->id,
            patientId: $request->patient_id,
            isUrgent:  $request->is_urgent ?? false,
        ));
    }
}