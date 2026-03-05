<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected $userService;

    /**
     * Injection de dépendance du UserService (qui hérite de CrudService)
     * 
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Afficher tous les utilisateurs.
     */
    public function index(): JsonResponse
    {
        // On récupère les utilisateurs avec pagination (15 par page)
        $users = $this->userService->paginate(15);

        return response()->json($users);
    }

    /**
     * Créer un nouvel utilisateur.
     */
    public function store(Request $request): JsonResponse
    {
        // Validation basique (un FormRequest serait idéal)
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        // Hasher le mot de passe avant la création
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Création via notre service Hérité
        $user = $this->userService->create($validatedData);

        return response()->json($user, 201);
    }

    /**
     * Afficher un utilisateur spécifique.
     */
    public function show($id): JsonResponse
    {
        // Trouvera l'utilisateur ou lancera une exception 404 (non trouvé)
        $user = $this->userService->find($id);

        return response()->json($user);
    }

    /**
     * Mettre à jour un utilisateur.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:8',
        ]);

        // Si on met à jour le mot de passe, penser à le hasher
        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        $user = $this->userService->update($id, $validatedData);

        return response()->json($user);
    }

    /**
     * Supprimer un utilisateur.
     */
    public function destroy($id): JsonResponse
    {
        $this->userService->delete($id);

        return response()->json(null, 204);
    }
}
