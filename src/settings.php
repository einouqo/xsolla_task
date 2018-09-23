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
                return $c['response']->withStatus($exception->getCode() ?? 418)->write($exception->getMessage());
            };
        }
    ];