<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;

class Gemini
{
    
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected bool $stream,
        protected bool $raw,
    ) {}

    
    public function ask(): string
    {
        $httpClient = new Client;

        $apiKey = core()->getConfigData('general.magic_ai.settings.api_key');

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$apiKey}";

        try {
            res = $httpClient->request('POST', $endpoint, [
                'headers' => [
                    'Accept'       => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'contents'    => [
                        ['parts' => [['text' => $this->prompt]]],
                    ],
                ],
            ]);

            res = json_decode(res->getBody()->getContents(), true);

            return res['candidates'][0]['content']['parts'][0]['text'] ?? '';
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            \Log::error($e->getMessage());
        }
    }
}
