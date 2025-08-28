<?php

namespace App\Private_lib;

use App\Constraints\ConfigurationConstraint;
use App\Service\ComponentService;
use App\Service\PeripheryService;

class ProductServiceDispatcher
{
    public function __construct(
        private readonly ComponentService $componentService,
        private readonly PeripheryService $peripheryService
    ){}

    public function getService(string $type): BaseProductService
    {
        //$productType = ConfigurationConstraint::getProductType($type);

        return match ($type) {
            'components' => $this->componentService,
            'peripherals' => $this->peripheryService,
            default => throw new \InvalidArgumentException("Unknown product type: $type"),
        };
    }
}