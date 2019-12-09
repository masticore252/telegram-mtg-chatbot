<?php

namespace App\Http\Controllers;

use Throwable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Log\LogManager as Log;

use App\Chatbot\TelegramChatbot;

class ChatbotController extends Controller
{

    public function __invoke(Request $request, TelegramChatbot $chatbot, Log $log) {

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
                    $log->error(get_class($th), [
                        'message' => $th->getMessage(),
                        'code' => $th->getCode(),
                        'file' => $th->getFile(),
                        'line' => $th->getLine(),
                        'trace' => $th->getTraceAsString(),
                    ]);
                }

            }

        }
    }
}
