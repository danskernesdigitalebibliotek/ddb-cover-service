<?php

namespace App\Service\VendorService\TheMovieDatabase;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
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
     * @param string                      $apiKey
     * @param \GuzzleHttp\ClientInterface $httpClient
     * @param \Psr\Log\LoggerInterface    $logger
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
     * @param string $originalYear
     *   The release year of the item
     *
     * @param string|null $director
     * @return string
     *   The poster url or ''
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function searchPosterUrl(string $title = null, string $originalYear = null, string $director = null): ?string
    {
        $posterUrl = null;

        if ($title == null || $originalYear == null || $director == null) {
            return $posterUrl;
        }

        try {
            $query = [
                'query' => [
                    'query' => $title,
                    'year' => $originalYear,
                    'api_key' => $this->apiKey,
                    'page' => '1',
                    'include_adult' => 'false',
                    'language' => 'da_DK',
                ],
            ];

            $responseData = $this->sendRequest(self::SEARCH_URL, $query);

            $result = $this->getResultFromSet($responseData->results, $title, $director);

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
     * @param array $results
     *   Array of search results
     * @param string $title
     *   The title of the item
     *
     * @return \stdClass|null
     *   The matching result or null
     */
    private function getResultFromSet(array $results, string $title, string $director): ?\stdClass
    {
        $chosenResult = null;

        foreach ($results as $result) {
            if (strtolower($result->title) === strtolower($title) || strtolower($result->original_title) === strtolower($title)) {
                // Validate director.
                try {
                    $queryUrl = 'https://api.themoviedb.org/3/movie/'.$result->id.'/credits';
                    $responseData = $this->sendRequest($queryUrl);

                    $directors = array_reduce($responseData->crew, function ($carry, $item) {
                        if ($item->job === 'Director') {
                            $carry[] = $item->name;
                        }
                        return $carry;
                    }, []);

                    if (in_array($director, $directors)) {
                        if ($chosenResult != null) {
                            return null;
                        }
                        $chosenResult = $result;
                    }
                }
                catch (GuzzleException $e) {
                    // Ignore error.
                }
            }
        }

        return $chosenResult;
    }

    /**
     * Get the poster url for a search result.
     *
     * @param \stdClass $result
     *   The result to create poster url from
     *
     * @return string|null
     *   The poster url or null
     */
    private function getPosterUrl(\stdClass $result): string
    {
        return ($result && !empty($result->poster_path)) ? self::BASE_IMAGE_PATH.$result->poster_path : null;
    }

    /**
     * Send request to the movie database api.
     *
     * @param $searchUrl
     * @param array $query
     * @param string $method
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function sendRequest($searchUrl, $query = null, $method = 'GET')
    {
        if ($query == null) {
            $query = [
                'query' => [
                    'api_key' => $this->apiKey,
                ],
            ];
        }

        $response = $this->client->request($method,$searchUrl, $query);

        // Respect api rate limits: https://developers.themoviedb.org/3/getting-started/request-rate-limiting
        // If 429 rate limit has been hit. Retry request after Retry-After.
        if (429 === $response->getStatusCode()) {
            $retryAfterHeader = $response->getHeader('Retry-After');
            if (is_numeric($retryAfterHeader)) {
                $retryAfter = (int) $retryAfterHeader;
            } else {
                $retryAfter = (new \DateTime($retryAfterHeader))->format('U') - time();
            }

            // Rate limit hit. Wait until 'Retry-After' header, then retry.
            $this->logger->alert(sprintf('Rate limit hit. Sleeping for %d seconds', $retryAfter));

            sleep($retryAfter);

            // Retry request.
            $response = $this->client->request($method, $searchUrl, $query);
        }

        $content = $response->getBody()->getContents();

        return json_decode($content, false);
    }
}
