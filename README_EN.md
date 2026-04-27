# Laravel YAR RPC Client

A YAR RPC client based on Laravel framework service registration and facade, providing elegant YAR RPC service calls.

## Features

- 🚀 Elegant RPC calling with Laravel Facade support
- ⚡ Supports HTTP Keep-Alive persistent connections
- 📝 Auto-generates Facade classes with type hints
- 🔧 Flexible global and client-level configurations
- 🔄 Supports multiple serialization formats (PHP, JSON, MsgPack)
- ⏱️ Supports deferred loading (implements `DeferrableProvider` interface) for on-demand instantiation

## Requirements

- PHP >= 7.3 || >= 8.0
- Laravel >= 5.8
- PHP YAR extension (`ext-yar`)

## Installation

```bash
composer require huo-zi/laravel-yar-rpc-client
```

## Configuration

### 1. Publish Configuration File

```bash
php artisan vendor:publish --provider="Huozi\Yar\Rpc\Client\ServiceProvider"
```

### 2. Configure RPC Servers

Edit `config/yar_client.php`:

```php
return [
    // Namespace for generated Facade classes
    'namespace' => 'App\\Rpc\\Clients\\',

    // Enable deferred loading
    // When enabled, RPC clients are instantiated only on first call, improving application startup performance
    // ServiceProvider implements DeferrableProvider interface for deferred loading
    'defer' => true,

    // Global options
    'options' => [
        // Call timeout (milliseconds)
        \YAR_OPT_TIMEOUT => 5000,

        // Connection timeout (milliseconds)
        \YAR_OPT_CONNECT_TIMEOUT => 1000,

        // Serialization format: php, json, msgpack
        \YAR_OPT_PACKAGER => 'php',

        // Enable HTTP Keep-Alive
        \YAR_OPT_PERSISTENT => true,
    ],

    // RPC client configurations
    'clients' => [
        'user' => [
            'url' => 'http://api.example.com/user',
            // Client-level options (optional, overrides global options)
            'options' => [
                \YAR_OPT_TIMEOUT => 3000,
            ],
        ],
        'order' => [
            'url' => 'http://api.example.com/order',
        ],
    ],
];
```

## Usage

### 1. Auto-generate Facades

```bash
php artisan make:rpc_facade
```

This command will automatically:
- Crawl the API method list from the server
- Generate corresponding Facade classes in `App/Rpc/Clients/` directory

### 2. Call RPC Methods

```php
use App\Rpc\Clients\UserRpcClient;
use App\Rpc\Clients\OrderRpcClient;

// Call remote methods
$user = UserRpcClient::getUser(1);
$users = UserRpcClient::listUsers(['page' => 1, 'limit' => 10]);

// Call order service
$order = OrderRpcClient::getOrder(123);
```

### 3. Concurrent Calls

The library supports concurrent calls to multiple RPC services to improve request efficiency:

#### 3.1 Magic Method Call (Recommended)

Using magic method call for cleaner code:

```php
use App\Rpc\Clients\ConcurrentRpcClient;

// Concurrent calls to multiple services (magic method way)
$result = ConcurrentRpcClient::user('getUser', [1])
    ->order('getOrder', [123])
    ->execute();

// Get results
$user = $result->get('user.getUser');
$order = $result->get('order.getOrder');
```

#### 3.2 Using call() Method

Using `call()` method for more flexibility:

```php
use App\Rpc\Clients\ConcurrentRpcClient;

// Concurrent calls to multiple services (call() method way)
$result = ConcurrentRpcClient::call('user', 'getUser', [1])
    ->call('order', 'getOrder', [123])
    ->execute();

// Get results
$user = $result->get('user.getUser');
$order = $result->get('order.getOrder');
```

#### 3.3 Result Format

```php
// Success example
[
    'user.getUser' => [
        'code' => 0, // 0 means success
        'data' => $userData // Data returned by the remote method
    ],
    'order.getOrder' => [
        'code' => 0,
        'data' => $orderData
    ]
]

// Failure example (one service call failed)
[
    'user.getUser' => [
        'code' => 0, // Success
        'data' => $userData
    ],
    'order.getOrder' => [
        'code' => 1, // Failure, yar error code
        'message' => 'Connect timeout' // Error message
    ]
]
```

**Concurrent Call Features:**
- All requests are sent simultaneously, reducing waiting time
- Automatically handles errors, even if one service fails, it won't affect other services
- Results are organized in `client.method` format for easy access
- Supports method chaining for cleaner code
- Supports two calling methods: magic method and call() method

## Configuration Options

| Option | Default | Description |
|--------|--------|-------------|
| `namespace` | `App\Rpc\Clients\` | Directory for generated Facade classes |
| `options.timeout` | 5000ms | Call timeout duration |
| `options.connect_timeout` | 1000ms | Connection timeout duration |
| `options.packager` | `php` | Serialization format |
| `options.persistent` | `true` | Enable persistent connections |

## Service Registration Principle

The service provider implements the `DeferrableProvider` interface for deferred loading:

```php
// Service container key format
app('rpc.client.user');
app('rpc.client.order');
```

**Deferred Loading Mechanism:**
- The service provider implements `provides()` method, returning a list of service identifiers
- When an RPC Facade is first called, Laravel registers the corresponding service provider and instantiates the client

## Cache Clearing After Configuration Updates

After updating the RPC client configuration in `config/yar_client.php`, you need to clear the following cache files:

```bash
php artisan clear-compiled
```

**Note:** Always clear the cache after updating RPC configuration in production environment, otherwise the new configuration will not take effect.

## License

MIT License