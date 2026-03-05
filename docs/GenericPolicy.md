# Documentation : Generic Policy

La `GenericPolicy` est une classe d'autorisation globale (ou de secours) conçue pour valider toutes les entités (modèles) dans l'application qui n'ont pas encore leur propre Policy dédiée.

## Fichiers Modifiés

- `app/Policies/GenericPolicy.php` : Contient la logique d'autorisation par défaut.
- `app/Providers/AppServiceProvider.php` : Configure Laravel pour utiliser cette politique par défaut via `Gate::guessPolicyNamesUsing()`.

## Fonctionnement

Par défaut, Laravel cherche une classe de la forme `ModelPolicy` dans `app/Policies` (exemple : `UserPolicy` pour `User`).
Grâce à notre configuration, si Laravel ne trouve **pas** la Policy spécifique pour un modèle, il va alors se rabattre sur `GenericPolicy`.

### Méthodes disponibles

La `GenericPolicy` implémente les méthodes standards d'une Policy :
- `viewAny(User $user)` : L'utilisateur peut-il voir la liste ?
- `view(User $user, mixed $model)` : Peut-il voir une ressource précise ?
- `create(User $user)` : Peut-il créer une ressource ?
- `update(User $user, mixed $model)` : Peut-il modifier cette ressource ?
- `delete(User $user, mixed $model)` : Peut-il supprimer cette ressource ?
- `restore(User $user, mixed $model)` : Peut-il restaurer cette ressource ?
- `forceDelete(User $user, mixed $model)` : Peut-il supprimer définitivement cette ressource ?

**NB:** Le typage de `$model` en `mixed` permet à ces méthodes d'accepter n'importe quel objet de modèle Laravel. Par défaut, **elles retournent toutes `true`**, signifiant que toutes les actions sont autorisées s'il n'y a pas de politique stricte. Vous êtes libre d'adapter la logique avec vos propres règles (par exemple : vérifier un rôle, s'assurer que l'ID de l'utilisateur correspond au créateur du modèle, etc.).

### Super Administrateur (Optionnel)

La méthode `before()` est également disponible :
```php
public function before(User $user, string $ability): bool|null
{
    // if ($user->is_super_admin) return true;
    return null; // Autrement, poursuivre la vérification normale de la policy
}
```
Si vous permettez la gestion d'un rôle "Administrateur", cette fonction permet d'approuver toutes les actions avant même de vérifier la méthode dédiée (comme `update` ou `delete`).

## Comment l'utiliser dans un Controller ?

Puisque la policy "rattrape" tous les modèles sans policy définie, vous pouvez l'utiliser directement dans les contrôleurs exactement de la même façon que n'importe quelle autre policy classique.

```php
public function update(Request $request, Post $post)
{
    // Va appeler GenericPolicy@update si il n'y a pas de PostPolicy.
    $this->authorize('update', $post); 

    // Ou via la Gate :
    // Gate::authorize('update', $post);
    
    // Suite de la fonction...
}
```
