<?php

namespace Modules\Soins\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Soins\App\Models\Hospitalisation;

class HospitalisationPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }

    public function terminer(User $user, Hospitalisation $hospitalisation): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('admin');
    }

    public function view(User $user, Hospitalisation $hospitalisation): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }
}