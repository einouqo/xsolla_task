<?php
    namespace App\Services;

    use App\Model\EmployeeAdmin;
    use App\Repository\AdminRepository;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;

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

        /**
         * @return mixed
         * @throws \Exception
         */
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

        /**
         * @return \App\Model\Employee|EmployeeAdmin
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param EmployeeAdmin $admin
         * @param array $data
         * @throws \Exception
         */
        private function dataAccessValidation(EmployeeAdmin $admin, array $data)
        {
            if (is_null($data['userID']) || $data['userID'] == '') {
                throw new \Exception('User ID cannot be empty.', 403);
            }
            if(!is_numeric($data['userID'])) {
                throw new \Exception('User ID may consist digits only', 403);
            }
            if (is_null($data['warehouseID']) || $data['warehouseID'] == '') {
                throw new \Exception('Warehouse ID cannot be empty.', 403);
            }
            if(!is_numeric($data['warehouseID'])) {
                throw new \Exception('Warehouse ID may consist digits only', 403);
            }
            if (!$admin->isEmployeeExist($data['userID'])) {
                throw new \Exception('Employee with this ID wasn\'t found in your organisation.', 400);
            };
            if (!$admin->isWarehouseExist($data['warehouseID'])) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function giveAccess(array $data)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillWarehouses($admin);
            $this->adminRepository->fillEmployees($admin);
            $this->adminRepository->fillAccesses($admin);

            $this->dataAccessValidation($admin, $data);
            if ($admin->isAccessExist($data['userID'], $data['warehouseID'])) {
                throw new \Exception('This access is already exist.', 403);
            };
            $this->adminRepository->giveAccess($data, $admin->getCompanyID());

            return 'Access was added successfully.';
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteAccess(array $data)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillWarehouses($admin);
            $this->adminRepository->fillEmployees($admin);
            $this->adminRepository->fillAccesses($admin);

            $this->dataAccessValidation($admin, $data);
            if (!$admin->isAccessExist($data['userID'], $data['warehouseID'])) {
                throw new \Exception('This access isn\'t exist.', 403);
            }
            $this->adminRepository->deleteAccess($data, $admin->getCompanyID());

            return 'Access was deleted successfully.';
        }

        /**
         * @param EmployeeAdmin $admin
         * @param array $data
         * @throws \Exception
         */
        private function createWarehouseValidation(EmployeeAdmin $admin, array $data)
        {
            if (is_null($data['roomID']) || $data['roomID'] == '') {
                throw new \Exception('Room ID cannot be empty.', 403);
            }
            if (!is_numeric($data['roomID'])) {
                throw new \Exception('Room ID may consist digits only', 403);
            }
            if (is_null($data['name']) || $data['name'] == '') {
                throw new \Exception('Name cannot be empty.', 403);
            }
            if (is_null($data['capacity']) || $data['capacity'] == '') {
                throw new \Exception('Capacity cannot be empty.', 403);
            }
            if (!is_numeric($data['capacity'])) {
                throw new \Exception('Capacity value may consist digits only', 403);
            }
            if ($data['capacity'] < 1) {
                throw new \Exception('Capacity value can be positive only.', 403);
            }
            if (!$admin->isRoomExist($data['roomID'])) {
                throw new \Exception('Room with this ID wasn\'t found in your organisation.', 400);
            };
            if ($admin->isWarehouseExist($data['roomID'])) {
                throw new \Exception('Warehouse was created before.', 403);
            }
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function createWarehouse(array $data)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillRooms($admin);
            $this->adminRepository->fillWarehouses($admin);

            $this->createWarehouseValidation($admin, $data);
            $this->adminRepository->createWarehouse($data);

            return 'Warehouse was successfully created.';
        }

        /**
         * @param EmployeeAdmin $admin
         * @param int $warehouseID
         * @param array $data
         * @throws \Exception
         */
        private function changeWarehouseValidation(EmployeeAdmin $admin, int $warehouseID, array $data)
        {
            if (!$admin->isWarehouseExist($warehouseID)) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }

            if (isset($data['capacity']) && !is_numeric($data['capacity'])) {
                throw new \Exception('Capacity value may consist digits only', 403);
            }

            $warehouse = $admin->getWarehouseByID($warehouseID);
            if ((is_null($data['name']) || $data['name'] == '') && (is_null($data['capacity']) || $data['capacity'] == '') ||
                $warehouse->getName() == $data['name'] && $warehouse->getCapacity() == $data['capacity']) {
                throw new \Exception('There is nothing to change.', 400);
            }

            $oldWarehouseInfo = $warehouse->getFullInfo();
            foreach ($data as $field => $value) {
                if ($value == '') {
                    $data[$field] = $oldWarehouseInfo[$field];
                }
                if ($oldWarehouseInfo[$field] == $value) {
                    throw new \Exception('The '.$field.' value can not be the same as the old one, please try again.', 400);
                }
            }
        }

        /**
         * @param int $warehouseID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeWarehouse(int $warehouseID, array $data)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillWarehouses($admin);

            $this->changeWarehouseValidation($admin, $warehouseID, $data);
            $this->adminRepository->changeWarehouse($admin->getWarehouseByID($warehouseID), $data);

            return 'Warehouse info was successfully updated.';
        }

        /**
         * @param int $warehouseID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteWarehouse(int $warehouseID)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillWarehouses($admin);

            if (!$admin->isWarehouseExist($warehouseID)) {
                throw new \Exception('Warehouse with this ID wasn\'t found in your organisation.', 400);
            }
            $this->adminRepository->deleteWarehouse($warehouseID, $admin->getWarehouseByID($warehouseID)->getAddress());

            return 'Warehouse was successfully deleted.';
        }

        /**
         * @param EmployeeAdmin $admin
         * @param array $data
         * @throws \Exception
         */
        private function addRoomValidation(EmployeeAdmin $admin, array $data)
        {
            if (is_null($data['address']) || $data['address'] == '') {
                throw new \Exception('Address cannot be empty.', 403);
            }
            if ($admin->isRoomExistByAddress($data['address'])) {
                throw new \Exception('Room was added before.', 403);
            }
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addRoom(array $data)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillRooms($admin);

            $this->addRoomValidation($admin, $data);
            $this->adminRepository->addRoom($data, $admin->getCompanyID());

            return 'Room was successfully created.';
        }

        /**
         * @param EmployeeAdmin $admin
         * @param $roomID
         * @throws \Exception
         */
        private function deleteRoomValidation(EmployeeAdmin $admin, $roomID)
        {
            if (!is_numeric($roomID)) {
                throw new \Exception('Room ID are wrong.', 403);
            }
            if (!$admin->isRoomExist($roomID)) {
                throw new \Exception('This room wasn\'t found in your company.', 403);
            }
        }

        /**
         * @param $roomID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteRoom($roomID)
        {
            $admin = $this->getUser();

            $this->adminRepository->fillRooms($admin);

            $this->deleteRoomValidation($admin, $roomID);
            $this->adminRepository->deleteRoom($roomID);

            return 'Room was successfully deleted.';
        }

        /**
         * @return array|string
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getRooms()
        {
            $admin = $this->getUser();

            $this->adminRepository->fillRooms($admin);

            return $admin->getRoomsList();
        }

        /**
         * @param int $warehouseID
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForWarehouse(int $warehouseID)
        {
            $admin = $this->getUser();
            $this->adminRepository->fillWarehouses($admin);
            if (is_null($admin->getWarehouseByID($warehouseID))) {
                throw new \Exception('This warehouse wasn\'t found in your company.', 403);
            }

            $this->adminRepository->fillTransfers($admin);
            return $admin->getWarehouseTransfers($warehouseID);
        }

        /**
         * @param int $itemID
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForItem(int $itemID)
        {
            $admin = $this->getUser();
            $this->adminRepository->fillTransfers($admin);
            return $admin->getItemTransfers($itemID);
        }

        /**
         * @param array $data
         * @throws \Exception
         */
        private function newItemValidation(array $data)
        {
            foreach ($data as $field => $value) {
                if (is_null($value) || $value == '') {
                    throw new \Exception('Field '.$field.' cannot be empty.', 403);
                }
            }
            if (!is_numeric($data['price'])) {
                throw new \Exception('Price should be numeric.', 403);
            }
        }

        /**
         * @param array $data
         * @param $warehouseID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addItem(array $data, $warehouseID)
        {
            $admin = $this->getUser();

            if (is_null($warehouseID) || $warehouseID == '') {
                throw new \Exception('Receiving warehouse ID cannot be empty.', 403);
            }

            if (!is_numeric($warehouseID)) {
                throw new \Exception('Warehouse ID value may consist digits only.', 403);
            }

            $this->adminRepository->fillWarehouses($admin, true);
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

        /**
         * @param $itemID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeItem($itemID, array $data)
        {
            $this->getUser();

            if ((is_null($data['price']) || $data['price'] == '') &&
                (is_null($data['name']) || $data['name'] == '') &&
                (is_null($data['type']) || $data['type'] == '')) {
                throw new \Exception('Nothing to change.', 400);
            }

            $this->adminRepository->changeItem($itemID, $data);

            return 'Item was successfully changed.';
        }

        /**
         * @param int $id
         * @param \DateTime|null $onDate
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        public function itemState(int $id, \DateTime $onDate = null)
        {
            $admin = $this->getUser();
            return $this->adminRepository->itemState($id, $admin->getCompanyID(), $onDate);
        }
    }