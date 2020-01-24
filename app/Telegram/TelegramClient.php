<?php
namespace App\Telegram;

use GuzzleHttp\Client as HttpClient;

class TelegramClient
{
    protected $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
        $this->url = "https://api.telegram.org/bot".env('TELEGRAM_TOKEN');
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
}

