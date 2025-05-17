<?php

namespace App\Service\Impl;

use App\Constraints\VendorConstraints;
use App\Private_lib\vendor\Vendor;
use App\Repository\ComponentRepository;
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

    private ComponentRepository $componentRepository;


    private Vendor $vendor;

    /**
     * @param VendorOfferRepository $vendorOfferRepository
     */
    public function __construct(VendorOfferRepository $vendorOfferRepository
                    , ComponentRepository $componentRepository
                    , Vendor $vendor)
    {
        $this->vendorsStyles = VendorConstraints::$vendorsArray;

        $this->vendorOfferRepository = $vendorOfferRepository;

        $this->componentRepository = $componentRepository;

        $this->vendor = $vendor;
    }


    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws \Exception
     */
    public function getVendorOffersByComponent(int $componentId): ?array
    {

        $componentOffers = $this->vendorOfferRepository->findOffersByComponentId($componentId); // get correctly offers my component id

        $currentComponentObject = $this->componentRepository->findComponentById($componentId);

        $productOffersResult = [];

        $productOffersResult['component_specifications'] = [
            'component_name' => $currentComponentObject->getName(),
            'power_wattage' => $currentComponentObject->getPowerWattage()
        ];

        foreach ($this->vendorsStyles as $vendorName => $vendorStyleParam) {

            foreach ($componentOffers as $componentOffer) {

                $component = $componentOffer->getComponent();

                if($vendorName === $componentOffer->getVendorEntity()->getName()) {

                    $productOffersResult[$component->getId()][] = $this->vendor->processVendorOffers(
                        $vendorName,
                        $componentOffer->getProductUrl(),
                        $componentOffer->getVendorEntity()->getDomainUrl(),
                        $this->vendorsStyles[$vendorName]['product_price_style'],
                        $this->vendorsStyles[$vendorName]['product_status_style'],
                        $this->vendorsStyles[$vendorName]['product_logo_style']
                    );
                }
            }

            // add price range(lowest - highest) of certain component part
            $offersPrice = [];

            foreach ($productOffersResult as  $offers){

                foreach ($offers as $offer){

                    if(isset($offer['price']) && $offer['price'] > 0){

                            $offersPrice[] = (float)$offer['price'];
                    }
                }
            }

            sort($offersPrice);

            $productOffersResult['offers_price_range'] = [
                'lowest_price' => number_format(reset($offersPrice),2 , '.', ''),
                'highest_price' => number_format(end($offersPrice),2 , '.', ''),
            ];
        }

        return $productOffersResult;
    }

    public function getAllVendorComponents(): array
    {
        $getAllVendorsOffers = $this->vendorOfferRepository->findAllVendorOffers();

        $vendorsComponentsResult = [];

        foreach ($getAllVendorsOffers as $vendorOffer) {

            //$vendor = $vendorOffer->getVendorEntity(); TODO: Could be used when in the database there is is_scrapable flag

            $vendorComponent = $vendorOffer->getComponent();

            if(!$vendorComponent || !$vendorComponent->getType()){
                continue;
            }

            $vendorComponentType = $vendorComponent->getType()->getName();

            $vendorComponentId = $vendorComponent->getId();

            if (!isset($vendorsComponentsResult[$vendorComponentType])) {

                $vendorsComponentsResult[$vendorComponentType] = [];
            }

            if(!in_array($vendorComponentId, $vendorsComponentsResult[$vendorComponentType])) {

                $vendorsComponentsResult[$vendorComponentType][] = $vendorComponentId;
            }

        }

        return $vendorsComponentsResult;

    }
}