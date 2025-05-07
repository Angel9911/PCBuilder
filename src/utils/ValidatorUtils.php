<?php

namespace App\utils;

final class ValidatorUtils
{
    private function __construct(){}

    /**
     * @param array $availableKeys
     * @param array $requiredKeys
     * @return array
     */
    public static function validateAsKey(array $availableKeys, array $requiredKeys): array
    {
       return array_intersect_key($availableKeys, array_flip($requiredKeys));
    }

    /**
     * TODO: Use this method for validation instead validateAsKey
     * @param array $input
     * @param array $allowedKeys
     * @return array
     */
    public static function filterValidKeys(array $input, array $allowedKeys): array
    {
        return array_filter(
            $input,
            fn($key) => in_array($key, $allowedKeys, true),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function validateAsString(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    /**
     * @param mixed $value
     * @return bool
     */
    public static function validateAsNumber(mixed $value): bool
    {
        return is_numeric($value) && $value > 0;
    }

    /**
     * @param array $input
     * @param array $keys
     * @param string $fieldType
     * @return array
     */
    public static function validateAsFieldType(array $input, array $keys, string $fieldType): array
    {
        $invalid = [];

        foreach ($keys as $key) {

            if($fieldType === 'string' && !self::validateAsString($input[$key])) {

                $invalid[] = $key;
            }
            if($fieldType === 'number' && !self::validateAsNumber($input[$key])) {

                $invalid[] = $key;
            }
        }

        return $invalid;
    }
}