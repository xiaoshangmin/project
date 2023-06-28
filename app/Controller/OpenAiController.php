<?php

namespace App\Controller;

use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\PostMapping;
use Orhanerday\OpenAi\OpenAi;
use Hyperf\HttpServer\Annotation\Controller;

#[Controller(prefix: "api/openai")]
class OpenAiController extends AbstractController
{

    #[GetMapping(path: "chat")]
    public function chat(){
        $open_ai_key = "";//getenv('OPENAI_API_KEY');
        $open_ai = new OpenAi($open_ai_key);

        $complete = $open_ai->chat([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    "role" => "system",
                    "content" => "You are a helpful assistant."
                ],
                [
                    "role" => "user",
                    "content" => "Who won the world series in 2020?"
                ],
                [
                    "role" => "assistant",
                    "content" => "The Los Angeles Dodgers won the World Series in 2020."
                ],
                [
                    "role" => "user",
                    "content" => "Where was it played?"
                ],
            ],
            'temperature' => 1.0,
            'max_tokens' => 4000,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
        ]);

        return $complete;
    }

}