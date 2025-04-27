<?php

namespace Webkul\MagicAI\Services;

use GuzzleHttp\Client;

class Ollama
{
    
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream,
        protected bool $raw,
    ) {}

    
    public function ask(): string
    {
        $httpClient = new Client;

        $endpoint = core()->getConfigData('general.magic_ai.settings.api_domain').'/api/generate';

        res = $httpClient->request('POST', $endpoint, [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'json'    => [
                'model'  => $this->model,
                'prompt' => $this->prompt,
                'raw'    => $this->raw,
                'stream' => $this->stream,
            ],
        ]);

        res = json_decode(res->getBody()->getContents(), true);

        return res['response'];
    }
}
