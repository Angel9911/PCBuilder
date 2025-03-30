<?php

namespace App\Service;

use App\Entity\Component;

interface ComponentService
{
    public function getAllComponents(): array;

    public function getCompatibleComponents(array $filterParams): array;
    public function getComponentByName(string $name): Component;

    public function getComponentsByType(string $type): array;

}