<?php

namespace App\Http\Controllers\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Sortable
{

    protected function applySort(Builder $query, Request $request, array $columns, ?string $default = null, string $defaultDir = 'asc'): array
    {
        $sort = $request->get('sort', $default);
        $dir = $request->get('dir', $defaultDir) === 'desc' ? 'desc' : 'asc';

        if ($sort && array_key_exists($sort, $columns)) {
            $column = $columns[$sort];

            if ($column instanceof Closure) {
                $column($query, $dir);
            } else {
                $query->orderBy($column, $dir);
            }

            return [$sort, $dir];
        }

        return [null, $dir];
    }
}
