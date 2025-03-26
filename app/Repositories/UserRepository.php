<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    public function all(): Collection
    {
        return User::all();
    }

    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function create(array $data): User
    {
        // S'assurer que le mot de passe est hashé si présent
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }

        // S'assurer que le mot de passe est hashé s'il est mis à jour
        if (isset($data['password'])) {
             // Vérifier si le nouveau mot de passe est fourni et non vide
            if(!empty($data['password'])) {
                $data['password'] = Hash::make($data['password']);
            } else {
                // Si le champ password est présent mais vide, le retirer pour ne pas écraser avec null/vide
                unset($data['password']);
            }
        }

        return $user->update($data);
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) {
            return false;
        }
        return $user->delete();
    }
}