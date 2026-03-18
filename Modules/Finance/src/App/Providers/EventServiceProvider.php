<?php

namespace Modules\Finance\App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

use Modules\Finance\App\Events\BillableAuthorized;
use Modules\Laboratoire\App\Listeners\AuthorizeExamenRequest;
use Modules\Soins\App\Listeners\AuthorizeConsultationRequest;
use Modules\Soins\App\Listeners\AuthorizeAccouchementRequest;
use Modules\Soins\App\Listeners\AuthorizeHospitalisationRequest;
use Modules\Soins\App\Listeners\AuthorizeActeOperatoireRequest;
use Modules\Imagerie\App\Listeners\AuthorizeImagerieRequest;

// ✅ Observer Paiement
use Modules\Finance\App\Models\FinancePayment;
use Modules\Finance\App\Observers\FinancePaymentObserver;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        // Un seul Event → tous les modules écoutent
        BillableAuthorized::class => [
            AuthorizeExamenRequest::class,
            AuthorizeConsultationRequest::class,
            AuthorizeAccouchementRequest::class,
            AuthorizeHospitalisationRequest::class,
            AuthorizeActeOperatoireRequest::class,
            AuthorizeImagerieRequest::class,
        ],
    ];

    public function boot(): void
    {
        parent::boot();

        // ✅ IMPORTANT : brancher l’observer
        FinancePayment::observe(FinancePaymentObserver::class);
    }
}