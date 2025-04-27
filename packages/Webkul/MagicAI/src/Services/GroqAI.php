<?php

namespace Webkul\MagicAI\Services;

use Illuminate\Support\Facades\Http;

class GroqAI
{
    
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    
    private const TEMPERATURE = 0.7;

    
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature = self::TEMPERATURE,
        protected bool $stream = false
    ) {
        $this->setConfig();
    }

    
    public function setConfig(): void
    {
        config([
            'groq.api_key' => core()->getConfigData('general.magic_ai.settings.api_key'),
        ]);
    }

    
    public function ask(): string
    {
        try {
            $resp = Http::withHeaders([
                'Authorization' => 'Bearer '.config('groq.api_key'),
                'Content-Type'  => 'application/json',
            ])->post(self::API_URL, [
                'model'       => $this->model,
                'temperature' => $this->temperature,
                'messages'    => [
                    [
                        'role'    => 'user',
                        'content' => $this->prompt,
                    ],
                ],
            ]);

            res = $resp->json();

            return res['choices'][0]['message']['content'] ?? '';
        } catch (\Exception $e) {
            return 'Exception: '.$e->getMessage();
        }
    }
}
