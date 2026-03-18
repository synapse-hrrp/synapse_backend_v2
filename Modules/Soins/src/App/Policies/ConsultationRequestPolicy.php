<?php

namespace Modules\Soins\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Soins\App\Models\ConsultationRequest;

class ConsultationRequestPolicy
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

    public function view(User $user, ConsultationRequest $request): bool
    {
        return $user->hasRole('medecin')
            || $user->hasRole('infirmier')
            || $user->hasRole('reception')
            || $user->hasRole('admin');
    }

    public function cancel(User $user, ConsultationRequest $request): bool
    {
        return ($user->hasRole('reception') || $user->hasRole('admin'))
            && $request->status === 'pending_payment';
    }
}