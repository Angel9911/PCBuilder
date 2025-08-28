<?php

namespace App\Private_lib\trait;

trait QueryFilterTrait
{
    protected function buildWhereClauseSql(
        string $idField,
        int    $id = 0,
        array  $filters = [],
        array  $selectedItems = [],
        string $alias = 't',
        array  &$params = []
    ): string
    {
        $conditions = [];

        if ($id > 0) {
            $conditions[] = "$alias.$idField = :id";
            $params['id'] = $id;
        }

        foreach ($filters as $field => $fieldValues) {
            if ($field === 'page') continue;

            $fieldValues = (array)$fieldValues;
            $placeholders = [];

            foreach ($fieldValues as $i => $value) {
                $key = "{$field}_{$i}";
                $placeholders[] = ":$key";
                $params[$key] = $value;
            }

            $conditions[] = "$alias.$field IN (" . implode(',', $placeholders) . ")";
        }

        if (!empty($selectedItems)) {
            $inClause = [];

            foreach ($selectedItems as $i => $item) {
                if (!isset($item[$idField])) continue;

                $key = "selected_id_$i";
                $inClause[] = ":$key";
                $params[$key] = $item[$idField];
            }

            if (!empty($inClause)) {
                $conditions[] = "$alias.$idField IN (" . implode(',', $inClause) . ")";
            }
        }

        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }

    protected function buildPaginationClauseSql(int $limit = 0, int $offset = 0): string
    {
        return $limit > 0 ? " LIMIT $limit OFFSET $offset" : '';
    }
}