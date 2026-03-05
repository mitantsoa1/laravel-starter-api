<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CrudService
{
    /**
     * L'instance du modèle concerné.
     *
     * @var Model
     */
    protected $model;

    /**
     * CrudService constructeur.
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Récupère toutes les données.
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return $this->model->with($relations)->get($columns);
    }

    /**
     * Récupère les données avec pagination.
     *
     * @param int $perPage
     * @param array $columns
     * @param array $relations
     * @return LengthAwarePaginator
     */
    public function paginate(int $perPage = 15, array $columns = ['*'], array $relations = []): LengthAwarePaginator
    {
        return $this->model->with($relations)->paginate($perPage, $columns);
    }

    /**
     * Trouve un enregistrement spécifique par son ID.
     *
     * @param int|string $id
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function find($id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model->with($relations)->findOrFail($id, $columns);
    }

    /**
     * Crée un nouvel enregistrement.
     *
     * @param array $data
     * @return Model
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Met à jour un enregistrement existant.
     *
     * @param int|string $id
     * @param array $data
     * @return Model
     */
    public function update($id, array $data): Model
    {
        $record = $this->find($id);
        $record->update($data);

        return $record;
    }

    /**
     * Supprime un enregistrement.
     *
     * @param int|string $id
     * @return bool|null
     */
    public function delete($id): ?bool
    {
        $record = $this->find($id);

        return $record->delete();
    }

    /**
     * Retourne le modèle utilisé.
     * 
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
