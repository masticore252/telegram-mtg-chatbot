<?php

namespace App\Chatbot;

use Throwable;
use GuzzleHttp\Client as GuzzleClient;

class TelegramChatbot
{
    protected $url;

    protected $httpClient;

    public function __construct( GuzzleClient $httpClient)
    {
        $this->url = "https://api.telegram.org/bot".env('TELEGRAM_TOKEN');
        $this->httpClient = $httpClient;
    }

    public function HandleMessage($input)
    {
        $chatId = $input['message']['chat']['id'];
        $messageId = $input['message']['message_id'];

        $text = $input['message']['text'];
        $entities = array_filter($input['message']['entities'], function($entity){ return $entity['type'] === 'bot_command'; });

        foreach ($entities as $entity) {

            $commandStart = $entity['offset'];
            $commandLength = $entity['length'];

            $queryStart = 1 + $commandStart + $commandLength;
            $queryEndPos = mb_strpos($text, "\n", $queryStart);
            $queryEnd = $queryEndPos !== false ? $queryEndPos - $queryStart : null;

            $command = mb_substr($text, $commandStart, $commandLength);
            $query = mb_substr($text, $queryStart, $queryEnd);

            switch ($command) {
                case '/card':
                    $this->handleCardCommand($query, $chatId, $messageId);
                break;
            }
        }


    }

    public function handleInlineQuery($input)
    {
        /** TODO not implemented yet*/
        throw new Exception('inline_query type is not handled yet');
    }

    protected function handleCardCommand($query, $chatId, $messageId)
    {
        $url = 'https://api.scryfall.com/cards/named?'.http_build_query([ 'fuzzy' => $query ]);
        $response = $this->httpClient->request('get', $url);
        $cardData = json_decode($response->getBody(), true);
        $photo = $cardData['image_uris']['normal'];

        return $this->sendphoto($chatId, $photo, ['reply_to_message_id' => $messageId]);
    }

    protected function sendPhoto($chatId, $photo, $extra)
    {
        $data = array_merge_recursive( $extra, [
            'chat_id' => $chatId,
            'photo' => $photo,
        ]);

        $url = $this->url.'/sendPhoto?'. http_build_query($data);
        $response = $this->httpClient->request('GET', $url);

        return [
            'telegran_request' => $data,
            'telegran_response' => json_decode((string)$response->getBody(), true),
            'endpoint' => '/sendPhoto',
        ];
    }

    protected function sendMessage($chatId, $text, $extra)
    {
        $data = array_merge_recursive( $extra, [
            'chat_id' => $chatId,
            'text' => $text,
        ]);

        $url = $this->url.'/sendMessage?'. http_build_query($data);
        $response = $this->httpClient->request('GET', $url);

        return [
            'telegran_request' => $data,
            'telegran_response' => json_decode((string)$response->getBody(), true),
            'endpoint' => '/sendMessage'
        ];
    }

}

