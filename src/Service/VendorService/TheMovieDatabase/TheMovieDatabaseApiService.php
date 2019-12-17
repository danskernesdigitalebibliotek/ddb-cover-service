<?php

namespace App\Service\VendorService\TheMovieDatabase;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class TheMovieDatabaseApiService
{
    private const SEARCH_URL = 'https://api.themoviedb.org/3/search/movie';
    private const BASE_IMAGE_PATH = 'https://image.tmdb.org/t/p/original';

    private $apiKey;
    private $client;
    private $logger;

    public function __construct(string $apiKey, ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->client = $httpClient;
        $this->logger = $logger;
    }

    public function searchPosterUrl(string $title, string $year): string
    {
        $posterUrl = '';

        try {
            $query = [
                'query' => [
                    'query' => $title,
                    'year' => $year,
                    'api_key' => $this->apiKey,
                    'page' => '1',
                    'include_adult' => 'false',
                    'language' => 'da_DK',
                ],
            ];

            $response = $this->client->request('GET', self::SEARCH_URL, $query);

            // Respect api rate limits: https://developers.themoviedb.org/3/getting-started/request-rate-limiting
            // If 429 rate limit has been hit. Retry request after Retry-After.
            if ($response->getStatusCode() == 429) {
                $retryAfterHeader = $response->getHeader('Retry-After');
                if (is_numeric($retryAfterHeader)) {
                    $retryAfter = (int) $retryAfterHeader;
                }
                else {
                    $retryAfter = (new \DateTime($retryAfterHeader))->format('U') - time();
                }

                // Rate limit hit. Wait until 'Retry-After' header, then retry.
                $this->logger(sprintf('Rate limit hit. Sleeping for %d seconds', $retryAfter));

                sleep($retryAfter);

                // Retry request.
                $response = $this->client->request('GET', self::SEARCH_URL, $query);
            }

            $content = $response->getBody()->getContents();
            $jsonResponse = json_decode($content, false);

            $result = $this->getResultFromSet($jsonResponse->results, $title);

            if ($result) {
                $posterUrl = $this->getPosterUrl($result);
            }
        } catch (\Exception $e) {
            // @TODO: Ignore errors?
        }

        return $posterUrl;
    }

    private function getResultFromSet(array $results, string $title): ?\stdClass
    {
        foreach ($results as $result) {
            if (strtolower($result->title) === strtolower($title)) {
                return $result;
            }
            if (strtolower($result->original_title) === strtolower($title)) {
                return $result;
            }
        }

        return null;
    }

    private function getPosterUrl($result): string
    {
        return ($result && !empty($result->poster_path)) ? self::BASE_IMAGE_PATH.$result->poster_path : null;
    }
}
