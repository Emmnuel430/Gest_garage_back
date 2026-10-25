<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

// 1. Canal personnel de l'utilisateur connecté
// Match avec receivesBroadcastNotificationsOn() défini dans User.php
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Canal de compatibilité par défaut Laravel
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// 2. Canal ciblé par rôle opérationnel
Broadcast::channel('garage.{garageId}.role.{role}', function ($user, $garageId, $role) {
    // L'administrateur a une vision transverse sur tous les canaux
    if ($user->role === 'admin') {
        return true;
    }
    return $user->role === $role;
});
