<?php

namespace App\Service;

use App\Entity\Component;

interface ComponentService
{
    public function getAllComponents(): array;

    public function updateComponentName(string $existingName, string $slugifyName): void;

    public function getComponentIdBySlugifyName(string $slugifyName): int;

    public function getCompatibleComponents(array $filterParams): array;

    public function getComponentsByFilters(string $componentType, array $filters): array;

    public function getTotalsCountComponentsByType(string $componentType): int;
    public function getComponentsByType(string $type): array;
    public function getComponentNameBySlugifyName(string $slugifyName): array;
}