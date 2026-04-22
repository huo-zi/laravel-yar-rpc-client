<?php

namespace Huozi\Yar\Rpc\Client\Commands;

use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeYarClientFacade extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:rpc_facade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create rpc client Facades';


    /**
     * @var Filesystem
     */
    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function handle()
    {
        foreach (config('yar_client.clients', []) as $alias => $config) {
            $methods = [];
            preg_match_all('/\w+\:\:\w+\([^\)]*\)/', @file_get_contents($config['url']), $methods);

            $this->createFacade($alias, implode("\n * @method static ", $methods));
        }

        $this->info('Rpc client Facades Created Successfully!');
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
     *
     * @param  string  $alias
     * @return string
     */
    protected function createFacade($alias, $methods)
    {
        $path = lcfirst(str_replace('\\', '/', config('yar_client.namespace', 'App\\Rpc\\Clients\\')));
        $path = $this->laravel->basePath($path);

        $content = $this->formatFacadeStub(
            $alias, $methods, file_get_contents(__DIR__.'/stubs/facade.stub')
        );
        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
     *
     * @param  string  $alias
     * @param  string  $stub
     * @return string
     */
    protected function formatFacadeStub($alias, $methods, $stub)
    {
        $replacements = [
            config('yar_client.namespace', 'App\\Rpc\\Clients\\') . Str::studly($alias),
            Str::studly($alias),
            'rpc.client.' . $alias,
            $methods,
        ];

        return str_replace(
            ['DummyNamespace', 'DummyName', 'DummyTarget', 'DummyMethod'], $replacements, $stub
        );
    }
}