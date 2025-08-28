<?php

namespace App\Private_lib;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;

abstract class BaseProduct
{

    abstract public static function getUnits(): array;

    abstract public function getProductKeySpecificationsByType(string $type): array;
    abstract public function getProductFiltersByType(string $type): array;
    public function getAdvancedFilterProducts(
        string $type,
        string $typeFieldName,
        array $products,
        string $productIdField,
        string $collectionKey = 'components', // if it's called from periphery service is 'peripherals'
        ?callable $additionalProductFields = null, // Optional: only passed by PeripheryService
        ?callable $connectionNameResolver = null, // Optional: only passed by PeripheryService
    ): array {
        $response = [
            $collectionKey => [],
            'filters' => [],
        ];

        if (empty($products)) return $response;

        $rawFilters = [];

        foreach ($products as $product) {
            $filterFields = $this->getProductKeySpecificationsByType($type);

            $basic = [
                'id' => $product['id'],
                $productIdField => $product[$productIdField],
                'name' => $product['name'],
                'slugify_name' => $product['slugify_name'],
            ];

            foreach ($filterFields as $field) {

                if (isset($product[$field])) {

                    $basic[$field] = $product[$field];
                } elseif ($field === 'connection_type' && $connectionNameResolver) {

                    // Call custom resolver for peripherals
                    $resolvedNames = $connectionNameResolver($product[$productIdField]);
                    $basic[$field] = implode(',', $resolvedNames); // for display(cardbox)
                    $product[$field] = $resolvedNames; // for filter logic
                }
            }

            $specifications = $this->formatSpecifications($basic);

            $finalProduct = [
                'id' => $product['id'],
                $productIdField => $product[$productIdField],
                'name' => $product['name'],
                'slugify_name' => $product['slugify_name'],
                'image_url' => ''
            ];

            if(!empty($additionalProductFields)) {

                if($collectionKey === 'components'){

                    $additionalFields['specifications_scores'] = $additionalProductFields($product);

                } else{

                    $additionalFields = $additionalProductFields($product);
                }

                $finalProduct = array_merge($finalProduct, $additionalFields);
            }

          /*  echo '<pre>';
            print_r($finalProduct);
            echo '</pre>';*/

            $finalProduct['specifications'] = $specifications;
           /* echo '<pre>';
            print_r($finalProduct);
            echo '</pre>';*/

            $response[$collectionKey][] = $finalProduct;

            foreach ($product as $key => $value) {
                if (in_array($key, ['id', $productIdField, 'name', 'slugify_name', $typeFieldName])) {
                    continue;
                }

                // Normalize arrays and compound fields
                $values = is_array($value)
                    ? $value
                    : (preg_match('/^\{(.+)\}$/', $value, $matches)
                        ? explode(',', $matches[1])
                        : [$value]);

                foreach ($values as $val) {
                    $val = trim($val);

                    if (!isset($rawFilters[$key])) {
                        $rawFilters[$key] = [];
                    }

                    if (!in_array($val, $rawFilters[$key], true)) {
                        $rawFilters[$key][] = $val;
                    }
                }
            }
        }

        foreach ($rawFilters as $key => $values) {
            $response['filters'][] = [
                'label' => ucwords(str_replace('_', ' ', $key)),
                'key' => $key,
                'values' => $values
            ];
        }

        return $response;
    }

    protected function getImagesByProduct(array $product): array
    {
        $imagesRaw = $product[0]['images'] ?? [];
        $images = isset($imagesRaw[0]) ? $imagesRaw : [$imagesRaw];

        $mainImage = array_filter($images, fn($img) =>
            isset($img['is_main']) && ($img['is_main'] === true || $img['is_main'] === 'true')
        );

        $mainImageUrl = count($mainImage) > 0
            ? array_values($mainImage)[0]['component_image_url']
            : $images[0]['component_image_url'] ?? null;

        $allImageUrls = array_map(fn($img) => ['url' => $img['component_image_url']], $images);

        return [
            'main_image_url' => $mainImageUrl,
            'all_image_urls' => $allImageUrls
        ];
    }

    protected function formatSpecifications(array $data): array
    {
        $exclude = ['id', 'component_id', 'peripheral_id', 'name', 'slugify_name', 'images']; // Exclude both components and peripherals keys
        $units = static::getUnits();
        $specs = [];

        foreach ($data as $key => $value) {

            if (in_array($key, $exclude, true)) {
                continue;
            }

            $label = ucwords(str_replace('_', ' ', $key));

            if (isset($units[$key])) {
                $value .= $units[$key];
            }

            $specs[$label] = $value;
        }

        return $specs;
    }
}