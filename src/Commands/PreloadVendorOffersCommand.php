<?php

declare(strict_types=1);

namespace App\Commands;

use App\Constraints\CacheConstraints;
use App\MessageHandler\PreloadVendorOffersMessage;
use App\Private_lib\redis\RedisWrapper;
use App\Service\Impl\VendorScraperImpl;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

#[AsCommand(name: 'app:preload_vendor_offers', description: 'Hello PhpStorm')]
class PreloadVendorOffersCommand extends Command
{
    private VendorScraperImpl $vendorService;
    private RedisWrapper $redisWrapper;

    public function __construct(MessageBusInterface $bus
                    , VendorScraperImpl $vendorService
                    , RedisWrapper $redisWrapper)
    {
        parent::__construct();
        $this->vendorService = $vendorService;
        $this->redisWrapper = $redisWrapper;
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $vendorComponents = $this->vendorService->getAllVendorComponents();

        foreach ($vendorComponents as $component => $ids) {

            foreach ($ids as $id) {
                $offers = $this->vendorService->getVendorOffersByComponent($id);

                $cacheKey = CacheConstraints::$OFFERS_COMPONENT_KEY . '_' . $id;

                $this->redisWrapper->set($cacheKey, json_encode($offers), 900);
                $this->redisWrapper->delete($cacheKey);
                $output->writeln("Fetched offers for component {$id}");
            }
        }

        return Command::SUCCESS;
    }
}
