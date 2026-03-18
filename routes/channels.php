<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Channels publics worklist (accessibles à tous les authentifiés)
Broadcast::channel('worklist.{module}', function ($user, $module) {
    // Vérifier que l'utilisateur a le bon rôle selon le module
    $rolesAutorises = match($module) {
        'laboratoire' => ['laborantin'],
        'soins'       => ['medecin', 'infirmier'],
        'imagerie'    => ['medecin', 'technicien_imagerie'],
        default       => [],
    };

    return $user->roles()
        ->whereIn('label', $rolesAutorises)
        ->exists();
});