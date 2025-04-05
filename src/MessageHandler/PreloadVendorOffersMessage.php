<?php

namespace App\MessageHandler;

class PreloadVendorOffersMessage
{
    private int $componentId;

    public function __construct(int $componentId)
    {
        $this->componentId = $componentId;
    }

    public function getComponentId(): int
    {
        return $this->componentId;
    }
}