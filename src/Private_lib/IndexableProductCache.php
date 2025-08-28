<?php

namespace App\Private_lib;

interface IndexableProductCache
{
    /** Return all DB IDs for the given subtype (ordered as you want the listing). */
    public function getProductsIdsByType(string $type): array; // int[]

    /** Return full rows (cards) for the given IDs within the subtype’s table(s). */
    public function getProductSpecsByTypeAndIds(string $peripheryType, array $ids): array; // array of rows

    public function getAndLoadProductFiltersByType(string $type, array $productsIds = []): array;
}