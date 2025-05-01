<?php

namespace App\Service;

use App\Entity\CompletedConfiguration;

interface PCConfiguratorService
{
    public function savePcConfiguration(array $componentsValues): CompletedConfiguration;

    public function getPcConfigurations(int $limit, int $offset): array;

    public function getPcConfigurationById(int $configurationId): array;

    public function getPcConfigurationDetails(int $configurationId): CompletedConfiguration;

    public function getTotalsCountConfigurations(): int;
}