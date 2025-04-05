<?php

namespace App\Service;

interface VendorScraperService
{
    public function getVendorOffersByComponent(int $componentId): ?array;

    public function getAllVendorComponents(): array;
}