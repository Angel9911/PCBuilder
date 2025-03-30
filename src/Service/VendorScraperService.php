<?php

namespace App\Service;

interface VendorScraperService
{
    public function getVendorsOffers(int $componentId): ?array;
}