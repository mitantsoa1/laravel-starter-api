<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class PasswordValidationService
{
    /**
     * Retourne les règles de validation du mot de passe.
     */
    public static function rules(bool $confirmed = true): array
    {
        $rule = Password::min(8)
            ->mixedCase()   // au moins une majuscule et une minuscule
            ->numbers()     // au moins un chiffre
            ->symbols();    // au moins un caractère spécial

        $rules = ['required', 'string', $rule];

        if ($confirmed) {
            $rules[] = 'confirmed';
        }

        return $rules;
    }

    /**
     * Valide un mot de passe directement et retourne les erreurs éventuelles.
     */
    public static function validate(string $password, bool $confirmed = true, ?string $passwordConfirmation = null): array
    {
        $data = ['password' => $password];
        if ($confirmed) {
            $data['password_confirmation'] = $passwordConfirmation;
        }

        $validator = Validator::make($data, [
            'password' => self::rules($confirmed),
        ]);

        return $validator->errors()->all();
    }
}
