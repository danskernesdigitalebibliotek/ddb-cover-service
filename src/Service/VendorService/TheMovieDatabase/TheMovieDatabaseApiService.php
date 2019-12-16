<?php

namespace App\Service\VendorService\TheMovieDatabase;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class TheMovieDatabaseApiService
{
    private const SEARCH_URL = 'https://api.themoviedb.org/3/search/movie';
    private const BASE_IMAGE_PATH = 'https://image.tmdb.org/t/p/original';

    private $apiKey;
    private $client;

    public function __construct(string $apiKey, ClientInterface $httpClient)
    {
        $this->apiKey = $apiKey;
        $this->client = $httpClient;
    }

    public function searchPosterUrl(string $title, string $year): string
    {
        $posterUrl = '';

        // @TODO Respect api rate limits: https://developers.themoviedb.org/3/getting-started/request-rate-limiting

        try {
            $response = $this->client->request('GET', self::SEARCH_URL, [
                'query' => [
                    'query' => $title,
                    'year' => $year,
                    'api_key' => $this->apiKey,
                    'page' => '1',
                    'include_adult' => 'false',
                    'language' => 'da_DK',
                ],
            ]);

            $content = $response->getBody()->getContents();
            $jsonResponse = json_decode($content, false);

            $result = $this->getResultFromSet($jsonResponse->results, $title);

            $posterUrl = $this->getPosterUrl($result);
        } catch (GuzzleException $exception) {
            $d = 1;
        }

        return $posterUrl;
    }

    private function getResultFromSet(array $results, string $title): ?\stdClass
    {
        foreach ($results as $result) {
            if ($result->title === $title) {
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
