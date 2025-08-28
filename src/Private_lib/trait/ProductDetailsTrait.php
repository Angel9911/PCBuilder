<?php

namespace App\Private_lib\trait;

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
        $where = "WHERE p.slugify_name = :slugify_name";

        $sqlDetails = "
            SELECT t.*, p.name
            FROM {$productTable} t
            JOIN {$tableAlias} p ON p.id = t.{$productIdField}
            $where
        ";

        $sqlImages = "
            SELECT img.image_url, img.is_primary
            FROM {$imageTable} img
            JOIN {$tableAlias} p ON p.id = img.{$imageJoinField}
            $where
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