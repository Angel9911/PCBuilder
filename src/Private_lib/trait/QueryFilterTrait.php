<?php

namespace App\Private_lib\trait;

trait QueryFilterTrait
{
    /**
     * Build WHERE clause using a catalog config (ComponentCatalogFilter / PeripheryCatalogFilter)
     *
     * @param array $filtersCfg  The 'filters' array from catalog config for the current type
     * @param array $input       Raw request filters (GET/POST)
     * @param string $tableAlias The main table alias for this type (usually 't')
     * @param array $params      Will be filled with named params
     * @param string|null $idField Optional scope key name to exclude from filter loop (e.g. 'page')
     *
     * @return string WHERE ... or '' if no conditions
     */
    protected function buildQueryFilter(array $filtersCfg, array $input, string $tableAlias, array &$params, ?string $idField = 'page'): string
    {
        $conditions = [];

        // 0) Normalize min/max pairs: power_wattage_min=..&power_wattage_max=..
        $normalized = self::normalizeRanges($input);

        foreach ($normalized as $key => $value) {
            if ($key === $idField) { continue; }
            if (!isset($filtersCfg[$key]) || ($filtersCfg[$key]['enabled'] ?? true) === false) {
                // Unknown or disabled facet -> ignore safely
                continue;
            }

            $cfg   = $filtersCfg[$key];
            $kind  = $cfg['kind']   ?? 'checkbox';
            $source= $cfg['source'] ?? 'column';

            // Resolve SQL expression to filter on
            // - checkbox/radio/range use $cfg['expr']
            // - range_span uses $cfg['min_expr'] / $cfg['max_expr']
            if ($kind === 'range_span') {
                $minExpr = $cfg['min_expr'];
                $maxExpr = $cfg['max_expr'];
            } else {
                $expr = $cfg['expr'] ?? $tableAlias . '.' . $key;
            }

            // === checkbox ===
            if ($kind === 'checkbox') {
                $vals = is_array($value) ? array_values($value) : [$value];
                $vals = array_values(array_filter($vals, static fn($v) => $v !== '' && $v !== null));

                if (empty($vals)) { continue; }

                // Junction: build EXISTS subquery; Column/Join: regular IN (...)
                if ($source === 'junction' && !empty($cfg['junction'])) {
                    // Build EXISTS (...) joining through the junction chain and applying expr IN (...)
                    [$existsSql, $existsParams] = self::buildJunctionExists($cfg, $expr, $vals, $tableAlias);
                    $conditions[] = $existsSql;
                    $params       = array_merge($params, $existsParams);
                } else {
                    $inPh = [];
                    foreach ($vals as $i => $v) {
                        $p = "{$key}_{$i}";
                        $params[$p] = $v;
                        $inPh[] = ':' . $p;
                    }
                    $conditions[] = "{$expr} IN (" . implode(',', $inPh) . ")";
                }
                continue;
            }

            // === radio (boolean) ===
            if ($kind === 'radio') {
                // accept 'Yes'/'No', '1'/'0', true/false
                $vals = is_array($value) ? array_values($value) : [$value];
                $bools = [];
                foreach ($vals as $v) {
                    $v = is_string($v) ? trim($v) : $v;
                    if ($v === 'Yes' || $v === '1' || $v === 1 || $v === true || $v === 'true') {
                        $bools[] = true;
                    } elseif ($v === 'No' || $v === '0' || $v === 0 || $v === false || $v === 'false') {
                        $bools[] = false;
                    }
                }
                if (empty($bools)) { continue; }

                // Allow multi-select radios -> expr IN (true,false)
                $inPh = [];
                foreach ($bools as $i => $b) {
                    $p = "{$key}_{$i}";
                    $params[$p] = $b;
                    $inPh[] = ':' . $p;
                }
                $conditions[] = "{$expr} IN (" . implode(',', $inPh) . ")";
                continue;
            }

            // === range (min/max) ===
            if ($kind === 'range') {
                // $value may be: ['min'=>..,'max'=>..] or scalar
                $min = is_array($value) ? ($value['min'] ?? null) : null;
                $max = is_array($value) ? ($value['max'] ?? null) : null;

                $rangeParts = [];
                if ($min !== null && $min !== '') {
                    $p = "{$key}_min";
                    $params[$p] = (is_numeric($min) ? 0 + $min : $min);
                    $rangeParts[] = "{$expr} >= :{$p}";
                }
                if ($max !== null && $max !== '') {
                    $p = "{$key}_max";
                    $params[$p] = (is_numeric($max) ? 0 + $max : $max);
                    $rangeParts[] = "{$expr} <= :{$p}";
                }
                if (!empty($rangeParts)) {
                    $conditions[] = '(' . implode(' AND ', $rangeParts) . ')';
                }
                continue;
            }

            // === range_span (e.g., dpi_min/dpi_max) ===
            if ($kind === 'range_span') {
                $min = is_array($value) ? ($value['min'] ?? null) : null;
                $max = is_array($value) ? ($value['max'] ?? null) : null;

                $spanParts = [];
                if ($min !== null && $min !== '') {
                    $p = "{$key}_min";
                    $params[$p] = 0 + $min;
                    // ensure product's max >= requested min
                    $spanParts[] = "{$maxExpr} >= :{$p}";
                }
                if ($max !== null && $max !== '') {
                    $p = "{$key}_max";
                    $params[$p] = 0 + $max;
                    // ensure product's min <= requested max
                    $spanParts[] = "{$minExpr} <= :{$p}";
                }
                if (!empty($spanParts)) {
                    $conditions[] = '(' . implode(' AND ', $spanParts) . ')';
                }
                continue;
            }

            // other kinds can be added here...
        }

        return !empty($conditions) ? 'WHERE ' . implode(' AND ', $conditions) : '';
    }
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


    /** Normalize power_wattage_min / power_wattage_max → power_wattage => ['min'=>..,'max'=>..] (same for any *_min/_max) */
    private static function normalizeRanges(array $in): array
    {
        $out = $in;
        // collect suffix pairs
        $pairs = [];
        foreach ($in as $k => $v) {
            if (str_ends_with($k, '_min') || str_ends_with($k, '_max')) {
                $base = preg_replace('/_(min|max)$/', '', $k);
                $pairs[$base][$k] = $v;
            }
        }
        foreach ($pairs as $base => $kv) {
            $min = $kv[$base . '_min'] ?? null;
            $max = $kv[$base . '_max'] ?? null;
            $out[$base] = ['min' => $min, 'max' => $max];
            unset($out[$base . '_min'], $out[$base . '_max']);
        }
        return $out;
    }

    /** Build EXISTS-subquery for junction checkbox facets (e.g., connection_type) */
    private static function buildJunctionExists(array $cfg, string $valueExpr, array $vals, string $rootAlias = 't'): array
    {
        // Start with the root alias
        $from = '';
        $first = true;
        foreach ($cfg['junction'] as $j) {
            $join = "{$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
            // INNER JOIN chain inside FROM clause of subquery
            if ($first) {
                // convert first JOIN to FROM ... JOIN ...
                // but for simplicity we’ll start with FROM the first table
                // We can reconstruct FROM as: FROM <first_table> <first_alias> JOIN next ...
                $from = "FROM {$j['table']} {$j['alias']}";
                $first = false;
            } else {
                $from .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
            }
        }

        // Build IN list params
        $inPh = [];
        $params = [];
        foreach ($vals as $i => $v) {
            $p = "jn_{$cfg['expr']}_{$i}";
            // cleanup placeholder name (expr may contain dots)
            $p = preg_replace('/[^a-zA-Z0-9_]/', '_', $p);
            $params[$p] = $v;
            $inPh[] = ':' . $p;
        }

        // We need to correlate the subquery with root row t.id or foreign key.
        // Common pattern for peripherals: pc.peripheral_id = p.id and p.id is linked to t.peripheral_id.
        // To keep it generic, require the junction ON clauses to already reference the root alias chain correctly (as in your loader config).
        // Then EXISTS just filters by value expr IN (...)
        $exists = "EXISTS (SELECT 1 {$from} WHERE {$valueExpr} IN (" . implode(',', $inPh) . "))";

        return [$exists, $params];
    }
}