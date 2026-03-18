<?php

namespace Modules\Soins\App\Policies;

use App\Models\User;
use Modules\Soins\App\Models\Pansement;

class PansementPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('soins.pansement.create');
    }

    public function terminer(User $user, Pansement $pansement): bool
    {
        return $user->hasPermissionTo('soins.pansement.terminer');
    }
}