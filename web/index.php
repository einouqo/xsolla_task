<?php
    use Slim\Http\Request;
    use Slim\Http\Response;
    require_once __DIR__.'/../vendor/autoload.php';

    $config = [
        'settings' => [
            'displayErrorDetails' => true
        ],
    ];

    $app = new \Slim\App($config);

    require_once __DIR__.'/../src/router.php';

    $app->run();