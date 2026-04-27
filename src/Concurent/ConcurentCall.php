<?php

namespace Huozi\Yar\Rpc\Client\Concurent;

use Illuminate\Support\Collection;

class ConcurentCall
{

    private $name;
    private $config;
    private $method;
    private $params;
    private $options;

    public function __construct($name, $config, $method, $params = [], $options = [])
    {
        $this->name = $name;
        $this->config = $config;
        $this->method = $method;
        $this->params = $params;
        $this->options = $options;
    }

    /**
     * 
     * @param Collection $collect
     * @param callable $stack
     */
    public function handle($collect, $stack)
    {
        \Yar_Concurrent_Client::call(
            $this->config['url'],
            $this->method,
            $this->params,
            function ($retval, $callinfo) use ($collect) {
                $collect->put("{$this->name}.{$this->method}", [
                    'code' => 0,
                    'data' => $retval,
                ]);
            },
            function ($type, $error, $callinfo) use ($collect) {
                $collect->put("{$this->name}.{$this->method}", [
                    'code' => $type,
                    'message' => $error,
                ]);
            },
            $this->options + ($this->config['options'] ?? [])
        );

        return $stack($collect);
    }
}
