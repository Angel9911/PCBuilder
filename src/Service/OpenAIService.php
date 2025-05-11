<?php

namespace App\Service;

use App\Entity\Component;

interface OpenAIService
{
    public function isConnected(): bool;
    public function generateRecommendedPcConfiguration(array $userAnswers): array;
    public function calculateBottleneckConfiguration(array $bottleneckComponents): array;
    public function reviewUserConfiguration(array $userAnswers): array;
}