<?php
    use Doctrine\DBAL\DriverManager;
    use Psr\Container\ContainerInterface;

    $container = $app->getContainer();

    $container['db'] = function () {
            return DriverManager::getConnection([
                'driver' => 'pdo_mysql',
                'host' => '192.168.100.123',
                'dbname' => 'PHP04',
                'user' => 'root',
                'password' => 'root',
                'charset' => 'utf8'
            ]);
        };


    $container['user.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\UserRepository($c->get('db'));
    };

    $container['user.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\UserService($c->get('user.repository'));
    };

    $container['user.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\UserController($c->get('user.service'));
    };

    $container['registration.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\RegistrationController($c->get('user.service'));
    };