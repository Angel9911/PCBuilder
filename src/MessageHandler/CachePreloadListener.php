<?php

namespace App\MessageHandler;

use App\Service\Impl\VendorScraperImpl;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class CachePreloadListener
{
    private MessageBusInterface $messageBus;

    private VendorScraperImpl $scraper;

    public function __construct(MessageBusInterface $messageBus
                    , VendorScraperImpl $scraper)
    {
        $this->messageBus = $messageBus;
        $this->scraper = $scraper;
    }

    /**
     * @throws ExceptionInterface
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        //file_put_contents('var/log/messenger_debug.log', $event->getRequest()->getPathInfo(), FILE_APPEND);
        if ($event->getRequest()->getPathInfo() === '/') {
            //file_put_contents('var/log/messenger_debug.log', "Dispatching messages from listener for each component\n", FILE_APPEND);

            $vendorComponents = $this->scraper->getAllVendorComponents();

            foreach ($vendorComponents as $component => $ids) {
                foreach ($ids as $id) {
                    $this->messageBus->dispatch(new PreloadVendorOffersMessage($id));
                    //file_put_contents('var/log/messenger_debug.log', "Dispatched message from listener 2 for component ID: $id\n", FILE_APPEND);
                }
            }
        }
    }
}