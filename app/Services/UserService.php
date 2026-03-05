<?php

namespace App\Services;

use App\Models\User;

class UserService extends CrudService
{
    /**
     * UserService constructeur.
     *
     * @param User $user
     */
    public function __construct(User $user)
    {
        parent::__construct($user);
    }

    // Vous pouvez rajouter vos méthodes spécifiques à "User" ici plus tard
}
