<?php
    namespace App\Services;

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
                $config = require __DIR__ . '/../settings.php';
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
                $this->adminRepository->fillWarehousesWithItems($user):
                $this->employeeRepository->fillWarehouses($user);

            return $user;
        }

        public function getList()
        {
            $user = $this->getUser();
            return $user->getWarehousesList();
        }

        private function getReport(array $report, \DateTime $date = null)
        {
            if (is_null($date)) {
                return $report;
            }

            $totalQuantity = 0;
            $totalPrice = 0.;
            foreach ($report['items'] as $key => $item) {
                print_r($report['items']);
                $report['items'][$key]['quantity'] +=
                    ($this->adminRepository->getSellingCondition($item['id'], $report['id'], $item['size'], $date) ?? 0) -
                    ($this->adminRepository->getDeliveryCondition($item['id'], $report['address'], $item['size'], $date) ?? 0) +
                    ($this->adminRepository->getSendedCondition($item['id'], $report['id'], $item['size'], $date) ?? 0) -
                    ($this->adminRepository->getReceivingCondition($item['id'], $report['id'], $item['size'], $date) ?? 0);
                $totalQuantity += $report['items'][$key]['quantity'];
                $totalPrice += $report['items'][$key]['quantity'] * $report['items'][$key]['price'];
            }
            $report['Total price: '] = $totalPrice;
            $report['Total quantity: '] = $totalQuantity;

            return $report;
        }

        public function getOne(int $warehouseID, \DateTime $date = null)
        {
            $user = $this->getUser();

            $warehouse = $user->getWarehouseByID($warehouseID);
            if (is_null($warehouse)) {
                throw new \Exception('This warehouse wasn\'t found in your organisation.', 400);
            }

            return $this->getReport($warehouse->getFullInfo(), $date);
        }
    }