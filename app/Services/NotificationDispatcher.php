<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Throwable;

class NotificationDispatcher
{
    /**
     * Envoie une notification à tous les utilisateurs ayant les rôles spécifiés.
     * Résilience absolue : capture toute exception et la logge sans bloquer la transaction principale.
     *
     * @param array<string> $roles Liste des rôles destinataires (ex: ['admin', 'caisse'])
     * @param Notification $notification Instance de la notification à envoyer
     * @param int|null $exceptUserId ID optionnel d'un utilisateur à exclure (ex: l'auteur de l'action)
     */
    public static function sendToRoles(array $roles, Notification $notification, ?int $exceptUserId = null): void
    {
        try {
            $query = User::whereIn('role', $roles);

            if ($exceptUserId !== null) {
                $query->where('id', '!=', $exceptUserId);
            }

            $users = $query->get();

            if ($users->isNotEmpty()) {
                NotificationFacade::send($users, $notification);
            }
        } catch (Throwable $e) {
            Log::error("Erreur NotificationDispatcher::sendToRoles : " . $e->getMessage(), [
                'roles' => $roles,
                'notification' => get_class($notification),
                'exception' => $e->getTraceAsString(),
            ]);
        }
    }
}
