<?php

namespace App\Private_lib;

interface BaseProductService
{
    public function getAllProductsByType(string $productType, int $limit = 12, int $offset = 0, array $selectedCompatibleProducts = [], array $productIds = []): array;
    public function getProductsByFilters(string $productType, array $filters, int $limit = 12, int $offset = 0, array $selectedComponents = [], );
    public function getProductDetailsByProductNameAndType(string $productName, string $productType): array;

    public function getAiRecommendedProduct(string $productType, array $userRequirements): array;

    public function getProductsTypeCount(string $type): int;

    public function getProductsIdsByType(string $type): array; // int[]

    /** Return full rows (cards) for the given IDs within the subtype’s table(s). */
    public function getProductCardSpecsByTypeAndIds(string $peripheryType, array $ids): array; // array of rows

    public function rateProduct(string $baseProductType, array $productRatingData, array $userData);

    public function getProductRating(string $productType, int $productId): array;
}