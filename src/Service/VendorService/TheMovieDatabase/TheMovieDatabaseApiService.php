<?php

namespace App\Service\VendorService\TheMovieDatabase;

use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;

/**
 * Class TheMovieDatabaseApiService.
 */
class TheMovieDatabaseApiService
{
    private const SEARCH_URL = 'https://api.themoviedb.org/3/search/movie';
    private const BASE_IMAGE_PATH = 'https://image.tmdb.org/t/p/original';

    private $apiKey;
    private $client;
    private $logger;

    /**
     * TheMovieDatabaseApiService constructor.
     *
     * @param string $apiKey
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(string $apiKey, ClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->apiKey = $apiKey;
        $this->client = $httpClient;
        $this->logger = $logger;
    }

    /**
     * Search in the movie database for a poster url by title and year.
     *
     * @param string $title
     *   The title of the item
     * @param string $year
     *   The release year of the item
     *
     * @return string
     *   The poster url or ''
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
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
            if (429 == $response->getStatusCode()) {
                $retryAfterHeader = $response->getHeader('Retry-After');
                if (is_numeric($retryAfterHeader)) {
                    $retryAfter = (int) $retryAfterHeader;
                } else {
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

    /**
     * Get the first match in the result set.
     *
     * @param array  $results
     *   Array of search results
     * @param string $title
     *   The title of the item
     *
     * @return \stdClass|null
     *   The matching result or null
     */
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

    /**
     * Get the poster url for a search result.
     *
     * @param array $result
     *   The result to create poster url from
     *
     * @return string|null
     *   The poster url or null
     */
    private function getPosterUrl($result): string
    {
        return ($result && !empty($result->poster_path)) ? self::BASE_IMAGE_PATH.$result->poster_path : null;
    }
}
