<?php

namespace Modules\Soins\App\Policies;

use App\Models\User;
use Modules\Soins\App\Models\PansementRequest;

class PansementRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('soins.pansement.create');
    }

    public function viewWorklist(User $user): bool
    {
        return $user->hasPermissionTo('soins.pansement.worklist');
    }
}