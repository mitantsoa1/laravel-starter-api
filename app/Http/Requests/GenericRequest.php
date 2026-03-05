<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenericRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // L'autorisation est généralement gérée par les Policies (ex: GenericPolicy).
        // On retourne true pour laisser le FormRequest passer le relais.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $modelClass = $this->guessModelClass();

        if ($modelClass && method_exists($modelClass, 'validationRules')) {
            // On extrait l'ID potentiel si c'est une modification (PUT/PATCH)
            $id = $this->extractId();

            return $modelClass::validationRules($this->method(), $id);
        }

        return []; // Pas de règles par défaut
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $modelClass = $this->guessModelClass();
        if ($modelClass && method_exists($modelClass, 'validationMessages')) {
            return $modelClass::validationMessages();
        }

        return parent::messages();
    }

    /**
     * Tente de deviner la classe du Modèle à partir du nom ou de l'URL de la route.
     * Ex: api/users -> User
     */
    protected function guessModelClass(): ?string
    {
        $resourceName = $this->guessResourceName();
        if (!$resourceName) return null;

        $modelName = \Illuminate\Support\Str::studly(\Illuminate\Support\Str::singular($resourceName));
        $modelClass = "App\\Models\\{$modelName}";

        return class_exists($modelClass) ? $modelClass : null;
    }

    /**
     * Extrait le nom principal de la ressource.
     */
    protected function guessResourceName(): ?string
    {
        if (!$this->route()) return null;

        // 1. Depuis le nom de route (ex: "users.store" ou "api.users.update" -> "users")
        $routeName = $this->route()->getName();
        if ($routeName) {
            $parts = explode('.', $routeName);
            return count($parts) > 1 && $parts[0] === 'api' ? $parts[1] : $parts[0];
        }

        // 2. Depuis l'URI (ex: "api/users" ou "api/users/{id}" -> prend "users")
        $uri = $this->route()->uri();
        if ($uri) {
            $parts = explode('/', $uri);
            foreach ($parts as $part) {
                if ($part !== 'api' && strpos($part, '{') === false) {
                    return $part;
                }
            }
        }

        return null;
    }

    /**
     * Extrait l'ID s'il est présent (utile pour ignorer l'enregistrement actuel lors de sa mise à jour).
     */
    protected function extractId(): mixed
    {
        if (!$this->route()) return null;

        $parameters = $this->route()->parameters();
        if (empty($parameters)) return null;

        $param = reset($parameters); // Récupère le premier paramètre de la route

        return $param instanceof \Illuminate\Database\Eloquent\Model ? $param->getKey() : $param;
    }
}
