<?php
    namespace App\Services;

    use App\Model\EmployeeAdmin;
    use App\Repository\AdminRepository;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;
    use http\Exception\RuntimeException;

    class AdminService
    {
        /**
         * @var AdminRepository
         */
        private $adminRepository;

        /**
         * @var UserRepository
         */
        private $userRepository;

        public function __construct(AdminRepository $adminRepository, UserRepository $userRepository)//Два репозитория - это ок?
        {
            $this->adminRepository = $adminRepository;
            $this->userRepository = $userRepository;
        }

        private function getUserIDFromCookie()//из user service, как переиспользовать?
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
            if (is_a($user, 'App\Model\EmployeeAdmin')) {
                return $user;
            } else {
                throw new \Exception('You have no access for this operation.', 403);
            }
        }

        private function fillWarehouses(EmployeeAdmin &$admin, bool $setLoaded = false)
        {
            $warehouses = $this->adminRepository->getWarehouses($admin->getCompanyID(), $setLoaded);
            foreach ($warehouses as $warehouse) {
                $admin->addWarehouse($warehouse);
            }
        }

        private function fillEmployees(EmployeeAdmin &$admin)
        {
            $employees = $this->adminRepository->getEmployees($admin->getCompanyID());
            foreach ($employees as $employee) {
                $admin->addEmployee($employee);
            }
        }

        private function fillAccesses(EmployeeAdmin &$admin)
        {
            $accesses = $this->adminRepository->getAccesses($admin->getCompanyID());
            foreach ($accesses as $access) {
                $admin->addAccess($access['userID'], $access['warehouseID']);
            }
        }

        private function fillRooms(EmployeeAdmin &$admin)
        {
            $rooms = $this->adminRepository->getRooms($admin->getCompanyID());
            foreach ($rooms as $room) {
                $admin->addRoom($room);
            }
        }

        private function dataAccessValidation(EmployeeAdmin $admin, array $data)
        {
            if (!isset($data['userID'], $data['warehouseID'])) {
                throw new \Exception('Not all fields are filled.', 403);
            }
            if (!$admin->isEmployeeExist($data['userID'])) {
                throw new \Exception('User with this ID wasn\'t found in your organisation.', 400);
            };
            if (!$admin->isWarehouseExist($data['warehouseID'])) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }
        }

        public function giveAccess(array $data)
        {
            $admin = $this->getUser();

            $this->fillWarehouses($admin);
            $this->fillEmployees($admin);
            $this->fillAccesses($admin);

            $this->dataAccessValidation($admin, $data);
            if ($admin->isAccessExist($data['userID'], $data['warehouseID'])) {
                throw new \Exception('This access is already exist.', 403);
            };
            $this->adminRepository->giveAccess($data, $admin->getCompanyID());

            return 'Access was added successfully.';
        }

        public function deleteAccess(array $data)
        {
            $admin = $this->getUser();

            $this->fillWarehouses($admin);
            $this->fillEmployees($admin);
            $this->fillAccesses($admin);

            $this->dataAccessValidation($admin, $data);
            if (!$admin->isAccessExist($data['userID'], $data['warehouseID'])) {
                throw new \Exception('This access isn\'t exist.', 403);
            }
            $this->adminRepository->deleteAccess($data, $admin->getCompanyID());

            return 'Access was deleted successfully.';
        }

        private function createWarehouseValidation(EmployeeAdmin $admin, array $data)
        {
            if (!isset($data['roomID'], $data['name'], $data['capacity'])) {
                throw new \Exception('Not all fields are filled.', 403);
            }
            if (!$admin->isRoomExist($data['roomID'])) {
                throw new \Exception('Room with this ID wasn\'t found in your organisation.', 400);
            };
            if ($admin->isWarehouseExist($data['roomID'])) {
                throw new \Exception('Warehouse was created before.', 403);
            }
            if (!is_numeric($data['capacity']) && $data['capacity'] < 1) {
                throw new \Exception('Capacity value is wrong.', 403);
            }
        }

        public function createWarehouse(array $data)
        {
            $admin = $this->getUser();

            $this->fillRooms($admin);
            $this->fillWarehouses($admin);

            $this->createWarehouseValidation($admin, $data);
            $this->adminRepository->createWarehouse($data);

            return 'Warehouse was successfully created.';
        }

        private function changeWarehouseValidation(EmployeeAdmin $admin, array $data)
        {
            if (!isset($data['warehouseID'])) {
                throw new \Exception('Warehouse ID is empty.', 403);
            }
            if (!$admin->isWarehouseExist($data['warehouseID'])) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }

            $warehouse = $admin->getWarehouseByID($data['warehouseID']);
            if (!isset($data['name']) && !isset($data['capacity']) ||
                $warehouse->getName() == $data['name'] && $warehouse->getCapacity() == $data['capacity']) {
                throw new \Exception('There nothing to change.', 400);
            }

            $oldWarehouseInfo = $warehouse->fullInfoToArray();
            foreach ($data as $field => $data) {
                if ($oldWarehouseInfo[$field] == $data) {
                    throw new \Exception('The '.$field.' value can not be the same as the old one, please try again.', 400);
                }
            }
        }

        public function changeWarehouse(array $data)
        {
            $admin = $this->getUser();

            $this->fillWarehouses($admin);

            $this->changeWarehouseValidation($admin, $data);
            $this->adminRepository->changeWarehouse($admin->getWarehouseByID($data['warehouseID']), $data);

            return 'Warehouse info was successfully updated.';
        }

        public function deleteWarehouse(int $warehouseID)
        {
            $admin = $this->getUser();

            $this->fillWarehouses($admin);

            if (!$admin->isWarehouseExist($warehouseID)) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }
            $this->adminRepository->deleteWarehouse($warehouseID, $admin->getWarehouseByID($warehouseID)->getAddress());

            return 'Warehouse was successfully deleted.';
        }

        private function addRoomValidation(EmployeeAdmin $admin, array $data)
        {
            if (!isset($data['address'])) {
                throw new \Exception('Not all fields are filled.', 403);
            }
            if ($admin->isRoomExistByAddress($data['address'])) {
                throw new \Exception('Room was added before.', 403);
            }
        }

        public function addRoom(array $data)
        {
            $admin = $this->getUser();

            $this->fillRooms($admin);

            $this->addRoomValidation($admin, $data);
            $this->adminRepository->addRoom($data, $admin->getCompanyID());

            return 'Room was successfully created.';
        }

        private function deleteRoomValidation(EmployeeAdmin $admin, $roomID)
        {
            if (!isset($roomID)) {
                throw new \Exception('Not all fields are filled.', 403);
            }
            if (!is_numeric($roomID)) {
                throw new \Exception('Room ID are wrong.', 403);
            }
            if (!$admin->isRoomExist($roomID)) {
                throw new \Exception('This room wasn\'t found in your company.', 403);
            }
        }

        public function deleteRoom($roomID)
        {
            $admin = $this->getUser();

            $this->fillRooms($admin);

            $this->deleteRoomValidation($admin, $roomID);
            $this->adminRepository->deleteRoom($roomID);

            return 'Room was successfully deleted.';
        }

        public function getRooms()
        {
            $admin = $this->getUser();

            $this->fillRooms($admin);

            return $admin->getRoomsList();
        }

        public function getTransfersForWarehouse(int $warehouseID)
        {
            $admin = $this->getUser();
            $this->fillWarehouses($admin);
            if (is_null($admin->getWarehouseByID($warehouseID))) {
                throw new \Exception('This warehouse wasn\'t found in your company.', 403);
            }

            $this->adminRepository->fillTransfers($admin);
            return $admin->getWarehouseTransfers($warehouseID);
        }

        public function getTransfersForItem(int $itemID)
        {
            $admin = $this->getUser();
            $this->adminRepository->fillTransfers($admin);
            return $admin->getItemTransfers($itemID);
        }

        private function newItemValidation(array $data)
        {
            foreach ($data as $field => $value) {
                if (is_null($value)) {
                    throw new \Exception('Field '.$field.' cannot be empty.', 403);
                }
            }
        }

        public function addItem(array $data, $warehouseID)
        {
            $admin = $this->getUser();

            if (is_null($warehouseID)) {
                throw new \Exception('Receiving warehouse ID cannot be empty.', 403);
            }

            $this->fillWarehouses($admin, true);
            $warehouseTo = $admin->getWarehouseByID($warehouseID);
            if (is_null($warehouseTo)) {
                throw new \Exception('This warehouse wasn\'t found in your company.', 403);
            }

            $this->newItemValidation($data);

            $available = $warehouseTo->getCapacity() - $warehouseTo->getLoaded();
            if ($available < $data['quantity']) {
                throw new \Exception('There is not enough storage space. Available: '.$available.'.', 403);
            }

            $this->adminRepository->addItem($data, $warehouseTo->getAddress());

            return 'Item was added successfully.';
        }
    }