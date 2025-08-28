<?php

namespace App\Service;

interface PeripheryService
{
    public function getPeripheralsByType(string $type): array;
}