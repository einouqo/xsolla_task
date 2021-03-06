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

    $container['middleware'] = function ($c) {
        $secret = (require __DIR__.'/settings.php')['jwt']['secret'];
        /** @var ContainerInterface $c */
        return new \App\Middleware($c->get('user.repository'), $secret);
    };

    $container['company.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\CompanyRepository($c->get('db'));
    };

    $container['user.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\UserRepository($c->get('db'));
    };

    $container['warehouse.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\WarehouseRepository($c->get('db'));
    };

    $container['admin.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\AdminRepository($c->get('db'));
    };

    $container['employee.repository'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Repository\EmployeeRepository($c->get('db'));
    };

    $container['company.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\CompanyService($c->get('company.repository'));
    };

    $container['user.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\UserService($c->get('user.repository'));
    };

    $container['warehouse.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\WarehouseService($c->get('warehouse.repository'));
    };

    $container['admin.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\AdminService($c->get('admin.repository'), $c->get('warehouse.repository'));
    };

    $container['employee.service'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Services\EmployeeService($c->get('employee.repository'), $c->get('warehouse.repository'));
    };

    $container['company.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\CompanyController($c->get('company.service'));
    };

    $container['registration.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\RegistrationController($c->get('user.service'));
    };

    $container['user.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\UserController($c->get('user.service'));
    };

    $container['warehouse.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\WarehouseController($c->get('warehouse.service'));
    };

    $container['admin.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\AdminController($c->get('admin.service'));
    };

    $container['employee.controller'] = function ($c) {
        /** @var ContainerInterface $c */
        return new \App\Controller\EmployeeController($c->get('employee.service'));
    };