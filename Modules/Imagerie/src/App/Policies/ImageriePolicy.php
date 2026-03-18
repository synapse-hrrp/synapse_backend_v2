<?php

namespace Modules\Imagerie\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Imagerie\App\Models\Imagerie;

class ImageriePolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }

    public function terminer(User $user, Imagerie $imagerie): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('admin');
    }

    public function view(User $user, Imagerie $imagerie): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }
}