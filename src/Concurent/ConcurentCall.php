<?php

namespace Huozi\Yar\Rpc\Client\Concurent;

use Illuminate\Support\Collection;

class ConcurentCall
{

    /**
     * The client name.
     *
     * @var string
     */
    private $name;

    /**
     * The client configuration.
     *
     * @var array
     */
    private $config;

    /**
     * The method name.
     *
     * @var string
     */
    private $method;

    /**
     * The parameters.
     *
     * @var array
     */
    private $params;

    /**
     * The options.
     *
     * @var array
     */
    private $options;

    /**
     * Create a new ConcurentCall instance.
     *
     * @param  string  $name
     * @param  array  $config
     * @param  string  $method
     * @param  array  $params
     * @param  array  $options
     */
    public function __construct($name, $config, $method, $params = [], $options = [])
    {
        $this->name = $name;
        $this->config = $config;
        $this->method = $method;
        $this->params = $params;
        $this->options = $options;
    }

    /**
     * Handle the concurrent call.
     *
     * @param  Collection  $collect
     * @param  callable  $stack
     * @return mixed
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
