<?php

namespace App\MessageHandler;

use App\Constraints\CacheConstraints;
use App\Private_lib\redis\RedisWrapper;
use App\Service\VendorScraperService;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class PreloadVendorOffers
{
    private RedisWrapper $redis;
    private VendorScraperService $vendorService;

    public function __construct(RedisWrapper $redis, VendorScraperService $vendorScraperService)
    {
        $this->redis = $redis;
        $this->vendorService = $vendorScraperService;
    }

    public function __invoke(PreloadVendorOffersMessage $message): void
    {
        $componentId = $message->getComponentId();

        $cacheKey = CacheConstraints::$OFFERS_COMPONENT_KEY . '_' .  $componentId;

        $vendorOffers = $this->vendorService->getVendorOffersByComponent($componentId);

        if (!empty($vendorOffers)) {

            file_put_contents('var/log/messenger.log', "Component $componentId has offers: " . json_encode($vendorOffers) . "\n", FILE_APPEND);

            $this->redis->set($cacheKey, json_encode($vendorOffers), 900);
        }
    }
}