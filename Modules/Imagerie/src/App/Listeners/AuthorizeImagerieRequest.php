<?php

namespace Modules\Imagerie\App\Listeners;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Imagerie\App\Models\ImagerieRequest;
use Modules\Finance\App\Events\WorklistUpdated;

class AuthorizeImagerieRequest
{
    public function handle(BillableAuthorized $event): void
    {
        if ($event->sourceType !== 'imagerie_request') {
            return;
        }

        $request = ImagerieRequest::find($event->sourceId);

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