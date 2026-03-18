<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\AuthServiceProvider::class,
    Modules\Pharmacie\PharmacieServiceProvider::class,
    Modules\Laboratoire\App\Providers\LaboratoireServiceProvider::class,
    Modules\Imagerie\App\Providers\ImagerieServiceProvider::class,
];
