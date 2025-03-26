<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Important pour l'unicité lors de la mise à jour

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // La vérification se fait via le middleware d'authentification
    }

    public function rules(): array
    {
        $userId = $this->user()->id; // Récupère l'ID de l'utilisateur authentifié

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes', // Rendre le champ optionnel
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($userId), // Ignore l'utilisateur actuel lors de la vérification d'unicité
            ],
            // Ne pas rendre 'password' required, mais valider s'il est présent
            'password' => 'sometimes|nullable|string|min:8|confirmed',
             // Ajoutez d'autres champs si nécessaire (ex: phone_number)
             // 'phone_number' => 'sometimes|nullable|string|max:20',
        ];
    }
    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.unique' => 'Cet email est déjà utilisé par un autre compte.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.',
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
        ];
    }
}