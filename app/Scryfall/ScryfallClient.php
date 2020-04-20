<?php
namespace App\Scryfall;

use GuzzleHttp\Client as HttpClient;

class ScryfallClient
{
    protected $httpClient;

    public function __construct(HttpClient $httpClient)
    {

        $this->httpClient = $httpClient;
        $this->url = 'https://api.scryfall.com';
    }

    public function search($query)
    {
        $url = $this->url.'/cards/search?'.http_build_query(['q' => $query]);
        $rawResponse = $this->httpClient->request('get', $url, ['http_errors' => false]);
        $response = json_decode($rawResponse->getBody(), true);

        return $response;
    }
}

