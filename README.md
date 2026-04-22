# Laravel YAR RPC Client

基于 Laravel 框架服务注册及门面的 YAR 客户端，提供优雅的 YAR RPC 服务调用。

## 功能特性

- 🚀 优雅的 RPC 调用方式，支持 Laravel Facade
- ⚡ 支持 HTTP Keep-Alive 持久连接
- 📝 自动生成类型提示完善的 Facade 类
- 🔧 灵活的全局配置和客户端级配置
- 🔄 支持多种序列化方式（PHP、JSON、MsgPack）
- ⏱️ 使用延迟加载（`DeferrableProvider` 接口）按需实例化提升性能

## 依赖要求

- PHP >= 7.3 || >= 8.0
- Laravel >= 5.8
- PHP YAR 扩展 (`ext-yar`)

## 安装

```bash
composer require huo-zi/laravel-yar-rpc-client
```

## 配置

### 1. 发布配置文件

```bash
php artisan vendor:publish --provider="Huozi\Yar\Rpc\Client\ServiceProvider"
```

### 2. 配置 RPC 服务端

编辑 `config/yar_client.php` 文件：

```php
return [
    // Facade 类生成的命名空间
    'namespace' => 'App\\Rpc\\Clients\\',

    // 全局配置
    'options' => [
        // 调用超时时间（毫秒）
        \YAR_OPT_TIMEOUT => 5000,

        // 连接超时时间（毫秒）
        \YAR_OPT_CONNECT_TIMEOUT => 1000,

        // 序列化方式：php, json, msgpack
        \YAR_OPT_PACKAGER => 'php',

        // 是否启用 HTTP Keep-Alive
        \YAR_OPT_PERSISTENT => true,
    ],

    // RPC 客户端配置
    'clients' => [
        'user' => [
            'url' => 'http://api.example.com/user',
            // 客户端级配置（可选，会覆盖全局配置）
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

## 使用

### 1. 生成 Facade

```bash
php artisan make:rpc_facade
```

此命令会自动：
- 爬取服务端的 API 方法列表
- 在 `App/Rpc/Clients/` 目录生成对应的 Facade 类

### 2. 调用 RPC 方法

```php
use App\Rpc\Clients\UserRpcClient;
use App\Rpc\Clients\OrderRpcClient;

// 调用远程方法
$user = UserRpcClient::getUser(1);
$users = UserRpcClient::listUsers(['page' => 1, 'limit' => 10]);

// 调用订单服务
$order = OrderRpcClient::getOrder(123);
```

## 配置选项说明

| 配置项 | 默认值 | 说明 |
|--------|--------|------|
| `namespace` | `App\Rpc\Clients\` | Facade 类生成目录 |
| `options.timeout` | 5000ms | 调用超时时间 |
| `options.connect_timeout` | 1000ms | 连接超时时间 |
| `options.packager` | `php` | 序列化方式 |
| `options.persistent` | `true` | 是否启用持久连接 |

## 服务注册原理

服务提供者已实现 `DeferrableProvider` 接口，支持延迟加载：

```php
// 服务容器键名格式
app('rpc.client.user');
app('rpc.client.order');
```

**延迟加载机制：**
- 服务提供者实现了 `provides()` 方法，返回服务标识列表
- 当首次调用某个 RPC Facade 时，Laravel 才会注册对应的服务提供者并实例化客户端

## 更新配置后的缓存清理

当你更新了 `config/yar_client.php` 中的客户端配置后，需要清理以下缓存文件：

```bash
php artisan clear-compiled
```

**注意：** 在生产环境中更新 RPC 配置后，务必执行缓存清理，否则新配置不会生效。

## License

MIT License