<?php

namespace App\Private_lib\vendor;

use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Vendor extends AbstractVendor
{
    public function __construct(HttpClientInterface $client = null)
    {
        parent::__construct($client);
    }


    /**
     * @throws Exception
     */
    public function processVendorOffers(string $productUrl, string $domain, string $priceStyleClass, string $statusStyleClass, string $logoStyleClass): array
    {
        try {

            return $this->getProductDetails($productUrl, $domain, $priceStyleClass, $statusStyleClass, $logoStyleClass);

        }catch (Exception
        | ClientExceptionInterface
        | RedirectionExceptionInterface
        | ServerExceptionInterface
        | TransportExceptionInterface $exception){

            throw new Exception($exception->getMessage());
        }
    }
}