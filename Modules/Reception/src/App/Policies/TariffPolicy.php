<?php

namespace Modules\Reception\App\Policies;

use Modules\Users\App\Models\User;

class TariffPolicy
{
    /**
     * Voir les plans et services — tout le monde connecté
     */
    public function view(User $user): bool
    {
        return $user->hasRole('reception')
            || $user->hasRole('admin')
            || $user->hasRole('gestionnaire');
    }

    /**
     * Gérer les tarifs — Admin + Gestionnaire uniquement
     */
    public function manage(User $user): bool
    {
        return $user->hasRole('admin')
            || $user->hasRole('gestionnaire');
    }
}