<?php

namespace App\Service;

use App\Entity\Component;

interface ComponentService
{
    public function getAllComponents(): array;

    public function getCompatibleComponents(array $filterParams): array;

    public function getComponentsDetailsByType(string $componentType): array;

    public function getTotalsCountComponentsByType(string $componentType): int;
    public function getComponentsByType(string $type): array;

}