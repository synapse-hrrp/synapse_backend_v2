<?php

namespace Modules\Soins\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Soins\App\Models\AccouchementRequest;

class AccouchementRequestPolicy
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

    public function cancel(User $user, AccouchementRequest $request): bool
    {
        return ($user->hasRole('reception') || $user->hasRole('admin'))
            && $request->status === 'pending_payment';
    }
}