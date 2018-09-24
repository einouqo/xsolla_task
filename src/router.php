<?php
    require_once ('resources.php');

    $app->post('/registration', 'registration.controller:registration');

    $app->group('/user', function () use ($app) {
        $app->post('/authentication', 'user.controller:authentication');
        $app->group('/settings', function () use ($app) {
            $app->delete('/delete', 'user.controller:delete');
            $app->put('/change', 'user.controller:change');
        });
        $app->get('/exit', 'user.controller:logoff');
    });

    $app->group('/access', function () use ($app) {
        $app->post('/give', 'admin.controller:giveAccess');
        $app->delete('/delete', 'admin.controller:deleteAccess');
    });

    $app->group('/rooms', function () use ($app) {
        $app->get('/list', 'admin.controller:getListRooms');
        $app->post('/add', 'admin.controller:addRoom');
        $app->delete('/delete', 'admin.controller:deleteRoom');
    });

    $app->group('/warehouses', function () use ($app) {
        $app->get('/list', 'warehouse.controller:getList');
        $app->post('/create', 'admin.controller:createWarehouse');
        $app->group('/{id}', function () use ($app) {
            $app->get('/info', 'warehouse.controller:getOne');
            $app->get('/transfers', 'admin.controller:getTransfersForWarehouse');
            $app->put('/change', 'admin.controller:changeWarehouse');
            $app->delete('/delete', 'admin.controller:deleteWarehouse');
        });
        
        $app->group('/items', function () use ($app) {
            $app->group('/list', function () use ($app) {
                $app->get('/pending', 'employee.controller:pendingList');
                $app->get('/available','employee.controller:availableList');
            });
            //для регуляр юзера
            $app->post('/take', 'employee.controller:takeTransfer');
            $app->group('/{id}', function () use ($app) {
                $app->delete('/sell', 'employee.controller:sellItem');//
                $app->post('/toTransfer', 'employee.controller:addToTransfer');
            });
            $app->group('/transfer', function () use ($app) {
                $app->get('/list', 'employee.controller:showTransfer');
                $app->get('/clear', 'employee.controller:clearTransfer');
                $app->post('/send', 'employee.controller:sendTransfer');
            });
        });
    });

    $app->group('/items', function () use ($app) {
        $app->post('/add', 'admin.controller:addItem');
        $app->group('/{id}', function () use ($app) {
            $app->put('/change', 'admin.controller:changeItem');//основыне параметры итема
            $app->get('/state', 'admin.controller:itemState');//
            $app->get('/transfers', 'admin.controller:getTransfersForItem');
        });
    });