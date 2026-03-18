<?php

namespace Modules\Soins\App\Listeners;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Soins\App\Models\HospitalisationRequest;
use Modules\Finance\App\Events\WorklistUpdated;

class AuthorizeHospitalisationRequest
{
    public function handle(BillableAuthorized $event): void
    {
        if ($event->sourceType !== 'hospitalisation_request') {
            return;
        }

        $request = HospitalisationRequest::find($event->sourceId);

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