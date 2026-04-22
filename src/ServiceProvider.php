<?php

namespace Huozi\Yar\Rpc\Client;

use Huozi\Yar\Rpc\Client\Commands\MakeYarClientFacade;
use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/yar_client.php' => config_path('yar_client.php'),
        ]);
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->registerClients();

        $this->commands(MakeYarClientFacade::class);
    }

    protected function registerClients()
    {
        foreach ($this->config('clients', []) as $name => $config) {
            $this->app->singleton('rpc.client.' . $name, function () use ($name, $config) {
                $client = new \Yar_client($config['url']);
                $options =  $this->config("clients.{$name}.options", []) + $this->config("options", []);
                foreach ($options as $key => $value) {
                    $client->setOpt($key, $value);
                }

                return $client;
            });
        }
    }

    /**
     * Helper to get the config values.
     *
     * @param  string  $key
     * @param  string  $default
     *
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->app['config']->get("yar_client.{$key}", $default);
    }

}