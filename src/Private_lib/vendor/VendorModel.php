<?php

namespace App\Private_lib\vendor;

interface VendorModel
{
    function getProductDetails(string $productUrl, string $domain, string $priceStyleClass, string $statusStyleClass, string $logoStyleClass);
    function getProductPrice(string $priceStyleClass): string;
    function getProductStatus(string $statusStyleClass): string;
    function getProductLogoUrl(string $logoStyleClass, string $domain): string;
}