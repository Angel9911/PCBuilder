<?php

namespace App\Controller;

use App\Private_lib\redis\RedisWrapper;
use App\Service\VendorScraperService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PeripheryController extends AbstractController
{
    private VendorScraperService $vendorScraperService;

    private RedisWrapper $redis;
}