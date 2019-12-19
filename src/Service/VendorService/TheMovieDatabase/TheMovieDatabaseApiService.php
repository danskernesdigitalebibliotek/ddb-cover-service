<?php

/**
 * @file
 * Contains TheMovieDatabaseApiService for searching in TheMovieDatabase.
 */

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
     * Search in the movie database for a poster url by title, year and director.
     *
     * @param string $title
     *   The title of the item
     * @param string $originalYear
     *   The release year of the item
     * @param string|null $director
     *   The director of the movie
     *
     * @return string
     *   The poster url or null
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function searchPosterUrl(string $title = null, string $originalYear = null, string $director = null): ?string
    {
        $posterUrl = null;

        // Bail out if the required information is not supplied.
        if (null === $title || null === $originalYear || null === $director) {
            return $posterUrl;
        }

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

        try {
            $responseData = $this->sendRequest(self::SEARCH_URL, $query);

            $result = $this->getResultFromSet($responseData->results, $title, $director);

            if ($result) {
                $posterUrl = $this->getPosterUrl($result);
            }
        } catch (\Exception $e) {
            // Catch all exceptions to avoid crashing.
            $posterUrl = null;
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
     * @param string $director
     *   The director of the item
     *
     * @return \stdClass|null
     *   The matching result or null
     */
    private function getResultFromSet(array $results, string $title, string $director): ?\stdClass
    {
        $lowercaseResultTitle = mb_strtolower($title, 'UTF-8');
        $lowercaseDirector = mb_strtolower($director, 'UTF-8');

        $chosenResult = null;

        foreach ($results as $result) {
            // Validate title againt result->title or result->original_title.
            if (mb_strtolower($result->title, 'UTF-8') === $lowercaseResultTitle || mb_strtolower($result->original_title, 'UTF-8') === $lowercaseResultTitle) {
                // Validate director.
                try {
                    // https://developers.themoviedb.org/3/movies/get-movie-credits
                    $queryUrl = 'https://api.themoviedb.org/3/movie/'.$result->id.'/credits';
                    $responseData = $this->sendRequest($queryUrl);

                    $directors = array_reduce($responseData->crew, function ($carry, $item) {
                        if ('Director' === $item->job) {
                            $carry[] = mb_strtolower($item->name, 'UTF-8');
                        }

                        return $carry;
                    }, []);

                    if (in_array($lowercaseDirector, $directors)) {
                        // If more that one director, bail out.
                        if (null !== $chosenResult) {
                            return null;
                        }
                        $chosenResult = $result;
                    }
                } catch (GuzzleException $e) {
                    // Ignore error.
                } catch (\Exception $e) {
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
    private function getPosterUrl(\stdClass $result): ?string
    {
        return ($result && !empty($result->poster_path)) ? self::BASE_IMAGE_PATH.$result->poster_path : null;
    }

    /**
     * Send request to the movie database api.
     *
     * @param string $queryUrl
     *   The query url
     * @param array  $query
     *   The query. Remember to add the api key to the query
     * @param string $method
     *   The request method
     *
     * @return \stdClass
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    private function sendRequest(string $queryUrl, array $query = null, string $method = 'GET'): \stdClass
    {
        // Default to always supplying the api key in the query.
        if (null === $query) {
            $query = [
                'query' => [
                    'api_key' => $this->apiKey,
                ],
            ];
        }

        // Send the request to The Movie Database.
        $response = $this->client->request($method, $queryUrl, $query);

        // Respect api rate limits: https://developers.themoviedb.org/3/getting-started/request-rate-limiting
        // If 429 rate limit has been hit. Retry request after Retry-After.
        if (429 === $response->getStatusCode()) {
            $retryAfterHeader = $response->getHeader('Retry-After');
            if (is_numeric($retryAfterHeader)) {
                $retryAfter = (int) $retryAfterHeader;
            } else {
                $retryAfter = (int) ((new \DateTime($retryAfterHeader))->format('U')) - time();
            }

            // Rate limit hit. Wait until 'Retry-After' header, then retry.
            $this->logger->alert(sprintf('Rate limit hit. Sleeping for %d seconds', $retryAfter + 1));

            sleep($retryAfter);

            // Retry request.
            $response = $this->client->request($method, $queryUrl, $query);
        }

        // Get the response content.
        $content = $response->getBody()->getContents();

        try {
            return json_decode($content, false, 512, JSON_THROW_ON_ERROR);
        } catch (\Exception $exception) {
            return new \stdClass();
        }
    }
}
