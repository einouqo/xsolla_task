<?php
    require_once __DIR__.'/../vendor/autoload.php';

    $config = require_once __DIR__ . '/../src/settings.php';
    $app = new \Slim\App($config);

    require_once __DIR__.'/../src/router.php';

    $app->run();