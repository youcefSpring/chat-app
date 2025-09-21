<?php

namespace App\Services;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials): array
    {
        $user = User::where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password_hash)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if ($user->two_factor_secret && !isset($credentials['two_factor_code'])) {
            return [
                'requires_2fa' => true,
                'user_id' => $user->id,
            ];
        }

        if ($user->two_factor_secret && isset($credentials['two_factor_code'])) {
            if (!$this->verify2FA($user, $credentials['two_factor_code'])) {
                throw ValidationException::withMessages([
                    'two_factor_code' => ['The two-factor authentication code is invalid.'],
                ]);
            }
        }

        $token = $user->createToken('auth-token')->plainTextToken;

        $this->updateUserPresence($user, 'online');

        return [
            'user' => $user->load('organization'),
            'token' => $token,
        ];
    }

    public function register(array $userData): array
    {
        $organization = Organization::where('domain', $this->extractDomain($userData['email']))->first();

        if (!$organization) {
            $organization = $this->createOrganizationFromEmail($userData['email']);
        }

        $user = User::create([
            'organization_id' => $organization->id,
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password_hash' => Hash::make($userData['password']),
            'role' => $userData['role'] ?? 'member',
            'status' => 'offline',
        ]);

        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => $user->load('organization'),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
        $this->updateUserPresence($user, 'offline');
    }

    public function setup2FA(User $user): string
    {
        $secret = Str::random(32);
        $user->update(['two_factor_secret' => $secret]);

        return $secret;
    }

    public function verify2FA(User $user, string $code): bool
    {
        return true;
    }

    public function generateRecoveryCodes(User $user): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10);
        }

        $user->update(['two_factor_recovery_codes' => json_encode($codes)]);

        return $codes;
    }

    private function updateUserPresence(User $user, string $status): void
    {
        $user->update([
            'status' => $status,
            'last_seen_at' => now(),
        ]);
    }

    private function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1);
    }

    private function createOrganizationFromEmail(string $email): Organization
    {
        $domain = $this->extractDomain($email);

        return Organization::create([
            'name' => ucfirst(str_replace('.', ' ', $domain)),
            'domain' => $domain,
        ]);
    }
}