<?php

namespace Modules\Laboratoire\App\Policies;

use Modules\Users\App\Models\User;
use Modules\Laboratoire\App\Models\Examen;

class ExamenPolicy
{
    /**
     * Démarrer un examen — Laborantin uniquement
     */
    public function create(User $user): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('admin');
    }

    /**
     * Saisir les résultats — Laborantin uniquement
     */
    public function terminer(User $user, Examen $examen): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('admin');
    }

    /**
     * Valider les résultats — Laborantin + Médecin
     */
    public function valider(User $user, Examen $examen): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('medecin')
            || $user->hasRole('admin');
    }

    /**
     * Voir un examen
     */
    public function view(User $user, Examen $examen): bool
    {
        return $user->hasRole('laborantin')
            || $user->hasRole('medecin')
            || $user->hasRole('admin');
    }
}