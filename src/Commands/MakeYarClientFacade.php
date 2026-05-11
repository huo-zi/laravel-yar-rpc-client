<?php

namespace Huozi\Yar\Rpc\Client\Commands;

use Huozi\Yar\Rpc\Client\Concurent\ConcurrentService;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
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
     * The filesystem instance.
     *
     * @var Filesystem
     */
    private $filesystem;

    /**
     * Create a new MakeYarClientFacade instance.
     *
     * @param  Filesystem  $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->cleanOldFacades();

        $this->createConcurrentFacade();

        foreach (config('yar_client.clients', []) as $alias => $config) {
            $methods = [];
            preg_match_all('/\w+\:\:(\w+\([^\)]*\))/', @file_get_contents($config['url']), $methods);

            $this->createFacade($alias, implode("\n * @method static mixed ", $methods[1]));
        }

        $this->info('Rpc client Facades Created Successfully!');
    }

    /**
     * Clean up the old facade files
     *
     * @return void
     */
    protected function cleanOldFacades()
    {
        $facades = $this->filesystem->allFiles($this->getFacadePath());
        $this->filesystem->delete($facades);
    }

    /**
     * Create concurrent client facade.
     *
     * @return void
     */
    protected function createConcurrentFacade()
    {
        $path = $this->getFacadePath() . 'ConcurrentRpcClient.php';
        $class = ConcurrentService::class;

        $clients = array_map(function($client) {
            return $client . '(string $method, array $params = [], array $options = [])';
        }, array_keys(config('yar_client.clients', [])));
        $content = str_replace(
            [
                'DummyNamespace', 
                'DummyName', 
                'DummyTarget', 
                'DummyMethod'
            ], [
                \rtrim(config('yar_client.namespace', 'App\\Rpc\\Clients\\'), '\\'),
                'Concurrent',
                $class,
                implode("\n * @method static static|\\{$class} ", array_merge(['execute()', 'call(string $clientName, string $method, array $params = [], array $options = [])'], $clients)),
            ], $this->getStubs()
        );
        $this->filesystem->put($path, $content);
    }

    /**
     * Ensure that the given alias has an existing real-time facade class.
     *
     * @param  string  $alias
     * @param  string  $methods
     * @return string
     */
    protected function createFacade($alias, $methods)
    {
        $path = $this->getFacadePath() . Str::studly($alias) . 'RpcClient.php';

        $content = $this->formatFacadeStub(
            $alias, $methods, $this->getStubs()
        );
        $this->filesystem->put($path, $content);

        return $path;
    }

    /**
     * Format the facade stub with the proper namespace and class.
     *
     * @param  string  $alias
     * @param  string  $methods
     * @param  string  $stub
     * @return string
     */
    protected function formatFacadeStub($alias, $methods, $stub)
    {
        $replacements = [
            \rtrim(config('yar_client.namespace', 'App\\Rpc\\Clients\\'), '\\'),
            Str::studly($alias),
            'rpc.client.' . $alias,
            $methods,
        ];

        return str_replace(
            ['DummyNamespace', 'DummyName', 'DummyTarget', 'DummyMethod'], $replacements, $stub
        );
    }

    /**
     * Get the facade path.
     *
     * @return string
     */
    protected function getFacadePath()
    {
        $path = \str_replace([$this->laravel->getNamespace(), '\\'], ['', \DIRECTORY_SEPARATOR], config('yar_client.namespace', 'App\\Rpc\\Clients\\'));
        return $this->laravel->path . \DIRECTORY_SEPARATOR . $path;
    }

    /**
     * Get the stubs.
     *
     * @return string
     */
    protected function getStubs()
    {
        return file_get_contents(__DIR__.'/stubs/facade.stub');
    }
}