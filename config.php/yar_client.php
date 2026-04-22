<?php

return [
    'namespace' => 'App\\Rpc\\Clients\\',

    // global options
    'options' => [
        // yar.timeout //default 5000 (ms)
        \YAR_OPT_TIMEOUT => 5000, // ms

        // yar.connect_timeout //default 1000 (ms)
        \YAR_OPT_CONNECT_TIMEOUT => 1000, // ms

        // yar.packager //default "php", when built with --enable-msgpack then default "msgpack", it should be one of "php", "json", "msgpack"
        \YAR_OPT_PACKAGER => 'php',

        // After Yar 2.1.0, if YAR_OPT_PERSISTENT is set to true, then Yar is able to use HTTP keep-alive to speedup repeated calls to a same address, 
        // the link will be released at the end of the PHP request lifecycle.
        \YAR_OPT_PERSISTENT => true,
    ],

    'clients' => [
        // "rpc_name" => [
        //     'url' => "http://hosr/api",
        //     // client options
        //     'options' => [

        //     ],
        // ],
    ],
];