<?php

namespace App\Service\Impl;

use App\Constraints\VendorConstraints;
use App\Private_lib\vendor\Vendor;
use App\Repository\VendorOfferRepository;
use App\Service\VendorScraperService;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class VendorScraperImpl implements VendorScraperService
{
    private array $vendorsStyles;

    private VendorOfferRepository $vendorOfferRepository;

    private Vendor $vendor;

    /**
     * @param VendorOfferRepository $vendorOfferRepository
     */
    public function __construct(VendorOfferRepository $vendorOfferRepository
                    , Vendor $vendor)
    {
        $this->vendorsStyles = VendorConstraints::$vendorsArray;

        $this->vendorOfferRepository = $vendorOfferRepository;

        $this->vendor = $vendor;
    }


    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \Exception
     */
    public function getVendorsOffers(int $componentId): ?array
    {

        $componentOffers = $this->vendorOfferRepository->findOffersByComponentId($componentId); // get correctly offers my component id


        $productOffersResult = [];
        foreach ($this->vendorsStyles as $vendorName => $vendorStyleParam) {

            foreach ($componentOffers as $componentOffer) {

                $component = $componentOffer->getComponent();

                if($vendorName === $componentOffer->getVendorEntity()->getName()) {

                    $productOffersResult[$component->getId()][] = $this->vendor->processVendorOffers(
                        $componentOffer->getProductUrl(),
                        $componentOffer->getVendorEntity()->getDomainUrl(),
                        $this->vendorsStyles[$vendorName]['product_price_style'],
                        $this->vendorsStyles[$vendorName]['product_status_style'],
                        $this->vendorsStyles[$vendorName]['product_logo_style']
                    );

                }
            }
        }

        return $productOffersResult;
    }

}