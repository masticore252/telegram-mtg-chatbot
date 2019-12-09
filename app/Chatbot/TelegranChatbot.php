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
            $queryEndPos = (mb_strlen($text) <= $commandLength) ? false : mb_strpos($text, "\n", $queryStart);
            $queryEnd = $queryEndPos !== false ? $queryEndPos - $queryStart : null;

            $command = mb_substr($text, $commandStart, $commandLength);
            $query = mb_substr($text, $queryStart, $queryEnd);

            switch ($command) {
                case '/start':
                    $this->handleStartCommand($chatId, $messageId);
                break;

                case '/help':
                    $this->handleHelpCommand($chatId, $messageId);
                break;

                case '/card':
                    $this->handleCardCommand($query, $chatId, $messageId);
                break;
            }
        }


    }

    public function handleInlineQuery($input)
    {
        $id = $input['inline_query']['id'];
        $query = [
            'q' => urlencode($input['inline_query']['query'])
        ];

        $url = 'https://api.scryfall.com/cards/search?'.http_build_query($query);
        $rawResponse = $this->httpClient->request('get', $url, ['http_errors' => false]);
        $response = json_decode($rawResponse->getBody(), true);

        $results = [];


        if($response['object'] == 'list') {

            foreach ($response['data'] as $card) {

                if ($card['layout'] == 'transform') {

                    $pos = 0;
                    foreach ($card['card_faces'] as $face) {

                        $results[] = [
                            'type' => 'photo',
                            'id' => $card['multiverse_ids'][$pos++],
                            'photo_url' => $face['image_uris']['normal'],
                            'thumb_url' => $face['image_uris']['small'],
                        ];

                    }

                } else {
                    $results[] = [
                        'type' => 'photo',
                        'id' => $card['multiverse_ids'][0],
                        'photo_url' => $card['image_uris']['normal'],
                        'thumb_url' => $card['image_uris']['small'],
                    ];
                }

            }

        } else {
            $results[] = [
                'type' => 'article',
                'id' => 1,
                'title' => 'No results',
                'description' => $response['details'],
                'input_message_content' => [
                    'message_text' => $response['details'],
                ]
            ];
        }

        $this->answerInlineQuery($id, $results, 60*60*24*7);
    }

    public function handleStartCommand($chatId, $messageId)
    {
        $text = "Hi! I'm a Magic: the gathering bot\n";
        $text .= "I can help you find favorite cards\n";
        $text .= "just open any of your chats and type '@albertMTGbot Jace' (or your favorite card name) ";
        $text .= "in the message field and I'll show a list of search results\n";
        $text .= "tap one to see to preview it, tap ✅ to send it\n";
        $text .= "Easy peasy!\n\n";
        $text .= "You can also search more than just card names, use the full syntax from scryfall.com\n\n";
        $text .= "Read all about it here: https://scryfall.com/docs/syntax\n\n";
        $this->sendMessage($chatId, $text, $messageId);
    }

    public function handleHelpCommand($chatId, $messageId)
    {
        $text = "Coming soon!\n";
        $this->sendMessage($chatId, $text, $messageId);
    }

    protected function handleCardCommand($query, $chatId, $messageId)
    {
        $url = 'https://api.scryfall.com/cards/named?'.http_build_query([ 'fuzzy' => $query ]);
        $response = $this->httpClient->request('get', $url);
        $data = json_decode($response->getBody(), true);

        $cardData = [];

        if($data['layout'] == 'transform') {

            foreach ($data['card_faces'] as $face) {
                $photos[] = [
                    'type' => 'photo',
                    'media' => $face['image_uris']['normal']
                ];
            }

            return $this->sendMediaGroup($chatId, $photos, $messageId);

        } else {

            return $this->sendphoto($chatId, $data['image_uris']['normal'], $messageId);

        }

    }

    protected function sendPhoto($chatId, $photo, $replyTo = false, $extra = [])
    {
        $data = array_merge_recursive( $extra, [
            'chat_id' => $chatId,
            'photo' => $photo,
            'reply_to_message_id' => $replyTo,
        ]);

        $url = $this->url.'/sendPhoto?'. http_build_query(array_filter($data));
        $response = $this->httpClient->request('GET', $url);

        return [
            'telegran_request' => $data,
            'telegran_response' => json_decode((string)$response->getBody(), true),
            'endpoint' => '/sendPhoto',
        ];
    }

    protected function sendMessage($chatId, $text, $replyTo = false, $extra = [])
    {
        $data = array_merge_recursive( $extra, [
            'chat_id' => $chatId,
            'text' => $text,
            'reply_to_message_id' => $replyTo,
        ]);

        $url = $this->url.'/sendMessage?'. http_build_query(array_filter($data));
        $response = $this->httpClient->request('GET', $url);

        return [
            'telegran_request' => $data,
            'telegran_response' => json_decode((string)$response->getBody(), true),
            'endpoint' => '/sendMessage'
        ];
    }

    protected function sendMediaGroup($chatId, $media, $replyTo, $disableNotification = false)
    {

        $data = [
            'chat_id' => $chatId,
            'media' => $media,
            'reply_to_message_id' => $replyTo,
            'disable_notification' => $disableNotification
        ];

        $url = $this->url.'/sendMediaGroup';

        $response = $this->httpClient->request('POST', $url, [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);

    }

    protected function answerInlineQuery($id, $results, $cacheTime)
    {
        $url = $this->url.'/answerInlineQuery';
        $data = [
            'inline_query_id' => $id,
            'results' => $results,
            'cache_time' => $cacheTime
        ];

        $this->httpClient->request('POST', $url, [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json',
            ],
        ]);
    }

}

