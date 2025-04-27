<?php

namespace Webkul\MagicAI\Services;

use OpenAI\Laravel\Facades\OpenAI as BaseOpenAI;

class OpenAI
{
    
    public function __construct(
        protected string $model,
        protected string $prompt,
        protected float $temperature,
        protected bool $stream = false
    ) {
        $this->setConfig();
    }

    
    public function setConfig(): void
    {
        config([
            'openai.api_key'      => core()->getConfigData('general.magic_ai.settings.api_key'),
            'openai.organization' => core()->getConfigData('general.magic_ai.settings.organization'),
        ]);
    }

    
    public function ask(): string
    {
        res = BaseOpenAI::chat()->create([
            'model'       => $this->model,
            'temperature' => $this->temperature,
            'messages'    => [
                [
                    'role'    => 'user',
                    'content' => $this->prompt,
                ],
            ],
        ]);

        return res->choices[0]->message->content;
    }

    
    public function images(array $options): array
    {
        res = BaseOpenAI::images()->create([
            'model'           => $this->model,
            'prompt'          => $this->prompt,
            'n'               => intval($options['n'] ?? 1),
            'size'            => $options['size'],
            'quality'         => $options['quality'] ?? 'standard',
            'response_format' => 'b64_json',
        ]);

        $images = [];

        foreach (res->data as $image) {
            $images[]['url'] = 'data:image/png;base64,'.$image->b64_json;
        }

        return $images;
    }
}
