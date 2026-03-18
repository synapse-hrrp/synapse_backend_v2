<?php

namespace Modules\Soins\App\Policies;

use App\Models\User;
use Modules\Soins\App\Models\KinesitherapieRequest;

class KinesitherapieRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('soins.kinesitherapie.create');
    }

    public function viewWorklist(User $user): bool
    {
        return $user->hasPermissionTo('soins.kinesitherapie.worklist');
    }
}