<?php

namespace App\Private_lib\trait;

use App\Constraints\ComponentCatalogFilter;
use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManagerInterface;

trait ProductDetailsTrait
{
    protected EntityManagerInterface $entityManager;

    /**
     * @throws Exception
     */
    public function getProductDetailsBySlugifyName(
        string $slugifyName,
        string $tableAlias,
        string $productTable,
        string $productIdField,
        string $imageTable,
        string $imageJoinField
    ): array {
        if (empty($slugifyName) || empty($productTable)) {
            return [];
        }

        $conn = $this->entityManager->getConnection();

        $params = ['slugify_name' => $slugifyName];

        $productCatalogTable = ComponentCatalogFilter::get($productTable);

        // start select
        $select = ["t.*"];
        $joins = [];

        // add base joins
        foreach ($productCatalogTable['base_joins'] as $j) {

            $joins[] = sprintf(
                "%s JOIN %s %s ON %s",
                $j['type'],
                $j['table'],
                $j['alias'],
                $j['on']
            );
        }

        // add filters
        foreach ($productCatalogTable['filters'] as $filterKey => $filter) {

            if ($filter['source'] === 'join' && !empty($filter['joins'])) {

                foreach ($filter['joins'] as $fj) {
                    $joins[] = sprintf(
                        "%s JOIN %s %s ON %s",
                        $fj['type'],
                        $fj['table'],
                        $fj['alias'],
                        $fj['on']
                    );
                }

                // also add the column for SELECT
                $alias = preg_replace('/\W+/', '_', $filterKey);

                $select[] = $filter['expr'] . " AS " . $alias;
            }
            elseif ($filter['source'] === 'column') {

                $alias = preg_replace('/\W+/', '_', $filterKey);

                $select[] = $filter['expr'] . " AS " . $alias;
            }
        }

        // 3) WHERE (use configured slug_expr)
        $slugExpr = $productCatalogTable['slug_expr'] ?? 'c.slugify_name';

        $whereDetails = "WHERE {$slugExpr} = :slugify_name";

        // 3a) OPTIONAL: also select the product name from the same alias as slug_expr
        // Extract alias before the dot from slug_expr (e.g., 'c.slugify_name' -> 'c')
        if (preg_match('/^([a-zA-Z_][a-zA-Z0-9_]*)\./', $slugExpr, $m)) {

            $nameAlias = $m[1];
            // Add "alias.name AS name" only if that alias is present in the joins (or is the main table alias)
            // In practice base_joins includes it, so this is safe:
            $select[] = "{$nameAlias}.name";
        }

        // details query
        $sqlDetails = "SELECT " . implode(", ", $select) . "
        FROM {$productCatalogTable['table']} t
        " . implode(" ", array_unique($joins)) . "
        {$whereDetails}";

        $imageAlias = isset($nameAlias) ? $nameAlias : 'p';

        $sqlImages = "
            SELECT img.image_url, img.is_primary
            FROM {$imageTable} img
            JOIN {$tableAlias} {$imageAlias} ON {$imageAlias}.id = img.{$imageJoinField}
            WHERE {$slugExpr} = :slugify_name
        ";

        $details = $conn->prepare($sqlDetails)->executeQuery($params)->fetchAllAssociative();

        $images = $conn->prepare($sqlImages)->executeQuery($params)->fetchAllAssociative();

        if (empty($details)) {
            return [];
        }

        // Attach images
        $details[0]['images'] = array_map(fn($img) => [
            'component_image_url' => $img['image_url'],
            'is_main' => $img['is_primary'] ? 'true' : 'false'
        ], $images);

        return $details;
    }

    /**
     * @throws Exception
     */
    public function getProductsRatings(string $productType, int $productId): array
    {
        $conn = $this->entityManager->getConnection();

        $sql = "
            SELECT ROUND(AVG(rating)::numeric, 2) AS avg_rating,
                   COUNT(rating) AS count_ratings
            FROM product_ratings
            WHERE product_type = :product_type
            AND product_id = :product_id
        ";

        $productRatings = $conn->prepare($sql)->executeQuery(['product_type' => $productType, 'product_id' => $productId])->fetchAssociative();

        if(empty($productRatings)){

            return [];
        }

        return [
            'average' => $productRatings['avg_rating'] !== null ? round((float)$productRatings['avg_rating'], 1) : 0.0,
            'count' => (int)$productRatings['count_ratings'],
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductReviews(string $productType, int $productId): array
    {
        $conn = $this->entityManager->getConnection();

        $sqlSummary = "
        SELECT
            COUNT(*) FILTER (WHERE rating = 5) AS five_star,
            COUNT(*) FILTER (WHERE rating = 4) AS four_star,
            COUNT(*) FILTER (WHERE rating = 3) AS three_star,
            COUNT(*) FILTER (WHERE rating = 2) AS two_star,
            COUNT(*) FILTER (WHERE rating = 1) AS one_star
        FROM product_ratings
        WHERE product_type = :product_type
          AND product_id = :product_id
    ";

        $summary = $conn->prepare($sqlSummary)
            ->executeQuery([
                'product_type' => $productType,
                'product_id'   => $productId
            ])
            ->fetchAssociative();

        if (empty($summary)) {
            return [
                'rates'   => [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0],
                'comments' => [],
            ];
        }

        $sqlComments = "
        SELECT
            pr.rating,
            pr.review,
            pr.created_at,
            u.first_name AS first_name
        FROM product_ratings pr
        LEFT JOIN users u ON u.id = pr.user_id
        INNER JOIN user_accounts ac ON ac.id = u.user_account_id
        WHERE pr.product_type = :product_type
          AND pr.product_id = :product_id
          AND pr.review IS NOT NULL
          AND TRIM(pr.review) <> ''
        ORDER BY pr.created_at DESC
    ";

        $comments = $conn->prepare($sqlComments)
            ->executeQuery([
                'product_type' => $productType,
                'product_id'   => $productId
            ])
            ->fetchAllAssociative();

        // Format final response
        return [
            'rates'   => [
                5 => (int)$summary['five_star'],
                4 => (int)$summary['four_star'],
                3 => (int)$summary['three_star'],
                2 => (int)$summary['two_star'],
                1 => (int)$summary['one_star'],
            ],
            'comments' => array_map(fn($c) => [
                'user'       => $c['first_name'] ?? 'Anonymous',
                'rating'     => (int)$c['rating'],
                'comment'    => $c['review'],
                'created_at' => $c['created_at'],
            ], $comments),
        ];
    }

    /**
     * @throws Exception
     */
    public function getProductsTypeCount(string $type): int
    {
        $conn = $this->entityManager->getConnection(); // Make sure this is injected in your service constructor

        $sql = "
            SELECT COUNT(DISTINCT t.id)
            FROM {$type} t
            ";

        $stmt = $conn->prepare($sql);

        $result = $stmt->executeQuery();

        return $result->fetchOne();
    }
}