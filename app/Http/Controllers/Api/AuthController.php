<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User; 

class AuthController extends Controller
{
    protected UserRepositoryInterface $userRepository;

    // Injection de dépendance du Repository
    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
        // Appliquer le middleware d'authentification JWT sauf pour login et register
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Enregistre un nouvel utilisateur.
     * POST /api/auth/register
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        // Les données sont déjà validées par RegisterRequest
        $validatedData = $request->validated();

        // Le hashage est géré dans le repository ou le modèle (via $casts)
        $user = $this->userRepository->create($validatedData);

        // Optionnel : Connecter l'utilisateur directement après l'inscription
        // $token = auth('api')->login($user);
        // return $this->respondWithToken($token);

        // Ou retourner juste un message de succès ou l'utilisateur créé (sans le mot de passe)
        return response()->json([
            'message' => 'Utilisateur enregistré avec succès!',
            'user' => $user->only(['id', 'name', 'email', 'created_at']) // Exclure le mot de passe
        ], 201); // 201 Created
    }

    /**
     * Connecte un utilisateur et retourne un token JWT.
     * POST /api/auth/login
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated(); // Récupère email et password validés

        // Tente d'authentifier l'utilisateur avec les credentials fournis
        // et de générer un token si succès
        if (! $token = auth('api')->attempt($credentials)) {
            // Si l'authentification échoue
            return response()->json(['error' => 'Non autorisé. Vérifiez vos identifiants.'], 401);
        }

        // Si l'authentification réussit, retourne le token et les infos utilisateur
        return $this->respondWithToken($token);
    }

    /**
     * Récupère l'utilisateur actuellement authentifié.
     * GET /api/auth/me
     */
    public function me(): JsonResponse
    {
         // auth('api')->user() retourne l'instance de l'utilisateur authentifié
        $user = auth('api')->user();
        if (!$user) {
             // Normalement impossible si le middleware 'auth:api' est bien appliqué
            return response()->json(['error' => 'Utilisateur non trouvé ou non authentifié.'], 404);
        }
        return response()->json($user);
    }

    /**
     * Déconnecte l'utilisateur (Invalide le token).
     * POST /api/auth/logout
     */
    public function logout(): JsonResponse
    {
        auth('api')->logout(); // Invalide le token actuel

        return response()->json(['message' => 'Déconnexion réussie']);
    }

    /**
     * Rafraîchit un token.
     * POST /api/auth/refresh
     */
    public function refresh(): JsonResponse
    {
        // Rafraîchit le token actuel et retourne un nouveau token
         try {
             $newToken = auth('api')->refresh();
             return $this->respondWithToken($newToken);
         } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
             return response()->json(['error' => 'Impossible de rafraîchir le token', 'details' => $e->getMessage()], 500);
         }
    }

    /**
     * Structure la réponse avec le token JWT.
     *
     * @param  string $token
     * @return \Illuminate\Http\JsonResponse
     */
    protected function respondWithToken(string $token): JsonResponse
    {
        $user = auth('api')->user(); // Récupère l'utilisateur associé au token

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
             // Le TTL est en minutes par défaut dans la config jwt.php
            'expires_in' => auth('api')->factory()->getTTL() * 60, // Convertir en secondes
             // Retourner aussi les informations de l'utilisateur (sans le mot de passe)
            'user' => $user ? $user->only(['id', 'name', 'email']) : null
        ]);
    }

    // --- Optionnel : Authentification via Réseaux Sociaux ---
    // Nécessite `composer require laravel/socialite` et configuration dans config/services.php

    /*
    public function redirectToProvider($provider) // ex: google, facebook
    {
        // Valider que le provider est supporté
        if (!in_array($provider, ['google', 'facebook', 'github'])) {
            return response()->json(['error' => 'Provider non supporté'], 400);
        }
         // Utiliser Socialite::driver($provider)->stateless()->redirect(); pour les API
        return Socialite::driver($provider)->stateless()->redirect();
    }

    public function handleProviderCallback($provider)
    {
         if (!in_array($provider, ['google', 'facebook', 'github'])) {
            return response()->json(['error' => 'Provider non supporté'], 400);
        }

        try {
             // Utiliser stateless() pour les API
            $socialUser = Socialite::driver($provider)->stateless()->user();

            // Vérifier si l'utilisateur existe déjà avec cet email
            $user = $this->userRepository->findByEmail($socialUser->getEmail());

            if ($user) {
                // L'utilisateur existe, le connecter
                // Optionnel: Mettre à jour les infos si nécessaire (nom, avatar...)
                // $this->userRepository->update($user->id, ['name' => $socialUser->getName()]);
            } else {
                // L'utilisateur n'existe pas, le créer
                $user = $this->userRepository->create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    // Pas de mot de passe local ou un mot de passe aléatoire sécurisé
                    'password' => null, // ou Hash::make(Str::random(16))
                    'email_verified_at' => now(), // Considérer l'email comme vérifié
                    // Stocker l'ID et le provider pour référence future si besoin
                    // 'provider_id' => $socialUser->getId(),
                    // 'provider_name' => $provider,
                ]);
            }

            // Générer un token JWT pour cet utilisateur
            $token = auth('api')->login($user);

            // Retourner le token (ou rediriger avec le token dans l'URL pour une SPA)
            return $this->respondWithToken($token);

        } catch (\Exception $e) {
            \Log::error("Erreur Socialite ($provider): " . $e->getMessage());
            return response()->json(['error' => 'Erreur lors de l\'authentification via ' . $provider], 500);
        }
    }
    */
}