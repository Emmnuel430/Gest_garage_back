<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\User;
use App\Models\Outil;
use App\Models\Mecanicien;
use App\Models\Vehicule;
use App\Models\Reception;
use App\Models\Reparation;
use App\Models\Chrono;
use App\Models\Facture;
use App\Models\PretOutil;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FacturationOutilsSpeciauxTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected Mecanicien $mecanicien;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::firstOrCreate(
            ['pseudo' => 'admintest'],
            [
                'first_name' => 'Admin',
                'last_name' => 'Test',
                'role' => 'admin',
                'password' => bcrypt('password123'),
            ]
        );

        $mecanicienUser = User::firstOrCreate(
            ['pseudo' => 'mecanotest'],
            [
                'first_name' => 'Moussa',
                'last_name' => 'Diallo',
                'role' => 'gardien',
                'password' => bcrypt('password123'),
            ]
        );

        $this->mecanicien = Mecanicien::firstOrCreate(
            ['user_id' => $mecanicienUser->id],
            [
                'nom' => 'Diallo',
                'prenom' => 'Moussa',
                'type' => 'interne',
                'contact' => '770001122',
                'contact_urgence' => '770003344',
            ]
        );
    }

    /**
     * Teste que les réglages tarifaires ne contiennent plus le prix d'entrée
     * et conservent le tarif horaire et jours supp.
     */
    public function test_settings_tarifs_does_not_contain_prix_entree()
    {
        $this->actingAs($this->admin);

        // GET /api/settings/tarifs
        $response = $this->getJson('/api/settings/tarifs');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'tarif_horaire',
                'tarif_jours_supp',
            ])
            ->assertJsonMissing(['prix_entree']);

        // POST /api/settings/tarifs
        $updateResponse = $this->postJson('/api/settings/tarifs', [
            'tarif_horaire' => 1500,
            'tarif_jours_supp' => 3000,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJson([
                'tarif_horaire' => 1500,
                'tarif_jours_supp' => 3000,
            ])
            ->assertJsonMissing(['prix_entree']);

        $this->assertEquals(1500, (int) Setting::get('tarif_horaire'));
        $this->assertEquals(3000, (int) Setting::get('tarif_jours_supp'));
    }

    /**
     * Teste l'ajout et la modification d'un outil avec ses propriétés spéciales (est_special, prix_par_heure).
     */
    public function test_outil_creation_and_update_with_special_fields()
    {
        $this->actingAs($this->admin);

        $ref = 'TEST_SPEC_' . uniqid();

        // Ajout d'un outil spécial avec tarif horaire
        $addResponse = $this->postJson('/api/add_outil', [
            'libelle' => 'Pont élévateur mobile',
            'reference' => $ref,
            'quantite' => 2,
            'est_special' => true,
            'prix_par_heure' => 2500,
        ]);

        $addResponse->assertStatus(201)
            ->assertJsonPath('outil.est_special', true)
            ->assertJsonPath('outil.prix_par_heure', 2500);

        $outilId = $addResponse->json('outil.id');
        $this->assertDatabaseHas('outils', [
            'id' => $outilId,
            'est_special' => 1,
            'prix_par_heure' => 2500,
        ]);

        // Mise à jour : désactivation du statut spécial
        $updateResponse = $this->postJson("/api/update_outil/{$outilId}", [
            'libelle' => 'Pont élévateur mobile modifié',
            'reference' => $ref,
            'quantite' => 3,
            'est_special' => false,
            'prix_par_heure' => 0,
        ]);

        $updateResponse->assertStatus(200)
            ->assertJsonPath('outil.est_special', false)
            ->assertJsonPath('outil.prix_par_heure', 0);

        $this->assertDatabaseHas('outils', [
            'id' => $outilId,
            'est_special' => 0,
            'prix_par_heure' => 0,
        ]);
    }

    /**
     * Teste le calcul de la facture sans prise en charge initiale
     * et avec facturation par heure de l'outil spécial selon le chrono du véhicule.
     */
    public function test_facturation_calculates_special_tools_per_vehicle_and_removes_prise_en_charge()
    {
        Storage::fake();
        $this->actingAs($this->admin);

        Setting::set('tarif_horaire', 1000);
        Setting::set('tarif_jours_supp', 2000);

        // 1. Créer véhicule, réception et réparation
        $immat = 'AA-' . rand(100, 999) . '-ZZ';
        $vehicule = Vehicule::create([
            'immatriculation' => $immat,
            'marque' => 'Toyota',
            'modele' => 'Corolla',
            'mecanicien_id' => $this->mecanicien->id,
        ]);

        $reception = Reception::create([
            'vehicule_id' => $vehicule->id,
            'created_by_id' => $this->admin->id,
            'validated_by_id' => $this->admin->id,
            'repaired_by_id' => $this->admin->id,
            'date_arrivee' => now()->subHours(2),
            'motif_visite' => 'Révision complète',
            'statut' => 'validee',
        ]);

        $reparation = Reparation::create([
            'reception_id' => $reception->id,
            'user_id' => $this->admin->id,
            'description' => 'Changement pièces',
            'statut' => 'en_cours',
        ]);

        // Chrono actif : ~110 minutes nettes (arrondi à 2 heures pleines facturées)
        $chrono = Chrono::create([
            'reception_id' => $reception->id,
            'start_time' => Carbon::now()->subMinutes(110),
            'statut' => 'en_cours',
            'temps_total_pause' => 0,
        ]);

        $facture = Facture::create([
            'reception_id' => $reception->id,
            'user_id' => $this->admin->id,
            'montant' => 0,
            'statut' => 'en_attente',
        ]);

        // 2. Créer un outil spécial facturé 3000 FCFA/h
        $outilSpecial = Outil::create([
            'libelle' => 'Valise Diagnostic Pro',
            'reference' => 'VAL-' . uniqid(),
            'quantite' => 5,
            'est_special' => true,
            'prix_par_heure' => 3000,
        ]);

        // Prêter cet outil pour cette réparation
        PretOutil::create([
            'outil_id' => $outilSpecial->id,
            'reparation_id' => $reparation->id,
            'mecanicien_id' => $this->mecanicien->id,
            'quantite' => 1,
            'quantite_pretee' => 1,
            'statut' => 'prete',
            'est_partage' => false,
        ]);

        // 3. Arrêter le chrono et générer la facture
        $response = $this->postJson("/api/generer_facture/{$reception->id}");

        $response->assertStatus(200);

        // Durée nette = 120 minutes = 2 heures
        // Main d'œuvre : 2h * 1000 FCFA = 2000 FCFA
        // Surcharge J+1 : 0 FCFA
        // Prise en charge : 0 FCFA (supprimée du système)
        // Outil spécial : 2h * 3000 FCFA * 1 = 6000 FCFA
        // Total attendu = 2000 + 6000 = 8000 FCFA
        $factureFraiche = $facture->fresh();
        $this->assertEquals(8000, (int) $factureFraiche->montant);
        $this->assertEquals('generee', $factureFraiche->statut);
        $this->assertNotNull($factureFraiche->recu);

        // Vérifier que le reçu PDF est bien généré
        Storage::assertExists('public/' . $factureFraiche->recu);
    }

    /**
     * Teste la facturation par véhicule lorsqu'un mécanicien a plusieurs véhicules en réparation
     * et utilise le même outil spécial sur chacun d'eux avec des chronos différents.
     */
    public function test_facturation_per_vehicle_with_shared_special_tool_and_different_chronos()
    {
        Storage::fake();
        $this->actingAs($this->admin);

        Setting::set('tarif_horaire', 1000);
        Setting::set('tarif_jours_supp', 2000);

        $outilSpecial = Outil::create([
            'libelle' => 'Scanner OBD Universel',
            'reference' => 'OBD-' . uniqid(),
            'quantite' => 3,
            'est_special' => true,
            'prix_par_heure' => 2000,
        ]);

        // --- Véhicule A : Chrono 1 heure (60 min) ---
        $vehiculeA = Vehicule::create([
            'immatriculation' => 'VEH-A-' . rand(100, 999),
            'marque' => 'Peugeot',
            'modele' => '208',
            'mecanicien_id' => $this->mecanicien->id,
        ]);
        $receptionA = Reception::create([
            'vehicule_id' => $vehiculeA->id,
            'created_by_id' => $this->admin->id,
            'validated_by_id' => $this->admin->id,
            'date_arrivee' => now()->subHour(),
            'motif_visite' => 'Panne A',
            'statut' => 'validee',
        ]);
        $reparationA = Reparation::create([
            'reception_id' => $receptionA->id,
            'user_id' => $this->admin->id,
            'description' => 'Réparation A',
            'statut' => 'en_cours',
        ]);
        Chrono::create([
            'reception_id' => $receptionA->id,
            'start_time' => Carbon::now()->subMinutes(50),
            'statut' => 'en_cours',
            'temps_total_pause' => 0,
        ]);
        $factureA = Facture::create([
            'reception_id' => $receptionA->id,
            'user_id' => $this->admin->id,
            'montant' => 0,
            'statut' => 'en_attente',
        ]);
        PretOutil::create([
            'outil_id' => $outilSpecial->id,
            'reparation_id' => $reparationA->id,
            'mecanicien_id' => $this->mecanicien->id,
            'quantite' => 1,
            'quantite_pretee' => 1,
            'statut' => 'prete',
            'est_partage' => true,
        ]);

        // --- Véhicule B : Chrono ~170 min (arrondi à 3 heures) ---
        $vehiculeB = Vehicule::create([
            'immatriculation' => 'VEH-B-' . rand(100, 999),
            'marque' => 'Renault',
            'modele' => 'Clio',
            'mecanicien_id' => $this->mecanicien->id,
        ]);
        $receptionB = Reception::create([
            'vehicule_id' => $vehiculeB->id,
            'created_by_id' => $this->admin->id,
            'validated_by_id' => $this->admin->id,
            'date_arrivee' => now()->subHours(3),
            'motif_visite' => 'Panne B',
            'statut' => 'validee',
        ]);
        $reparationB = Reparation::create([
            'reception_id' => $receptionB->id,
            'user_id' => $this->admin->id,
            'description' => 'Réparation B',
            'statut' => 'en_cours',
        ]);
        Chrono::create([
            'reception_id' => $receptionB->id,
            'start_time' => Carbon::now()->subMinutes(170),
            'statut' => 'en_cours',
            'temps_total_pause' => 0,
        ]);
        $factureB = Facture::create([
            'reception_id' => $receptionB->id,
            'user_id' => $this->admin->id,
            'montant' => 0,
            'statut' => 'en_attente',
        ]);
        PretOutil::create([
            'outil_id' => $outilSpecial->id,
            'reparation_id' => $reparationB->id,
            'mecanicien_id' => $this->mecanicien->id,
            'quantite' => 1,
            'quantite_pretee' => 1,
            'statut' => 'prete',
            'est_partage' => true,
        ]);

        // Génération facture Véhicule A
        $this->postJson("/api/generer_facture/{$receptionA->id}")->assertStatus(200);
        // Véhicule A : 1h MO (1000) + 1h Outil (2000) = 3000 FCFA
        $this->assertEquals(3000, (int) $factureA->fresh()->montant);

        // Génération facture Véhicule B
        $this->postJson("/api/generer_facture/{$receptionB->id}")->assertStatus(200);
        // Véhicule B : 3h MO (3000) + 3h Outil (6000) = 9000 FCFA
        $this->assertEquals(9000, (int) $factureB->fresh()->montant);
    }

    public function test_update_pret_can_affect_tool_to_second_vehicle_without_decrementing_stock(): void
    {
        $this->actingAs($this->admin);

        $outil = Outil::create([
            'libelle' => 'Scanner Diagnostic Partagé',
            'reference' => 'SCN-SHARED-01',
            'quantite' => 3,
            'statut' => 'disponible',
            'est_special' => true,
            'prix_par_heure' => 1500,
        ]);

        $vehicule1 = Vehicule::create([
            'immatriculation' => 'VEH-SHARE-1',
            'marque' => 'Toyota',
            'modele' => 'Corolla',
            'mecanicien_id' => $this->mecanicien->id,
        ]);
        $reception1 = Reception::create([
            'vehicule_id' => $vehicule1->id,
            'created_by_id' => $this->admin->id,
            'date_arrivee' => now(),
            'motif_visite' => 'Entretien',
            'statut' => 'validee',
        ]);
        $reparation1 = Reparation::create([
            'reception_id' => $reception1->id,
            'user_id' => $this->admin->id,
            'description' => 'Réparation 1',
            'statut' => 'en_cours',
        ]);

        // Prêt initial sur Véhicule 1 : Stock passe de 3 à 2
        $loanRes = $this->postJson('/api/prete_outil', [
            'mecanicien_id' => $this->mecanicien->id,
            'reparation_id' => $reparation1->id,
            'outil_id' => $outil->id,
            'quantite' => 1,
        ]);
        $loanRes->assertStatus(201);
        $this->assertEquals(2, $outil->fresh()->quantite);
        $pret1Id = $loanRes->json('pret.id');

        // Arrivée d'un 2ème véhicule pour ce même mécanicien
        $vehicule2 = Vehicule::create([
            'immatriculation' => 'VEH-SHARE-2',
            'marque' => 'Honda',
            'modele' => 'Civic',
            'mecanicien_id' => $this->mecanicien->id,
        ]);
        $reception2 = Reception::create([
            'vehicule_id' => $vehicule2->id,
            'created_by_id' => $this->admin->id,
            'date_arrivee' => now(),
            'motif_visite' => 'Diagnostic',
            'statut' => 'validee',
        ]);
        $reparation2 = Reparation::create([
            'reception_id' => $reception2->id,
            'user_id' => $this->admin->id,
            'description' => 'Réparation 2',
            'statut' => 'en_cours',
        ]);

        // Affecter / Partager l'outil déjà prêté sur le véhicule 2 via update_pret
        $updateRes = $this->putJson("/api/update_pret/{$pret1Id}", [
            'affecter_autre_reparation_id' => $reparation2->id,
        ]);
        $updateRes->assertStatus(200);

        // Le stock physique reste à 2 (aucun décrément supplémentaire)
        $this->assertEquals(2, $outil->fresh()->quantite);

        // Un prêt enfant a été créé sur Véhicule 2
        $pret2 = PretOutil::where('reparation_id', $reparation2->id)
            ->where('outil_id', $outil->id)
            ->first();
        $this->assertNotNull($pret2);
        $this->assertTrue((bool) $pret2->est_partage);
        $this->assertEquals($pret1Id, $pret2->parent_pret_id);
    }

    public function test_admin_is_authorized_to_terminer_reparation_gate(): void
    {
        $this->assertTrue($this->admin->can('terminer_reparation'));
        $this->assertTrue($this->admin->can('terminer-reparation'));
    }
}
