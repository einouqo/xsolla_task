<?php
    return [
        'settings' => [
            'displayErrorDetails' => true
        ],
        'jwt' => [
            'secret' => 'supersecretkey'
        ],
        'errorHandler' => function ($c) {
            return function ($request, $response, $exception) use ($c) {
                return $exception instanceof \Doctrine\DBAL\DBALException ?
                    $c['response']->withStatus(500)->write('Database error. Operation are failed.'):
                    $c['response']->withStatus($exception->getCode())->write($exception->getMessage());
            };
        }
    ];