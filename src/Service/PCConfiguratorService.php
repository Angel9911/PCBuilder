<?php

namespace App\Service;

use App\Entity\CompletedConfiguration;

interface PCConfiguratorService
{
    public function savePcConfiguration(array $componentsValues): CompletedConfiguration;

    public function getPcConfigurations(): array;

    public function getPcConfigurationById(int $configurationId): array;

    public function getPcConfigurationDetails(int $configurationId): CompletedConfiguration;
}