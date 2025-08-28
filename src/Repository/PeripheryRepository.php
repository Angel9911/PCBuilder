<?php

namespace App\Repository;

use App\Constraints\PeripheryCatalogFilter;
use App\Entity\Component;
use App\Entity\Periphery\Periphery;
use App\Private_lib\IndexableProductCache;
use App\Private_lib\trait\ProductDetailsTrait;
use App\Private_lib\trait\QueryFilterTrait;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

class PeripheryRepository extends ServiceEntityRepository implements IndexableProductCache
{
    protected EntityManagerInterface $entityManager;

    use QueryFilterTrait;

    use ProductDetailsTrait;
    private static int $PAGE = 0;
    private static int $OFFSET = 0;
    private static array $COMPONENT_FILTERS = [];
    private static array $SELECTED_COMPONENTS = [];

    public function __construct(ManagerRegistry $registry, EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, Periphery::class);
        $this->entityManager = $entityManager;
    }

    /**
     * @param string $type
     * @return array
     */
    public function getPeripheralsByType(string $type): array
    {
        $result = $this->createQueryBuilder('p')
            ->select('p.id AS id','p.name AS name')
            ->innerJoin('p.type', 'pt')
            ->where('pt.name = :name')
            ->setParameter('name', $type)
            ->getQuery()
            ->getScalarResult();

        return $result;
    }

    /**
     * @throws Exception
     */
    public function getProductsIdsByType(string $type): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = "SELECT p.id
            FROM peripherals p
            JOIN periphery_types pt ON pt.id = p.type_id
            WHERE pt.name = :type
            ORDER BY p.id ASC";

        $ids = $conn->executeQuery($sql, ['type' => $type])->fetchFirstColumn();

        // return int[]
        return array_map('intval', $ids);

    }

    /**
     * @throws Exception
     */
    public function getProductSpecsByTypeAndIds(string $peripheryType, array $ids): array
    {
        if (empty($ids)) return [];

        $conn = $this->entityManager->getConnection();

        $sql = "
        SELECT
            t.*,
            p.id          AS peripheral_id,
            p.name,
            p.slugify_name,
            p.description AS description,
            pb.name       AS brand_name,
            pt.name       AS periphery_type
        FROM {$peripheryType} t
        JOIN peripherals      p  ON p.id = t.peripheral_id
        JOIN periphery_types  pt ON pt.id = p.type_id
        JOIN brands           pb ON pb.id = p.brand_id
        WHERE p.id = ANY(:ids)
    ";


        $stmt = $conn->prepare($sql);
        return $stmt->executeQuery(['ids' => $ids])->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function getPeripherySpecs(string $type
        , int $limit = 0
        , int $offset = 0
        , array $productIds = []
        , array $filters=[]
        , array $selectedPeripherals = []
        , Connection $conn = null
        , int $id = 0
    ): ?array
    {
        if ($conn === null) {
            $conn = $this->entityManager->getConnection();
        }

        $params = [];
        $whereSql = $this->buildWhereClauseSql(
            'peripheral_id',
            $id,
            $filters,
            $selectedPeripherals,
            't',
            $params
        );

        // NEW: If we were given a list of base IDs, constrain by p.id
        if (!empty($productIds)) {
            $placeholders = [];
            foreach ($productIds as $i => $pid) {
                $key = "pid_$i";
                $placeholders[] = ":$key";
                $params[$key] = (int)$pid;
            }
            $whereSql .= (trim($whereSql) === '' ? ' WHERE ' : ' AND ')
                . 'p.id IN (' . implode(',', $placeholders) . ')';
        }

        $sql = "
            SELECT t.*, p.name, p.slugify_name, p.description AS description, pb.name AS brand_name, pt.name AS periphery_type
            FROM {$type} t
            JOIN peripherals p ON p.id = t.peripheral_id
            JOIN periphery_types pt ON pt.id = p.type_id
            JOIN brands pb on pb.id = p.brand_id
            $whereSql
            ORDER BY t.id ASC
        ";

        $sql .= $this->buildPaginationClauseSql($limit, $offset);

        //$stmt = $conn->prepare($sql);
/*        echo '<pre>';
        print_r($params['ids']);
        echo '</pre>';*/
        $result = $conn->executeQuery(
            $sql,
            $params,
            isset($params['ids']) ? ['ids' => ArrayParameterType::INTEGER] : []
        );

        return $id > 0 ? ($result->fetchAssociative() ?: []) : $result->fetchAllAssociative();
    }

    /**
     * @throws Exception
     */
    public function getPeripheryDetailsBySlugifyNameAndType(string $slugifyComponentName, string $peripheryType): array
    {
        $details = $this->getProductDetailsBySlugifyName(
            $slugifyComponentName,
            'peripherals',
            $peripheryType,
            'peripheral_id',
            'peripheral_images',
            'peripheral_id'
        );

        return $details;
    }

    /**
     * @throws Exception
     */
    public function getPeripheralConnectionNames(int $peripheralId): array
    {
        $sql = "
        SELECT DISTINCT pc.name
        FROM peripheral_connections pconn
        JOIN periphery_connections pc ON pc.id = pconn.connection_id
        WHERE pconn.peripheral_id = :pid
    ";

        $stmt = $this->entityManager->getConnection()->prepare($sql);
        $result = $stmt->executeQuery(['pid' => $peripheralId]);

        return array_column($result->fetchAllAssociative(), 'name');
    }

    /**
     * @throws Exception
     */
    function getTotalsCountPeriphery(string $type): int
    {
        return $this->getProductsTypeCount($type);
    }

    /**
     * @throws Exception
     */
    public function getAndLoadProductFiltersByType(string $type, array $productsIds = []): array
    {
        $conn = $this->entityManager->getConnection();

        $cfg = PeripheryCatalogFilter::get($type);

        $table = $cfg['table'];
        $filtersCfg = $cfg['filters'] ?? [];

        $baseFrom = "FROM {$table} t";
        $baseWhere = "WHERE 1=1";
        $params = [];

        if ($productsIds) {
            $baseWhere .= " AND t.peripheral_id = ANY(:compat_ids)";
            // Fast & simple: pass as Postgres array text
            $params['compat_ids'] = '{' . implode(',', array_map('intval', $productsIds)) . '}';
        }

        $out = [];

        foreach ($filtersCfg as $key => $f) {
            if (isset($f['enabled']) && $f['enabled'] === false) {
                continue; // ⛔ hidden for now
            }

            $label  = $f['label'] ?? ucfirst(str_replace('_',' ',$key));
            $kind   = $f['kind'];
            $source = $f['source'];

            // Build FROM with joins if needed
            $from = $baseFrom;
            if (!empty($f['joins'])) {
                foreach ($f['joins'] as $j) {
                    $from .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
                }
            }
            if ($source === 'junction' && !empty($f['junction'])) {
                foreach ($f['junction'] as $j) {
                    $from .= " {$j['type']} JOIN {$j['table']} {$j['alias']} ON {$j['on']}";
                }
            }

            // === CHECKBOX (distinct values) ===
            if ($kind === 'checkbox') {
                $expr = $f['expr'];
                $sql = "
                    SELECT DISTINCT {$expr} AS val
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL AND {$expr}::text <> ''
                    ORDER BY {$expr} ASC
                ";

                $vals = array_column($conn->fetchAllAssociative($sql, $params), 'val');

                // was: array_values($vals)
                $vals = array_map(static fn($v) => (string)$v, $vals);
                $assoc = !empty($vals) ? array_combine($vals, $vals) : [];

                $out[] = ['label' => $label, 'type' => $kind, 'key' => $key, 'values' => $assoc];

                continue;
            }

            // === RADIO (boolean → Yes/No) ===
            if ($kind === 'radio') {
                $expr = $f['expr'];
                $sql = "
                    SELECT DISTINCT {$expr} AS val
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                    ORDER BY {$expr} ASC
                ";
                $raw = array_column($conn->fetchAllAssociative($sql, $params), 'val');

                // Normalize: show as Yes/No
                $labels = [];
                foreach ($raw as $v) {

                    $labels[] = (filter_var($v, FILTER_VALIDATE_BOOLEAN) || $v === true || $v === 'true') ? 'Yes' : 'No';
                }
                // Ensure stable ordering Yes, No (if both exist)
                $labels = array_values(array_unique($labels));
                usort($labels, fn($a,$b) => ($a === 'Yes' ? 1 : 0) <=> ($b === 'No' ? 0 : 1));

                $out[] = ['label' => $label, 'type' => $kind, 'key' => $key, 'values' => $labels];
                continue;
            }

            // === RANGE (min/max + distinct ticks) ===
            if ($kind === 'range') {
                $expr = $f['expr'];
                $maxDistinct = $f['max_distinct'] ?? 50;

                $minMaxSql = "
                    SELECT MIN({$expr}) AS min, MAX({$expr}) AS max
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                ";
                $row = $conn->fetchAssociative($minMaxSql, $params) ?: ['min'=>null,'max'=>null];

                $ticksSql = "
                    SELECT DISTINCT {$expr} AS v
                    {$from}
                    {$baseWhere} AND {$expr} IS NOT NULL
                    ORDER BY {$expr} ASC
                    LIMIT {$maxDistinct}
                ";

                $ticks = array_column($conn->fetchAllAssociative($ticksSql, $params), 'v');
                $ticks = array_map(static fn($v) => (string)$v, $ticks);

                $distinctAssoc = !empty($ticks) ? array_combine($ticks, $ticks) : [];

                $out[] = [
                    'label'    => $label,
                    'key'      => $key,
                    'type'     => $kind,
                    'values'   => ['min' => $row['min'], 'max' => $row['max']],
                    'distinct' => $distinctAssoc,
                ];

                continue;
            }

            // === RANGE_SPAN (dpi_min/dpi_max) ===
            if ($kind === 'range_span') {
                $minExpr = $f['min_expr'];
                $maxExpr = $f['max_expr'];
                $maxDistinct = $f['max_distinct'] ?? 50;

                $minMaxSql = "
                    SELECT MIN({$minExpr}) AS min, MAX({$maxExpr}) AS max
                    {$from}
                    {$baseWhere} AND {$minExpr} IS NOT NULL AND {$maxExpr} IS NOT NULL
                ";
                $row = $conn->fetchAssociative($minMaxSql, $params) ?: ['min'=>null,'max'=>null];

                // Build ticks from union of distinct mins and maxes
                $ticksSql = "
                    SELECT v FROM (
                        SELECT DISTINCT {$minExpr} AS v
                        {$from}
                        {$baseWhere} AND {$minExpr} IS NOT NULL
                        UNION
                        SELECT DISTINCT {$maxExpr} AS v
                        {$from}
                        {$baseWhere} AND {$maxExpr} IS NOT NULL
                    ) u
                    ORDER BY v ASC
                    LIMIT {$maxDistinct}
                ";

                $ticks = array_column($conn->fetchAllAssociative($ticksSql, $params), 'v');
                $ticks = array_map(static fn($v) => (string)$v, $ticks);

                $distinctAssoc = !empty($ticks) ? array_combine($ticks, $ticks) : [];

                $out[] = [
                    'label'    => $label,
                    'key'      => $key,
                    'type'     => $kind,
                    'values'   => ['min' => $row['min'], 'max' => $row['max']],
                    'distinct' => $distinctAssoc,
                ];

                continue;
            }

            throw new \LogicException("Unsupported filter kind '{$kind}' for '{$key}'");
        }

        return $out;
    }
}