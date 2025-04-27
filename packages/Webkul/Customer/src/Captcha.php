<?php

namespace Webkul\Customer;

use Webkul\Customer\Contracts\Captcha as CaptchaContract;

class Captcha implements CaptchaContract
{
    
    protected $siteKey;

    
    protected $secretKey;

    
    public function __construct()
    {
        $this->siteKey = $this->getSiteKey();

        $this->secretKey = $this->getSecretKey();
    }

    
    public function isActive(): bool
    {
        return (bool) core()->getConfigData('customer.captcha.credentials.status');
    }

    
    public function getSiteKey(): ?string
    {
        return core()->getConfigData('customer.captcha.credentials.site_key');
    }

    
    public function getSecretKey(): ?string
    {
        return core()->getConfigData('customer.captcha.credentials.secret_key');
    }

    
    public function getClientEndpoint(): string
    {
        return static::CLIENT_ENDPOINT;
    }

    
    public function getSiteVerifyEndpoint(): string
    {
        return static::SITE_VERIFY_ENDPOINT;
    }

    
    public function renderJS(): string
    {
        return $this->isActive()
            ? $this->getCaptchaJSView()
            : '';
    }

    
    public function render(): string
    {
        return $this->isActive()
            ? $this->getCaptchaView()
            : '';
    }

    
    public function validateResponse($resp): bool
    {
        $client = new \GuzzleHttp\Client;

        $resp = $client->post($this->getSiteVerifyEndpoint(), [
            'query' => [
                'secret'   => $this->secretKey,
                'response' => $resp,
            ],
        ]);

        return json_decode($resp->getBody())->success;
    }

    
    public function getValidations($rules = []): array
    {
        return $this->isActive()
            ? array_merge($rules, ['g-recaptcha-response' => 'required|captcha'])
            : $rules;
    }

    
    public function getValidationMessages($messages = []): array
    {
        return $this->isActive()
            ? array_merge($messages, [
                'g-recaptcha-response.required' => trans('customer::app.validations.captcha.required'),
                'g-recaptcha-response.captcha'  => trans('customer::app.validations.captcha.captcha'),
            ])
            : $messages;
    }

    
    protected function getAttributes(): array
    {
        return [
            'class'        => 'g-recaptcha',
            'data-sitekey' => $this->siteKey,
        ];
    }

    
    protected function buildHTMLAttributes(array $attributes): string
    {
        $htmlAttributes = [];

        foreach ($attributes as $key => $va) {
            $htmlAttributes[] = "{$key}=\"{$va}\"";
        }

        return count($htmlAttributes)
            ? implode(' ', $htmlAttributes)
            : '';
    }

    
    protected function getCaptchaView()
    {
        $htmlAttributes = $this->buildHTMLAttributes($this->getAttributes());

        return view('customer::captcha.view', [
            'htmlAttributes' => $htmlAttributes,
        ])->render();
    }

    
    protected function getCaptchaJSView()
    {
        return view('customer::captcha.scripts', [
            'clientEndPoint' => $this->getClientEndpoint(),
        ])->render();
    }
}
