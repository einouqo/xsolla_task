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
                try {
                    return $c['response']->withStatus($exception->getCode())->write($exception->getMessage());
                } catch (Exception $e) {
                    return $c['response']->withStatus(500)->write('Database error. Operation are failed.');
                }
            };
        }
    ];