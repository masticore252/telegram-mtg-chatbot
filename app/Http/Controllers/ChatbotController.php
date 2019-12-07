<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Sentry\State\Hub as Sentry;

use App\Chatbot\TelegramChatbot;

class ChatbotController extends Controller
{

    public function __invoke(Request $request, TelegramChatbot $chatbot, Sentry $sentry) {

        $input = new Collection($request->all());

        $types = [
            'message' =>        function(...$args) use($chatbot) { return $chatbot->handleMessage(...$args); },
            'inline_query' =>   function(...$args) use($chatbot) { return $chatbot->handleInlineQuery(...$args); },
        ];

        foreach ( $types as $type => $callable) {

            if ($input->has($type)) {

                try {
                    $callable($input);
                } catch (Throwable $th) {
                    $sentry->captureException($th);
                }

            }

        }
    }
}
