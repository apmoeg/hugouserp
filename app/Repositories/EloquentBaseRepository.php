<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Repositories\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

abstract class EloquentBaseRepository implements BaseRepositoryInterface
{
    public function __construct(
        protected Model $model,
    ) {}

    protected function query(): Builder
    {
        /** @var Builder $q */
        $q = $this->model->newQuery();

        return $q;
    }

    public function find(int $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    public function findOrFail(int $id, array $columns = ['*']): Model
    {
        return $this->query()->findOrFail($id, $columns);
    }

    public function create(array $data): Model
    {
        /** @var Model $model */
        $model = $this->model->newQuery()->create($data);

        return $model;
    }

    public function update(Model $model, array $data): Model
    {
        $model->fill($data);
        $model->save();

        return $model;
    }

    public function delete(Model $model): void
    {
        $model->delete();
    }

    public function paginate(int $perPage = 20, array $columns = ['*']): LengthAwarePaginator
    {
        /** @var LengthAwarePaginator $paginator */
        $paginator = $this->query()->paginate($perPage, $columns);

        return $paginator;
    }
}
