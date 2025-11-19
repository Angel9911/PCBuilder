<?php

namespace App\Service;

use App\Entity\CompletedConfiguration;

interface PCConfiguratorService
{
    public function savePcConfiguration(array $componentsValues): CompletedConfiguration;

    public function getPcConfigurations(int $limit, int $offset, array $specificConfigurations = null): array;

    public function getPcConfigurationById(int $configurationId): array;

    public function getPcConfigurationDetails(int $configurationId): CompletedConfiguration;

    public function getAiRecommendedConfigurations(array $userRequirements): array;

    public function ratePcConfiguration(array $configRating, array $userData): void;

    public function getPcConfigurationRating(): array;
    public function getTotalsCountConfigurations(): int;
}