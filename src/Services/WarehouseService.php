<?php
    namespace App\Services;

    use App\Model\Employee;
    use App\Model\EmployeeAdmin;
    use App\Repository\AdminRepository;
    use App\Repository\EmployeeRepository;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;

    class WarehouseService
    {
        /**
         * @var AdminRepository
         */
        private $adminRepository;

        /**
         * @var EmployeeRepository
         */
        private $employeeRepository;

        /**
         * @var UserRepository
         */
        private $userRepository;

        public function __construct(AdminRepository $adminRepository, EmployeeRepository $employeeRepository, UserRepository $userRepository)
        {
            $this->adminRepository = $adminRepository;
            $this->employeeRepository = $employeeRepository;
            $this->userRepository = $userRepository;
        }

        private function getUserIDFromCookie()
        {
            if (isset($_COOKIE['token'])) {
                $config = require __DIR__.'/../settings.php';
                return ((array)JWT::decode(
                    $_COOKIE['token'],
                    $config['jwt']['secret'],
                    array('HS256')
                ))['userID'];
            } else {
                throw new \Exception('You need to login.', 401);
            }
        }

        private function getUser()
        {
            $user = $this->userRepository->getUserInfoByID(
                $this->getUserIDFromCookie()
            );

            is_a($user, 'App\Model\EmployeeAdmin') ?
                $this->fillWarehousesForAdmin($user):
                $this->fillWarehousesForEmployee($user);

            return $user;
        }

        private function fillWarehousesForAdmin(EmployeeAdmin &$admin)
        {
            $warehouses = $this->adminRepository->getWarehouses($admin->getCompanyID());
            foreach ($warehouses as $warehouse) {
                $admin->addWarehouse($warehouse);
            }
        }

        private function fillWarehousesForEmployee(Employee &$employee)
        {
            $warehouses = $this->employeeRepository->getWarehouses($employee->getID());
            foreach ($warehouses as $warehouse) {
                $employee->addWarehouse($warehouse);
            }
        }

        public function getList()
        {
            $user = $this->getUser();
            return $user->getWarehousesList();
        }

        public function getOne(int $warehouseID)
        {
            $user = $this->getUser();
            return $user->getWarehouseByID($warehouseID);
        }
    }