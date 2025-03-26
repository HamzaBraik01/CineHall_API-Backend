<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Routes d'authentification (Publiques et Protégées)
Route::group([
    'middleware' => 'api', // S'assure que les requêtes sont traitées comme des appels API
    'prefix' => 'auth' // Préfixe pour toutes les routes d'authentification -> /api/auth/*
], function ($router) {
    // Routes publiques
    Route::post('register', [AuthController::class, 'register'])->name('api.register');
    Route::post('login', [AuthController::class, 'login'])->name('api.login');

    // Routes nécessitant une authentification JWT (middleware 'auth:api' appliqué dans le constructeur du contrôleur)
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.refresh');
    Route::get('me', [AuthController::class, 'me'])->name('api.me'); // Récupérer l'utilisateur courant

    // Optionnel : Routes pour l'authentification sociale
    // Route::get('{provider}/redirect', [AuthController::class, 'redirectToProvider'])->name('social.redirect');
    // Route::get('{provider}/callback', [AuthController::class, 'handleProviderCallback'])->name('social.callback');
});

// Routes de gestion du profil utilisateur (Protégées)
Route::group([
    'middleware' => 'api', // Applique le guard 'api' (configuré pour JWT)
    'prefix' => 'user' // Préfixe -> /api/user/*
], function ($router) {
     // Le middleware 'auth:api' est appliqué dans le constructeur de UserController
    Route::get('profile', [UserController::class, 'profile'])->name('api.user.profile'); // Ou utiliser /api/auth/me
    Route::put('profile', [UserController::class, 'updateProfile'])->name('api.user.updateProfile'); // Utiliser PUT ou PATCH
    Route::patch('profile', [UserController::class, 'updateProfile']); // Route alternative avec PATCH
    Route::delete('account', [UserController::class, 'deleteAccount'])->name('api.user.deleteAccount');
});

// Ajoutez ici les autres routes pour les Films, Séances, Salles, Réservations, etc.
// Exemple:
// Route::apiResource('films', FilmController::class)->middleware('auth:api'); // CRUD pour les films (protégé)
// Route::get('films/public', [FilmController::class, 'indexPublic']); // Liste publique des films