# Documentation : GenericRequest

Le `GenericRequest` est une classe de validation Laravel (`FormRequest`) conçue de manière générique pour s'adapter dynamiquement à n'importe quel modèle (entité) de l'application.

## Fichier

- `app/Http/Requests/GenericRequest.php`

## Avantages

- **Un seul fichier de requête HTTP :** Vous évite de créer un `UserRequest`, `PostRequest`, `CommentRequest`, etc. 
- **Centralisation des règles :** Vous définissez la logique de validation de vos données au plus près du Modèle.
- **Auto-détection :** Ce composant détecte automatiquement le modèle concerné via le l'URL ou le nom de la route, permettant une intégration immédiate dans n'importe quel contrôleur.

## Fonctionnement

1. Vous injectez `GenericRequest` dans la méthode du contrôleur.
2. Le `GenericRequest` analyse la route (exécution d'une requête sur `api/users` ou de la route nommée `users.store`) pour déduire le nom du modèle ciblé (ex: `User`).
3. Il vérifie si le Modèle possède une méthode statique `validationRules()`.
4. Si c'est le cas, il récupère les règles (y compris en gérant les modifications `PUT` en transmettant l'ID courant).

## Comment l'utiliser ?

### 1. Du côté du Modèle

Ajoutez une méthode `validationRules()` (publique et statique) dans n'importe quel modèle Eloquent. Vous pouvez y paramétrer des règles différentes selon la méthode HTTP (`POST` ou `PUT/PATCH`) ainsi que récupérer facilement un `$id` pour éviter les conflits `unique:` lors d'une mise à jour.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    // ...

    /**
     * Définir les règles de validation ici !
     */
    public static function validationRules(string $method, $id = null): array
    {
        if ($method === 'POST') {
            return [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:8',
            ];
        }

        // Règles pour la modification (PUT/PATCH)
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:users,email,{$id}",
        ];
    }
}
```

*Optionnel :* Vous pouvez aussi définir `public static function validationMessages(): array` pour personnaliser les messages d'erreurs, la classe GenericRequest les utilisera le cas échéant.

### 2. Du côté du Contrôleur

Utilisez simplement `GenericRequest` à la place de `Request` classique. 
Oubliez le `$request->validate([...])` : les requêtes non valides se verront automatiquement éjectées en retournant une erreur `422 Unprocessable Entity` avant même de rentrer dans la méthode.

Il vous suffit ensuite d'appeler `$request->validated()` pour récupérer vos données certifiées conformes.

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GenericRequest;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function store(GenericRequest $request): JsonResponse
    {
        // Si le script arrive ici, les règles 'POST' du Modèle 'User' ont été passées !
        $validatedData = $request->validated();
        
        $user = User::create($validatedData);

        return response()->json($user, 201);
    }

    public function update(GenericRequest $request, $id): JsonResponse
    {
        // Si le script arrive ici, les règles 'PUT' du Modèle 'User' ont été passées !
        $validatedData = $request->validated();
        
        $user = clone User::findOrFail($id);
        $user->update($validatedData);

        return response()->json($user);
    }
}
```

## Bonus

Ce sytème fonctionne pour n'importe quelle entité tant que la route respecte les standards REST (ex: `api/posts`, `posts.store` appelle `Post::validationRules()`, etc.).
