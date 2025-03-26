<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateProfileRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth; // Ou utiliser auth() helper

class UserController extends Controller
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        // Protéger toutes les routes de ce contrôleur
        $this->middleware('auth:api');
    }

    /**
     * Récupère le profil de l'utilisateur authentifié.
     * GET /api/user/profile (ou /api/auth/me dans AuthController)
     * Note: La route /api/auth/me est déjà dans AuthController, on peut la laisser là.
     *       Si on veut une route dédiée /api/user/profile, on peut l'ajouter ici.
     */
    public function profile(): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }
        // Retourne les informations de l'utilisateur (les attributs cachés seront masqués par le modèle)
        return response()->json($user);
    }


    /**
     * Met à jour le profil de l'utilisateur authentifié.
     * PUT/PATCH /api/user/profile
     */
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        $validatedData = $request->validated();

         // Retirer les clés avec des valeurs null ou vides si on ne veut pas écraser avec null
        $updateData = array_filter($validatedData, function ($value) {
            // Garde la valeur si elle n'est pas null.
            // Attention: si 'password' est vide après validation (nullable), il sera retiré ici.
            // La logique de hashage dans le repository doit gérer le cas où 'password' n'est pas dans $updateData.
            return !is_null($value);
        });


        // Le hashage du mot de passe est géré dans le repository si 'password' est présent et non vide
        $updated = $this->userRepository->update($user->id, $updateData);

        if ($updated) {
            // Recharger l'utilisateur pour obtenir les données à jour
            $updatedUser = $this->userRepository->findById($user->id);
            return response()->json([
                'message' => 'Profil mis à jour avec succès.',
                'user' => $updatedUser // Retourner l'utilisateur mis à jour
            ]);
        } else {
            // Cela peut arriver si l'ID n'est pas trouvé (peu probable ici) ou si la mise à jour échoue pour une raison inconnue
            return response()->json(['error' => 'Échec de la mise à jour du profil.'], 500);
        }
    }

    /**
     * Supprime le compte de l'utilisateur authentifié.
     * DELETE /api/user/account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $user = auth('api')->user();
        if (!$user) {
            return response()->json(['error' => 'Utilisateur non trouvé.'], 404);
        }

        // Optionnel: Demander confirmation du mot de passe avant suppression
        /*
        if (!isset($request->password) || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Mot de passe incorrect.'], 403);
        }
        */

        // Déconnecter l'utilisateur avant de supprimer
        auth('api')->logout();

        // Supprimer l'utilisateur via le repository
        $deleted = $this->userRepository->delete($user->id);

        if ($deleted) {
            return response()->json(['message' => 'Compte supprimé avec succès.']);
        } else {
             // Peu probable si l'utilisateur existait juste avant
            return response()->json(['error' => 'Échec de la suppression du compte.'], 500);
        }
    }
}