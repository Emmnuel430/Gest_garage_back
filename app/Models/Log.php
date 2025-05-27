<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;
    protected $fillable = [
        'idUser',           // Identifiant de l'utilisateur associé au log
        'user_nom',        // Nom de l'utilisateur (copie du nom)
        'user_prenom',      // Prénom de l'utilisateur (copie du prénom)
        'user_pseudo',      // Pseudo ou email de l'utilisateur (selon l'usage)
        'user_doc',         // Date de création de l'utilisateur
        'user_role',        // Rôle de l'utilisateur (exemple : super-admin, secretaire; ...)
        'action',           // Action effectuée (exemple : création, suppression)
        'table_concernee',  // Nom de la table concernée par l'action
        'details',          // Détails supplémentaires sur l'action
        'created_at',       // Date et heure de création du log
    ];

    // Active la gestion automatique des timestamps par Eloquent
    public $timestamps = true;

    // Définition de la relation entre le log et l'utilisateur
    public function user()
    {
        // Un log appartient à un utilisateur (relation "belongsTo")
        return $this->belongsTo(User::class, 'idUser');
    }
}
