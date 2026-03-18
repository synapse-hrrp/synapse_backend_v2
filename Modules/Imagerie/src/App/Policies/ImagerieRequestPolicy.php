<?php

namespace Modules\Imagerie\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Imagerie\App\Models\ImagerieRequest;

class ImagerieRequestPolicy
{
    public function create(User $user): bool
    {
        return $user->hasRole('reception')
            || $user->hasRole('admin');
    }

    public function viewWorklist(User $user): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('admin');
    }

    public function cancel(User $user, ImagerieRequest $request): bool
    {
        return ($user->hasRole('reception') || $user->hasRole('admin'))
            && $request->status === 'pending_payment';
    }
}