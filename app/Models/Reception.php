<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reception extends Model
{
    use HasFactory;

    protected $fillable = [
        'vehicule_id',
        'created_by_id',
        'validated_by_id',
        'repaired_by_id',
        'date_arrivee',
        'motif_visite',
        'fiche_reception_vehicule',
        'statut'
    ];

    protected static function booted()
    {
        static::deleting(function ($reception) {
            $reception->vehicule()->delete();
        });
    }

    public function vehicule()
    {
        return $this->belongsTo(Vehicule::class);
    }

    public function creePar()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function validePar()
    {
        return $this->belongsTo(User::class, 'validated_by_id');
    }

    public function reparePar()
    {
        return $this->belongsTo(User::class, 'repaired_by_id');
    }

    // Alias rétrocompatibles
    public function gardien()
    {
        return $this->creePar();
    }

    public function user()
    {
        return $this->creePar();
    }

    public function secretaire()
    {
        return $this->validePar();
    }

    public function chefAtelier()
    {
        return $this->reparePar();
    }

    public function checkReception()
    {
        return $this->hasOne(CheckReception::class);
    }

    public function chrono()
    {
        return $this->hasOne(Chrono::class);
    }

    public function reparation()
    {
        return $this->hasOne(Reparation::class);
    }

    public function billetSortie()
    {
        return $this->hasOne(BilletSortie::class);
    }

    public function facture()
    {
        return $this->hasOne(Facture::class);
    }

    public static function getAllowedTransitions(): array
    {
        return [
            'attente' => ['validee', 'annulee'],
            'validee' => ['en_cours', 'terminee', 'annulee'],
            'en_cours' => ['terminee', 'annulee'],
            'terminee' => ['cloturee', 'sortie'],
            'annulee' => [],
            'cloturee' => [],
            'sortie' => [],
        ];
    }

    public function canTransitionTo(string $newStatus): bool
    {
        if ($this->statut === $newStatus) {
            return true;
        }
        $allowed = static::getAllowedTransitions()[$this->statut] ?? [];
        return in_array($newStatus, $allowed, true);
    }
}
