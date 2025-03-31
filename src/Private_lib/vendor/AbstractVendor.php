<?php

namespace App\Private_lib\vendor;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class AbstractVendor implements VendorModel
{
    private HttpClientInterface $client;

    private Crawler $crawler;
    public function __construct(HttpClientInterface $client = null)
    {
        $this->client = $client;
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function getProductDetails(string $productUrl, string $domain, string $priceStyleClass, string $statusStyleClass, string $logoStyleClass): array
    {
        // Directly fetch product details from the given URL
        $response = $this->client->request('GET', $productUrl);

        if ($response->getStatusCode() !== 200) {
            return [];
        }

        $html = $response->getContent();

        $this->crawler = new Crawler($html);

        $productStatus = $this->getProductStatus($statusStyleClass);

        $productPrice = $this->getProductPrice($priceStyleClass);

        $productLogoUrl = $this->getProductLogoUrl($logoStyleClass, $domain);


        return [
            'vendor_nane' => 'test',
            'logo' => $productLogoUrl,
            'price' => $this->formatPrice($productPrice),
            'stock_status' => $productStatus !== 'Unknown' ? 'In Stock' : 'Out of Stock',
            "stockClass" => $productStatus !== 'Unknown' ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800",
            'link' => $productUrl,
            "disabled" => !($productStatus !== 'Unknown')
        ];

    }
    function getProductPrice(string $priceStyleClass): string
    {
        return $this->crawler->filter($priceStyleClass)->count() ? $this->crawler->filter($priceStyleClass)->text() : 'Not available';
    }

    function getProductStatus(string $statusStyleClass): string
    {
        return $this->crawler->filter($statusStyleClass)->count() ?  $this->crawler->filter($statusStyleClass)->text() : 'Unknown';
    }

    function getProductLogoUrl(string $logoStyleClass, string $domain): string
    {
        // Extract the vendor logo
        $logoUrl = $this->crawler->filter($logoStyleClass)->count()
            ? $this->crawler->filter($logoStyleClass)->attr('src')
            : null;
        // Ensure the logo URL is absolute
        if ($logoUrl && !str_starts_with($logoUrl, 'http')) {

            $logoUrl = 'https://' . $domain . $logoUrl;
        }
        return $logoUrl;
    }

    protected function formatPrice(string $price): string
    {
        // Remove non-numeric characters except dots and commas
        $price = trim($price);

        $formattedPrice = '';

        // Match the integer and decimal parts separately
        if (preg_match('/(\d+)(?:[.,](\d{1,2}))?/', $price, $matches)) {

            $integerPart = $matches[1]; // Main number

            $decimalPart = $matches[2] ?? '00'; // Decimal part (default to "00" if missing)

            $formattedPrice = $integerPart . '.' . $decimalPart;
        }

        return number_format(floatval($formattedPrice), 2, '.', '');

    }

}