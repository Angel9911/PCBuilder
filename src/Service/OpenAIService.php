<?php

namespace App\Service;

use App\Entity\Component;

interface OpenAIService
{
    public function isConnected(): bool;
    public function generateRecommendedPcConfiguration(array $userAnswers): array;
}