<?php
    require_once ('resources.php');

    $app->post('/registration', 'registration.controller:registration');

    $app->group('/user', function () use ($app) {
        $app->post('/authentication', 'user.controller:authentication');//проверить!
        $app->group('/settings', function() use ($app) {
            $app->delete('/delete', 'user.controller:delete');
            $app->put('/change', 'user.controller:change');
        });
    });