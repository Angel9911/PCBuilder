<?php

namespace App\Service;

interface CompatibilityService
{
    public function getCompatiblePcComponent(array $components): array;

}