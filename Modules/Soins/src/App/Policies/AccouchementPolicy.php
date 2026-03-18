<?php

namespace Modules\Soins\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Soins\App\Models\Accouchement;

class AccouchementPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('sage_femme')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }

    public function terminer(User $user, Accouchement $accouchement): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('sage_femme')
            || $user->hasRole('admin');
    }

    public function view(User $user, Accouchement $accouchement): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('sage_femme')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }
}