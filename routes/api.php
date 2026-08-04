<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\MecanicienController;
use App\Http\Controllers\ReceptionController;
use App\Http\Controllers\ChronoController;
use App\Http\Controllers\ReparationController;
use App\Http\Controllers\BilletSortieController;
use App\Http\Controllers\FactureController;
use App\Http\Controllers\VehiculeController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\OutilController;
use App\Http\Controllers\PretOutilController;
Route::middleware('auth:sanctum')->get('/user', fn(Request $request) => response()->json(['user' => $request->user()]));

// Définit une route POST pour l'endpoint '/login'.
// Lorsque cette route est appelée, elle exécute la fonction 'login' du UserController.
Route::post('login', [UserController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    // -----------------------------------------------
    // -------------   Dashboard   ----------------------
    // -----------------------------------------------
    // Définit une route GET pour l'endpoint '/dashboard_stats'.
    // Lorsque cette route est appelée, elle exécute la fonction 'index' du DashboardController.
    Route::get('/dashboard_stats', [DashboardController::class, 'index']);


    // -----------------------------------------------
    // -------------   Users   ----------------------
    // -----------------------------------------------

    // Définit une route POST pour l'endpoint '/logout'.
    // Lorsque cette route est appelée, elle exécute la fonction suivante.
    // Logout
    Route::post('/logout', [UserController::class, 'logout']);

    // Définit une route POST pour l'endpoint '/register'.
    // Lorsque cette route est appelée, elle exécute la fonction 'register' du UserController.
    Route::post('add_user', [UserController::class, 'addUser']);

    // Définit une route GET pour l'endpoint '/list'.
    // Lorsque cette route est appelée, elle exécute la fonction 'list' du UserController.
    Route::get('liste_user', [UserController::class, 'listeUser']);

    // Définit une route DELETE pour l'endpoint '/delete_user à qui est passé l'id de l'user'.
    // Lorsque cette route est appelée, elle exécute la fonction 'delete_user' du UserController.
    Route::delete('delete_user/{id}', [UserController::class, 'deleteUser']);

    // Définit une route GET pour l'endpoint '/user à qui est passé l'id de l'user '.
    // Lorsque cette route est appelée, elle exécute la fonction 'getuser' du UserController.
    Route::get('user/{id}', [UserController::class, 'getUser']);

    // Définit une route POST pour l'endpoint '/update_user à qui est passé l'id de l'user '.
    // Lorsque cette route est appelée, elle exécute la fonction 'updateUser' du UserController.
    Route::post('update_user/{id}', [UserController::class, 'updateUser']);


    // ------------------------------------------------
    // -------------   Mécaniciens   ------------------
    // ------------------------------------------------

    // Définit une route POST pour l'endpoint '/add_mecanicien'.
    // Lorsque cette route est appelée, elle exécute la fonction 'store' du MecanicienController.
    Route::post('add_mecanicien', [MecanicienController::class, 'store']);

    // Définit une route GET pour l'endpoint '/liste_mecaniciens'.
    // Lorsque cette route est appelée, elle exécute la fonction 'index' du MecanicienController.
    Route::get('liste_mecaniciens', [MecanicienController::class, 'index']);

    // Définit une route GET pour l'endpoint '/mecanicien/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'show' du MecanicienController.
    Route::get('mecanicien/{id}', [MecanicienController::class, 'show']);

    // Outils actifs prêtés à un mécanicien
    Route::get('mecaniciens/{id}/outils-actifs', [MecanicienController::class, 'outilsActifs']);

    // Définit une route POST pour l'endpoint '/update_mecanicien/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'update' du MecanicienController.
    Route::post('update_mecanicien/{id}', [MecanicienController::class, 'update']);

    // Définit une route DELETE pour l'endpoint '/delete_mecanicien/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'destroy' du MecanicienController.
    Route::delete('delete_mecanicien/{id}', [MecanicienController::class, 'destroy']);


    // ---------------------------------------------------------------
    // --------------------   Receptions   --------------------------
    // ---------------------------------------------------------------
    // Définit une route POST pour l'endpoint '/add_reception'.
    // Lorsque cette route est appelée, elle exécute la fonction 'addReception' du ReceptionController.
    Route::post('add_reception', [ReceptionController::class, 'addReception']);
    // Définit une route GET pour l'endpoint '/liste_receptions'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeReceptions' du ReceptionController.
    Route::get('liste_receptions', [ReceptionController::class, 'listeReception']);
    // Définit une route GET pour l'endpoint '/reception/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'getReception' du ReceptionController.
    Route::get('reception/{id}', [ReceptionController::class, 'getReception']);
    // Définit une route POST pour l'endpoint '/update_reception/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'updateReception' du ReceptionController.
    Route::post('update_reception/{id}', [ReceptionController::class, 'updateReception']);
    // Définit une route DELETE pour l'endpoint '/delete_reception/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'deleteReception' du ReceptionController.
    Route::delete('delete_reception/{id}', [ReceptionController::class, 'deleteReception']);

    // ---------------------------------------------------------------
    // --------------------   Check Reception   --------------------------
    // Définit une route POST pour l'endpoint '/check/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'validerReception' du ReceptionController.
    Route::post('check/{id}', [ReceptionController::class, 'validerReception']);
    // Définit une route POST pour l'endpoint '/terminer/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'terminerReparation' du ReceptionController.
    Route::post('terminer/{id}', [ReceptionController::class, 'terminerReparation']);
    // ---------------------------------------------------------------
    // --------------------   Chronos   --------------------------
    // Définit une route POST pour l'endpoint '/pause_chrono/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'pauseChrono' du ChronoController.
    Route::post('/pause_chrono/{id}', [ChronoController::class, 'pauseChrono']);

    // Définit une route POST pour l'endpoint '/resume_chrono/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'resumeChrono' du ChronoController.
    Route::post('/resume_chrono/{id}', [ChronoController::class, 'resumeChrono']);

    // Définit une route GET pour l'endpoint '/liste_chronos'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeChronos' du ChronoController.
    Route::get('liste_chronos', [ChronoController::class, 'listeChronos']);
    // -------------------------------------------------
    // --------------------   Réparations   ------------------
    // Définit une route GET pour l'endpoint 'liste_reparations'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeReparations' du ReparationController.
    Route::get('liste_reparations', [ReparationController::class, 'listeReparations']);
    // -------------------------------------------------
    // --------------------   Billet de Sortie   ------------------
    // Définit une route POST pour l'endpoint '/generer_billet/{id}'.
    // Cette route exécute la fonction 'genererBilletSortie' du BilletSortieController.
    Route::post('generer_billet/{id}', [BilletSortieController::class, 'genererBilletSortie']);
    // Définit une route GET pour l'endpoint '/liste_billet_sortie'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeBilletSortie' du BilletSortieController.
    Route::get('liste_billet_sortie', [BilletSortieController::class, 'listeBilletSortie']);
    // ---------------------------------------------------------------
    // --------------------   Factures   --------------------------
    // Définit une route POST pour l'endpoint '/generer_facture/{id}'.
    // Cette route exécute la fonction 'genererFactureEtArreterChrono' du FactureController.
    Route::post('generer_facture/{id}', [FactureController::class, 'genererFactureEtArreterChrono']);

    // Définit une route GET pour l'endpoint '/liste_factures'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeFacture' du FactureController.
    Route::get('liste_factures', [FactureController::class, 'listeFacture']);

    // Définit une route POST pour l'endpoint '/valider_paiement/{id}'.
    // Lorsque cette route est appelée, elle exécute la fonction 'validerPaiement' du FactureController.
    Route::post('/valider_paiement/{id}', [FactureController::class, 'validerPaiement']);



    // ---------------------------------------------------------------
    // --------------------   Logs   --------------------------
    // ---------------------------------------------------------------
    // Définit une route GET pour l'endpoint '/logs'.
    // Lorsque cette route est appelée, elle retourne les logs associés aux utilisateurs
    Route::get('/logs', [LogController::class, 'index']);

    // -----------------------------------------------
    // -----------------   Vehicule   --------------------
    // -----------------------------------------------
    // Définit une route GET pour l'endpoint '/liste_vehicules'.
    // Lorsque cette route est appelée, elle exécute la fonction 'listeVehicule' du VehiculeController.
    Route::get('liste_vehicules', [VehiculeController::class, 'listeVehicule']);


    // -----------------------------------------------
    // -----------------   Settings   --------------------
    // -----------------------------------------------
    // Définit une route POST pour l'endpoint '/settings/tarif-horaire'.
    // Lorsque cette route est appelée, elle exécute la fonction 'updateTarifHoraire' du SettingController.
    Route::post('/settings/tarif-horaire', [SettingController::class, 'updateTarifHoraire']);
    // Définit une route GET pour l'endpoint '/settings/tarif-horaire'.
    // Lorsque cette route est appelée, elle retourne le tarif horaire actuel.
    // Si le tarif horaire n'est pas défini, il retourne 1000 par défaut
    Route::get('/settings/tarif-horaire', fn() => response()->json([
        'tarif_horaire' => \App\Models\Setting::get('tarif_horaire', 1000)
    ]));


    // -----------------------------------------------
    // -----------------   Check Items   --------------------
    // Définit une route GET pour l'endpoint '/check-items'.
    // Lorsque cette route est appelée, elle retourne tous les items de vérification.
    Route::get('/check-items', function () {
        return \App\Models\CheckItem::all(); // Ou CheckItemResource si tu veux formater
    });

    // -----------------------------------------------
    // -----------------   Outils & Prets   ----------
    // -----------------------------------------------
    Route::get('/outils', [OutilController::class, 'index']);
    Route::post('/add_outil', [OutilController::class, 'store']);
    Route::post('/update_outil/{id}', [OutilController::class, 'update']);
    Route::delete('/delete_outil/{id}', [OutilController::class, 'destroy']);
    Route::get('/prets_outils', [PretOutilController::class, 'index']);
    Route::post('/prete_outil', [PretOutilController::class, 'preteOutil']);
    Route::post('/restitue_outil/{id}', [PretOutilController::class, 'restitueOutil']);
});


// -----------------------------------------------
// -----------------   Test   --------------------
// -----------------------------------------------
// Définit une route GET pour l'endpoint '/test'.
// Lorsque cette route est appelée, elle retourne un message JSON indiquant que l'API est en ligne.
// Cette route est utilisée pour vérifier si l'API fonctionne correctement.
Route::get('/test', fn() => response()->json(['message' => 'API en ligne 🎉']));

