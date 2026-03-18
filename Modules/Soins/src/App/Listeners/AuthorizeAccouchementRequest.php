<?php

namespace Modules\Soins\App\Listeners;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Finance\App\Events\WorklistUpdated;
use Modules\Soins\App\Models\AccouchementRequest;

class AuthorizeAccouchementRequest
{
    public function handle(BillableAuthorized $event): void
    {
        if ($event->sourceType !== 'accouchement_request') {
            return;
        }

        $request = AccouchementRequest::find($event->sourceId);
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