<?php

namespace Huozi\Yar\Rpc\Client\Concurent;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConcurrentService
{

    private $config;

    protected $pipes = [];

    public function __construct($config)
    {
        $this->config = $config;
    }

    public function call(string $clientName, string $method, array $params = [], array $options = [])
    {
        $config = $this->config("clients.{$clientName}");

        if (! $config || !isset($config['url'])) {
            throw new \InvalidArgumentException("Client [{$clientName}] is not configured.");
        }

        $this->pipes[] = new ConcurentCall(
            $clientName, 
            $config,
            $method, 
            $params,
            $options + $this->config('options'),
        );

        return $this;
    }

    public function execute(): Collection
    {
        if (empty($this->pipes)) {
            return collect();
        }

        $collect = (new Pipeline())
            ->send(collect())
            ->through($this->pipes)
            ->then(function ($collect) {
                \Yar_Concurrent_Client::loop();
                return $collect;
            });

        return $collect;
    }

    public function __call($method, $parameters)
    {
        if (!$this->config("clients.{$method}")) {
            throw new \InvalidArgumentException('Client name is required.');
        }

        \array_unshift($parameters, $method);
        return \call_user_func_array([$this, 'call'], $parameters);
    }

    protected function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}