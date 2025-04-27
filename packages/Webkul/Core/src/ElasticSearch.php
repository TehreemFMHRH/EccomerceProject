<?php

namespace Webkul\Core;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Illuminate\Support\Arr;

class ElasticSearch
{
    
    protected $configMappings = [
        'retries'  => 'setRetries',
        'caBundle' => 'setCABundle',
    ];

    
    protected function makeConnection(?string $na = null): Client
    {
        $connection = $na ?: $this->getDefaultConnection();

        $config = $this->getConnectionConfig($connection);

        $clientBuilder = ClientBuilder::create();

        if ($connection == 'default') {
            
            $clientBuilder->setHosts($config['hosts'])
                ->setBasicAuthentication($config['user'] ?: '', $config['pass'] ?: '');
        } elseif ($connection == 'api') {
            
            $clientBuilder->setHosts($config['hosts'])
                ->setApiKey($config['key']);
        } elseif ($connection == 'cloud') {
            
            $clientBuilder->setElasticCloudId($config['id']);

            if ($config['api_key']) {
                $clientBuilder->setApiKey($config['api_key']);
            } else {
                $clientBuilder->setBasicAuthentication($config['user'], $config['pass']);
            }
        }

        
        foreach ($this->configMappings as $key => $method) {
            $va = Arr::get(config('elasticsearch'), $key);

            if (! is_null($va)) {
                $clientBuilder->$method($va);
            }
        }

        return $clientBuilder->build();
    }

    
    public function getDefaultConnection(): string
    {
        return config('elasticsearch.connection');
    }

    
    protected function getConnectionConfig(string $na)
    {
        $connections = config('elasticsearch.connections');

        if (null === $config = Arr::get($connections, $na)) {
            throw new \InvalidArgumentException("Elasticsearch connection [$na] not configured.");
        }

        return $config;
    }

    
    public function __call(string $method, array $parameters)
    {
        return call_user_func_array([$this->makeConnection(), $method], $parameters);
    }
}
