<?php
    require ('resources.php');

    $app->group('/company', function () use ($app) {
        $app->get('/list', 'company.controller:getList');
        $app->post('/create', 'company.controller:create');
        $app->delete('/{id}/delete', 'company.controller:delete');
    });

    $app->post('/registration', 'registration.controller:registration');

    $app->group('/user', function () use ($app) {
        $app->post('/login', 'user.controller:login');
        $app->group('/settings', function () use ($app) {
            $app->delete('/delete', 'user.controller:delete')->add('middleware');
            $app->put('/change', 'user.controller:change')->add('middleware');
        });
        $app->get('/exit', 'user.controller:logoff');
    });

    $app->group('/access', function () use ($app) {
        $app->post('/give', 'admin.controller:giveAccess');
        $app->delete('/delete', 'admin.controller:deleteAccess');
    })->add('middleware');

    $app->group('/rooms', function () use ($app) {
        $app->get('/list', 'admin.controller:getListRooms');
        $app->post('/add', 'admin.controller:addRoom');
        $app->delete('/{id}/delete', 'admin.controller:deleteRoom');
    })->add('middleware');

    $app->group('/warehouses', function () use ($app) {
        $app->get('/list', 'warehouse.controller:getList');
        $app->post('/create', 'admin.controller:createWarehouse');
        $app->group('/{id}', function () use ($app) {
            $app->get('/state', 'warehouse.controller:getOne');
            $app->get('/transfers', 'admin.controller:getTransfersForWarehouse');
            $app->put('/change', 'admin.controller:changeWarehouse');
            $app->delete('/delete', 'admin.controller:deleteWarehouse');
        });
        
        $app->group('/items', function () use ($app) {
            $app->group('/list', function () use ($app) {
                $app->get('/pending', 'employee.controller:pendingList');
                $app->get('/available','employee.controller:availableList');
            });
            $app->post('/take', 'employee.controller:takeTransfer');
            $app->group('/{id}', function () use ($app) {
                $app->delete('/sell', 'employee.controller:sellItem');
                $app->post('/toTransfer', 'employee.controller:addToTransfer');
            });
            $app->group('/transfers', function () use ($app) {
                $app->get('/list', 'employee.controller:showTransfer');
                $app->get('/clear', 'employee.controller:clearTransfer');
                $app->delete('/send', 'employee.controller:sendTransfer');
            });
        });
    })->add('middleware');

    $app->group('/items', function () use ($app) {
        $app->post('/add', 'admin.controller:addItem');
        $app->group('/{id}', function () use ($app) {
            $app->put('/change', 'admin.controller:changeItem');
            $app->get('/state', 'admin.controller:itemState');
            $app->get('/transfers', 'admin.controller:getTransfersForItem');
        });
    })->add('middleware');