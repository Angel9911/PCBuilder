<?php

namespace App\utils;

use App\Private_lib\BaseProductService;
use App\Private_lib\IndexableProductCache;
use App\Private_lib\redis\RedisWrapper;

class ProductCache
{
    // Key builders (category = "components" | "peripherals")
    private static function idxKey(string $productCategory, string $productType): string {
        return "{$productCategory}:{$productType}:index";
    }
    private static function cardKey(string $productCategory, string $productType, int $productId): string {
        return "{$productCategory}:{$productType}:card:{$productId}";
    }

    // ─────────────────────────────────────────────
    // NEW KEYS FOR PRODUCT DETAILS / RATINGS
    // ─────────────────────────────────────────────

    private static function slugToIdKey(string $category, string $type, string $slug): string {
        return "{$category}:{$type}:slug:{$slug}:id";
    }

    private static function detailsKey(string $category, string $type, int $id): string {
        return "{$category}:{$type}:{$id}:details";
    }

    private static function ratingKey(string $category, string $type, int $id): string {
        return "{$category}:{$type}:{$id}:rating";
    }

    public static function deleteIndex(RedisWrapper $r, string $cat, string $sub): void {
        $r->delete(self::idxKey($cat, $sub));
    }
    public static function deleteCard(RedisWrapper $r, string $cat, string $sub, int $id): void {
        $r->delete(self::cardKey($cat, $sub, $id));
    }

    /** Load index from cache or build from DB (IDs only). */
    public static function getIndexIdsFromCache(
        RedisWrapper $redis,
        string $category,
        string $subtype,
    ): ?array {

        $k = self::idxKey($category, $subtype);

        if ($redis->isKeyExist($k)) {
            $ids = $redis->get($k);
            if (is_array($ids)) return $ids;
        }

        return null;
    }

    public static function setIdsIndexCache(
        RedisWrapper $redis,
        BaseProductService $productService,
        string $category,
        string $subtype,
        int $ttlSeconds = 3600
    ): array
    {
        $k = self::idxKey($category, $subtype);

        $ids = $productService->getProductsIdsByType($subtype); // int[]
        $redis->set($k, $ids, $ttlSeconds);

        return $ids;
    }

    /** Compute page slice from the index. */
    public static function getPageIds(array $ids, int $page, int $limit): array {

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        return array_slice($ids, $offset, $limit);
    }

    /** Read cached cards for IDs. Returns [id => row]. */
    public static function getCachedCards(RedisWrapper $redis, string $category, string $subtype, array $ids): array {

        $cards = [];

        foreach ($ids as $id) {

            $k = self::cardKey($category, $subtype, (int)$id);

            if ($redis->isKeyExist($k)) {

                $row = $redis->get($k);

                if (is_array($row)){

                    $cards[(int)$id] = $row;
                }
            }
        }
        return $cards;
    }

    /** Cache rows (expects each row to contain its entity id). */
    public static function putCards(RedisWrapper $redis, string $category, string $subtype, array $products, int $ttlSeconds = 3600): void {

        foreach ($products as $product) {

            $id = (int)($product['component_id'] ?? $product['peripheral_id'] ?? $product['id'] ?? 0);

            if ($id > 0) {

                $productSpecsKey = self::cardKey($category, $subtype, $id);

                $redis->set($productSpecsKey, $product, $ttlSeconds);
            }
        }
    }

    // 🔹 Store slug → ID mapping (no TTL)
    public static function putSlugIdMapping(RedisWrapper $redis, string $category, string $type, string $slug, int $id): void {
        $redis->set(self::slugToIdKey($category, $type, $slug), $id);
    }

    // 🔹 Get ID from slug (fast lookup)
    public static function getIdFromSlugCache(RedisWrapper $redis, string $category, string $type, string $slug): ?int {
        $key = self::slugToIdKey($category, $type, $slug);
        if ($redis->isKeyExist($key)) {
            return (int)$redis->get($key);
        }
        return null;
    }

    // 🔹 Store full product details (long TTL)
    public static function putProductDetails(RedisWrapper $redis, string $category, string $type, int $id, array $details, int $ttlSeconds = 86400): void {
        $redis->set(self::detailsKey($category, $type, $id), $details, $ttlSeconds);
    }

    // 🔹 Get cached product details
    public static function getProductDetails(RedisWrapper $redis, string $category, string $type, int $id): ?array {
        $key = self::detailsKey($category, $type, $id);
        if ($redis->isKeyExist($key)) {
            $data = $redis->get($key);
            if (is_array($data)) return $data;
        }
        return null;
    }

    // 🔹 Store rating summary (short TTL)
    public static function putProductRating(RedisWrapper $redis, string $category, string $type, int $id, array $rating, int $ttlSeconds = 900): void {
        $redis->set(self::ratingKey($category, $type, $id), $rating, $ttlSeconds);
    }

    // 🔹 Get cached rating summary
    public static function getProductRating(RedisWrapper $redis, string $category, string $type, int $id): ?array {
        $key = self::ratingKey($category, $type, $id);
        if ($redis->isKeyExist($key)) {
            $data = $redis->get($key);
            if (is_array($data)) return $data;
        }
        return null;
    }

}