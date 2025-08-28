<?php

namespace App\Service;

use App\Entity\Component;

interface OpenAIService
{
    public function isConnected(): bool;
    public function generateRecommendedPcConfigurationFromQuestionnaire(array $userAnswers): array;
    public function generateRecommendedPcConfigurationFromField();
    public function calculateBottleneckConfiguration(array $bottleneckComponents): array;
    public function reviewUserConfiguration(array $userRequirements, array $selectedComponents): array;
    public function generateRecommendedProducts(string $productType, array $availableProducts, array $userAnswer): array;
}