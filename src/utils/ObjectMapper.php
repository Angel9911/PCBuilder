<?php declare(strict_types=1);

namespace App\utils;

class ObjectMapper
{
    public static function mapObjectToJson(mixed $json, int $flags = 0, int $depth = 512): string|false
    {
        return json_encode($json, $flags, $depth);
    }

    public static function mapJsonToObject(mixed $array, $associative = true, int $depth = 512, int $flags = 0): array
    {
        return json_decode($array, $associative, $depth, $flags);
    }
}