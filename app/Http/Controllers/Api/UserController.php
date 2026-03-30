<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\GenericRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use App\Services\LogService;

class UserController extends Controller
{
    protected $userService;

    /**
     * Injection de dépendance du UserService (qui hérite de CrudService)
     * 
     * @param UserService $userService
     * @param LogService $log
     */
    public function __construct(UserService $userService, private readonly LogService $log)
    {
        $this->userService = $userService;
    }

    /**
     * @OA\Get(
     *      path="/api/users",
     *      summary="Get list of users",
     *      tags={"Users"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(ref="#/components/schemas/User")
     *          )
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function index(): AnonymousResourceCollection
    {
        // On récupère les utilisateurs avec pagination (15 par page)
        $users = $this->userService->all();

        $this->log->action('user.index', ['count' => $users->count()]);

        return UserResource::collection($users);
    }

    /**
     * @OA\Post(
     *      path="/api/users",
     *      summary="Create a new user",
     *      tags={"Users"},
     *      security={{"bearerAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              required={"name","email","password","password_confirmation"},
     *              @OA\Property(property="name", type="string", example="New User"),
     *              @OA\Property(property="email", type="string", format="email", example="newuser@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="Secret@123"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="Secret@123")
     *          )
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="User created successfully",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function store(GenericRequest $request): UserResource
    {
        // La validation est gérée automatiquement par GenericRequest
        $validatedData = $request->validated();

        // Hasher le mot de passe avant la création
        $validatedData['password'] = bcrypt($validatedData['password']);

        // Création via notre service Hérité
        $user = $this->userService->create($validatedData);

        return new UserResource($user);
    }

    /**
     * @OA\Get(
     *      path="/api/users/{id}",
     *      summary="Get user by ID",
     *      tags={"Users"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Successful operation",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="User not found"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function show($id): UserResource
    {
        // Trouvera l'utilisateur ou lancera une exception 404 (non trouvé)
        $user = $this->userService->find($id);

        return new UserResource($user);
    }

    /**
     * @OA\Put(
     *      path="/api/users/{id}",
     *      summary="Update user by ID",
     *      tags={"Users"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          @OA\JsonContent(
     *              @OA\Property(property="name", type="string", example="Updated Name"),
     *              @OA\Property(property="email", type="string", format="email", example="updated@example.com"),
     *              @OA\Property(property="password", type="string", format="password", example="Secret@123", description="Optional: Only if you want to change the password"),
     *              @OA\Property(property="password_confirmation", type="string", format="password", example="Secret@123", description="Required if password is provided")
     *          )
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="User updated successfully",
     *          @OA\JsonContent(ref="#/components/schemas/User")
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="User not found"
     *      ),
     *      @OA\Response(
     *          response=422,
     *          description="Validation error"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function update(GenericRequest $request, $id): UserResource|JsonResponse
    {
        $validatedData = $request->validated();

        // Si on met à jour le mot de passe, penser à le hasher
        if (isset($validatedData['password'])) {
            $validatedData['password'] = bcrypt($validatedData['password']);
        }

        try {
            $user = $this->userService->update($id, $validatedData);
            $this->log->action('user.update', ['userId' => $id]);
            return new UserResource($user);
        } catch (\Exception $e) {
            $this->log->error('user.update', $e, ['userId' => $id]);
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Delete(
     *      path="/api/users/{id}",
     *      summary="Delete user by ID",
     *      tags={"Users"},
     *      security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          @OA\Schema(type="integer")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="User deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="User deleted successfully")
     *          )
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="User not found"
     *      ),
     *      @OA\Response(
     *          response=401,
     *          description="Unauthenticated"
     *      )
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            $this->log->action('user.delete', ['userId' => $id]);
        } catch (\Exception $e) {
            $this->log->error('user.delete', $e, ['userId' => $id]);
            return response()->json([
                'message' => $e->getMessage(),
            ], 500);
        }

        return response()->json(null, 204);
    }
}
