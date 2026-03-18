<?php

namespace Modules\Soins\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Soins\App\Models\ActeOperatoire;

class ActeOperatoirePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }

    public function terminer(User $user, ActeOperatoire $acteOperatoire): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('admin');
    }

    public function view(User $user, ActeOperatoire $acteOperatoire): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }
}