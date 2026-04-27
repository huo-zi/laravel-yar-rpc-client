<?php

namespace Huozi\Yar\Rpc\Client\Concurent;

use Illuminate\Pipeline\Pipeline;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ConcurrentService
{

    /**
     * The configuration.
     *
     * @var array
     */
    private $config;

    /**
     * The pipes.
     *
     * @var array
     */
    protected $pipes = [];

    /**
     * Create a new ConcurrentService instance.
     *
     * @param  array  $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Add an RPC call.
     *
     * @param  string  $clientName
     * @param  string  $method
     * @param  array  $params
     * @param  array  $options
     * @return $this
     * @throws \InvalidArgumentException
     */
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

    /**
     * Execute all concurrent calls.
     *
     * @return Collection
     */
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

    /**
     * Magic method to call client by method name.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function __call($method, $parameters)
    {
        if (!$this->config("clients.{$method}")) {
            throw new \InvalidArgumentException('Client name is required.');
        }

        \array_unshift($parameters, $method);
        return \call_user_func_array([$this, 'call'], $parameters);
    }

    /**
     * Helper to get the config values.
     *
     * @param  string  $key
     * @param  mixed  $default
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return Arr::get($this->config, $key, $default);
    }
}