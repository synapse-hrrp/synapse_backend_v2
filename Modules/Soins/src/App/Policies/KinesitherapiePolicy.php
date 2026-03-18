<?php

namespace Modules\Soins\App\Policies;

use App\Models\User;
use Modules\Soins\App\Models\Kinesitherapie;

class KinesitherapiePolicy
{
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('soins.kinesitherapie.create');
    }

    public function terminer(User $user, Kinesitherapie $kinesitherapie): bool
    {
        return $user->hasPermissionTo('soins.kinesitherapie.terminer');
    }
}