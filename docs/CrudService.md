# Documentation : CrudService

Le `CrudService` est un service générique permettant d'abstraire la logique courante de base de données (Create, Read, Update, Delete) liée aux modèles Eloquent de l'application.

## Fichier

- `app/Services/CrudService.php`

## Avantages

- **Réutilisable** : Vous évite de réécrire le code de base du CRUD pour chaque ressource et chaque contrôleur.
- **Factorisation** : Centralise la logique d'accès à la base de données (couche de service) au lieu de l'encombrer dans les contrôleurs, ce qui rend le code plus propre et la maintenance beaucoup plus facile.

## Injecter un Modèle (Initialisation)

Le `CrudService` s'instancie en lui passant une instance de modèle (Eloquent `Model`). 

Dans le cas de création d'un Service dédié à un modèle (par exemple : `UserService`), l'approche recommandée consiste à faire un héritage en passant le modèle au contructeur parent :

**Exemple en étendant (héritage) :**
```php
<?php

namespace App\Services;

use App\Models\User;

class UserService extends CrudService
{
    public function __construct(User $user)
    {
        parent::__construct($user); // Injecte l'instance User au CrudService global
    }
    
    // Vous pouvez ensuite rajouter vos propres méthodes personnalisées pour les Users
}
```

## Méthodes disponibles

### 1. `all(array $columns = ['*'], array $relations = [])` : Collection
Récupère tous les enregistrements.
- **$columns** : Les colonnes à récupérer (par défaut : toutes).
- **$relations** : Les relations ("eager-loading") avec un `with()` (par défaut : aucune, vide).

*(Exemple : `$userService->all(['id', 'name'], ['posts']);`)*

### 2. `paginate(int $perPage = 15, array $columns = ['*'], array $relations = [])` : LengthAwarePaginator
Récupère les enregistrements avec un système de pagination.
- **$perPage** : Nombre d'items par page.

*(Exemple : `$userService->paginate(10);`)*

### 3. `find($id, array $columns = ['*'], array $relations = [])` : ?Model
Trouve un modèle selon son identifiant `$id` via un `findOrFail()`.
S'il ne le trouve pas, lève une exception (résultant souvent en erreur 404 dans l'API).

*(Exemple : `$userService->find(5, ['id', 'email']);`)*

### 4. `create(array $data)` : Model
Procède à la création d'un nouvel enregistrement.

*(Exemple : `$userService->create(['name' => 'John', 'email' => 'test@test.com']);`)*

### 5. `update($id, array $data)` : Model
Trouve l'enregistrement par son `$id` et le met à jour avec les `$data` fournies, avant de le retourner.

*(Exemple : `$userService->update(5, ['name' => 'John Doe']);`)*

### 6. `delete($id)` : ?bool
Trouve l'enregistrement par son `$id` et exécute la méthode de suppression `delete()`. Renvoie un booléen en cas de succès.

*(Exemple : `$userService->delete(5);`)*

### 7. `getModel()` : Model
Retourne simplement l'instance du modèle injecté (précédemment utilisé). Pratique si vous devez effectuer des requêtes complexes sans quitter le contexte du Service.
```php
public function mesUtilisateursActifs() {
    return $this->getModel()->where('active', true)->get();
}
```
