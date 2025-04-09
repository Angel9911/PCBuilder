<?php

namespace App\Service\Impl;

use App\Entity\Component;
use App\Service\ComponentService;
use App\Service\OpenAIService;
use App\utils\ObjectMapper;
use Exception;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class OpenAIServiceImpl implements OpenAIService
{
    private ComponentService $componentService;

    private HttpClientInterface $httpClient;
    private string $apiKey;

    /**
     * @param ComponentService $componentService
     * @param  $apiKey
     */
    public function __construct(ComponentService $componentService, HttpClientInterface $httpClient, string $apiKey)
    {
        $this->componentService = $componentService;
        $this->httpClient = $httpClient;
        $this->apiKey = $apiKey;
    }

    public function isConnected(): bool
    {
        return false;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws Exception
     * @throws DecodingExceptionInterface
     */
    public function generateRecommendedPcConfiguration(array $userAnswers): array
    {
        $components = $this->componentService->getAllComponents();

        try {

            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'gpt-3.5-turbo', // or 'gpt-3.5-turbo'
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => "You are a PC build expert. Recommend a **compatible** PC build using only the provided components. 

                            - Match components to user preferences (brand, budget, usage).
                            - Ensure **full compatibility**.
                            - Output **only JSON**:  
                            1. **Component Selection** (structured list).  
                            2. **Explanation** (under 50 words)."
                        ],
                        [
                            'role' => 'user',
                            'content' => json_encode([
                                'User Preferences' => $userAnswers,
                                'Available Components' => $components
                            ])
                        ]
                    ],
                    'temperature' => 0.5,
                ],
            ]);

            $data = $response->toArray();
            $responseText = $data['choices'][0]['message']['content'] ?? '{}';

            //file_put_contents('ai_response_log.txt', $responseText);

            // **Proper JSON decoding**
            $decodedResponse = json_decode($responseText, true);

            // Ensure JSON is properly structured
            if (!isset($decodedResponse['Component Selection']) || !isset($decodedResponse['Explanation'])) {
                throw new Exception("Invalid AI response format.");
            }

            $formatAiResult = [];

            foreach ($decodedResponse['Component Selection'] as $key => $value) {

                $formattedKey = strtolower(str_replace(' ','_', $key));

                $formatAiResult['Component Selection'][$formattedKey] = [
                    "name" => $value
                ];
            }

            return [
                'components' => $formatAiResult['Component Selection'],  // Returns an associative array
                'explanation' => $decodedResponse['Explanation']
            ];

        } catch (Exception $e) {
            throw new Exception("Failed to generate OpenAI recommended PC configuration: " . $e->getMessage());
        }
    }

    public function getHardcodedArray(): array
    {
        $hardcodedResponse = [
            "Component Selection" => [
                "CPU" => [
                    "name" => "Intel Core i7-13700K",
                    'id' => 1,
                    "generation" => "13th Gen",
                    "power_wattage" => 60
                ],
                "Motherboard" => [
                    "name" => "ASUS PRIME H610M-K D4",
                    "year" => "2024"
                ],
                "RAM" => [
                    "name" => "Corsair Vengeance DDR5 32GB",
                    "year" => "2024"
                ],
                "GPU" => [
                    "name" => "NVIDIA GeForce RTX 4070 Ti",
                    "year" => "2024",
                    "power_wattage" => 300
                ],
                "PSU" => [
                    "name" => "EVGA SuperNOVA 1200 P3",
                    "year" => "2024",
                    "power_wattage" => 1200
                ],
                // Adding missing components with placeholders
                "Storage" => [
                    "name" => "Samsung 970 EVO Plus 1TB",
                    "type" => "NVMe SSD",
                    "year" => "2024"
                ],
                "CPU Cooling" => [
                    "name" => "Corsair iCUE H150i ELITE CAPELLIX",
                    "type" => "Liquid Cooler",
                    "year" => "2024"
                ],
                "Sound Card" => [
                    "name" => "Creative Sound Blaster Z SE",
                    "type" => "PCIe Sound Card",
                    "year" => "2024"
                ],
                "Network Card" => [
                    "name" => "TP-Link Archer TX3000E",
                    "type" => "Wi-Fi 6 PCIe Adapter",
                    "year" => "2024"
                ]
            ],
            "Explanation" => "This build is optimized for high-performance workstation tasks like 3D rendering and AI workloads. The Intel Core i9-14900K provides top-tier processing power, while the NVIDIA RTX 6000 Ada ensures excellent GPU acceleration..."
        ];

        return $hardcodedResponse;
    }
}