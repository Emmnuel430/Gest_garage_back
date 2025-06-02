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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


// ---------------------------------------------------------

// -----------------------------------------------
// -------------   Dashboard   ----------------------
// -----------------------------------------------
// Définit une route GET pour l'endpoint '/dashboard_stats'.
// Lorsque cette route est appelée, elle exécute la fonction 'index' du DashboardController.
Route::get('/dashboard_stats', [DashboardController::class, 'index']);


// -----------------------------------------------
// -------------   Users   ----------------------
// -----------------------------------------------
// Définit une route POST pour l'endpoint '/login'.
// Lorsque cette route est appelée, elle exécute la fonction 'login' du UserController.
Route::post('login', [UserController::class, 'login']);

// Définit une route POST pour l'endpoint '/logout'.
// Lorsque cette route est appelée, elle exécute la fonction suivante.
Route::post('/logout', function (Request $request) {
    $request->user()->currentAccessToken()->delete();
    return response()->json(['message' => 'Déconnecté avec succès']);
})->middleware('auth:sanctum');

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
// Définit une route POST pour l'endpoint '/stop_chrono/{id}'.
// Lorsque cette route est appelée, elle exécute la fonction 'stopChrono' du ChronoController.
Route::post('/stop_chrono/{id}', [ChronoController::class, 'stopChrono']);

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
// -----------------   Test   --------------------
// -----------------------------------------------
// Définit une route GET pour l'endpoint '/test'.
// Lorsque cette route est appelée, elle retourne un message JSON indiquant que l'API est en ligne.
// Cette route est utilisée pour vérifier si l'API fonctionne correctement.
Route::get('/test', function () {
    return response()->json(['message' => 'API en ligne 🎉']);
});