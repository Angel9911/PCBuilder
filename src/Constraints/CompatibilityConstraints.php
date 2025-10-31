<?php

namespace App\Constraints;

final class CompatibilityConstraints
{
    public static array $formFactorCompatibility = [
        'Mini-ITX' => ['Mini-ITX'],
        'Micro-ATX' => ['Micro-ATX'], // TODO: check if Mini-ITX is appropriate for Micro-ATX
        'ATX' => ['Micro-ATX', 'ATX'], // TODO: check if Mini-ITX is appropriate for ATX
        'E-ATX' => ['Mini-ITX', 'Micro-ATX', 'ATX', 'E-ATX'],
    ];
}