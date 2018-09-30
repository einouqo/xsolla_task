<?php
    namespace App\Services;

    use App\Model\EmployeeAbstract;
    use App\Model\EmployeeAdmin;
    use App\Repository\AdminRepository;
    use App\Repository\WarehouseRepository;

    class AdminService
    {
        /**
         * @var AdminRepository
         */
        private $adminRepository;

        /**
         * @var WarehouseRepository
         */
        private $warehouseRepository;

        public function __construct(AdminRepository $adminRepository, WarehouseRepository $warehouseRepository)
        {
            $this->adminRepository = $adminRepository;
            $this->warehouseRepository = $warehouseRepository;
        }

        /**
         * @param EmployeeAbstract $user
         * @return \App\Model\Employee|EmployeeAdmin
         * @throws \Exception
         */
        private function validateUser(EmployeeAbstract $user)
        {
            if ($user instanceof EmployeeAdmin) {
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
         * @param EmployeeAbstract $user
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function giveAccess(EmployeeAbstract $user, array $data)
        {
            $admin = $this->validateUser($user);

            $this->warehouseRepository->fillWarehousesForAdmin($admin);
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
         * @param EmployeeAbstract $user
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteAccess(EmployeeAbstract $user, array $data)
        {
            $admin = $this->validateUser($user);

            $this->warehouseRepository->fillWarehousesForAdmin($admin);
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
         * @param EmployeeAbstract $user
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function createWarehouse(EmployeeAbstract $user, array $data)
        {
            $admin = $this->validateUser($user);

            $this->adminRepository->fillRooms($admin);
            $this->warehouseRepository->fillWarehousesForAdmin($admin);

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
         * @param EmployeeAbstract $user
         * @param int $warehouseID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeWarehouse(EmployeeAbstract $user, int $warehouseID, array $data)
        {
            $admin = $this->validateUser($user);

            $this->warehouseRepository->fillWarehousesForAdmin($admin);

            $this->changeWarehouseValidation($admin, $warehouseID, $data);
            $this->adminRepository->changeWarehouse($admin->getWarehouseByID($warehouseID), $data);

            return 'Warehouse info was successfully updated.';
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $warehouseID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteWarehouse(EmployeeAbstract $user, int $warehouseID)
        {
            $admin = $this->validateUser($user);

            $this->warehouseRepository->fillWarehousesForAdmin($admin);

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
         * @param EmployeeAbstract $user
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addRoom(EmployeeAbstract $user, array $data)
        {
            $admin = $this->validateUser($user);

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
         * @param EmployeeAbstract $user
         * @param $roomID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteRoom(EmployeeAbstract $user, $roomID)
        {
            $admin = $this->validateUser($user);

            $this->adminRepository->fillRooms($admin);

            $this->deleteRoomValidation($admin, $roomID);
            $this->adminRepository->deleteRoom($roomID);

            return 'Room was successfully deleted.';
        }

        /**
         * @param EmployeeAbstract $user
         * @return array|string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getRooms(EmployeeAbstract $user)
        {
            $admin = $this->validateUser($user);

            $this->adminRepository->fillRooms($admin);

            return $admin->getRoomsList();
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $warehouseID
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForWarehouse(EmployeeAbstract $user, int $warehouseID)
        {
            $admin = $this->validateUser($user);
            $this->warehouseRepository->fillWarehousesForAdmin($admin);
            if (is_null($admin->getWarehouseByID($warehouseID))) {
                throw new \Exception('This warehouse wasn\'t found in your company.', 403);
            }

            $this->adminRepository->fillTransfers($admin);
            return $admin->getWarehouseTransfers($warehouseID);
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $itemID
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForItem(EmployeeAbstract $user, int $itemID)
        {
            $admin = $this->validateUser($user);
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
         * @param EmployeeAbstract $user
         * @param array $data
         * @param $warehouseID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addItem(EmployeeAbstract $user, array $data, $warehouseID)
        {
            $admin = $this->validateUser($user);

            if (is_null($warehouseID) || $warehouseID == '') {
                throw new \Exception('Receiving warehouse ID cannot be empty.', 403);
            }

            if (!is_numeric($warehouseID)) {
                throw new \Exception('Warehouse ID value may consist digits only.', 403);
            }

            $this->warehouseRepository->fillWarehousesForAdmin($admin, true);
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
         * @param EmployeeAbstract $user
         * @param $itemID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeItem(EmployeeAbstract $user, $itemID, array $data)
        {
            $this->validateUser($user);

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
         * @param array $itemData
         * @param \DateTime $onDate
         * @return int|mixed
         * @throws \Doctrine\DBAL\DBALException
         */
        private function countQuantityOnDate(int $id, array $itemData, \DateTime $onDate)
        {
            return $itemData['quantity'] -
                ($this->warehouseRepository->getDeliveryCondition($id, $itemData['address'], $itemData['size'], $onDate) ?? 0) +
                ($this->warehouseRepository->getSellingCondition($id, $itemData['id'], $itemData['size'], $onDate) ?? 0) +
                ($this->warehouseRepository->getSendedCondition($id, $itemData['id'], $itemData['size'], $onDate) ?? 0) -
                ($this->warehouseRepository->getReceivingCondition($id, $itemData['id'], $itemData['size'], $onDate) ?? 0);
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $id
         * @param \DateTime|null $onDate
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function itemState(EmployeeAbstract $user, int $id, \DateTime $onDate = null)
        {
            $admin = $this->validateUser($user);

            $itemsData = $this->adminRepository->getItems($id, $admin->getCompanyID());
            $result = [
                'itemID' => $id,
                'name' => reset($itemsData)['name'],
                'price' => (float)reset($itemsData)['price'],
                'warehouses' => []
            ];
            $totalQuantity = 0;
            $totalPrice = 0.;
            foreach ($itemsData as $itemData) {
                $quantity = is_null($onDate) ?
                    $itemData['quantity']:
                    $this->countQuantityOnDate($id, $itemData, $onDate);
                $totalQuantity += $quantity;
                $totalPrice += $quantity * $itemData['price'];
                if ($quantity != 0) {
                    array_push(
                        $result['warehouses'],
                        [
                            'id' => $itemData['id'],
                            'address' => $itemData['address'],
                            'size' => $itemData['size'],
                            'quantity' => $quantity
                        ]
                    );
                }
            }
            $result += [
                'Total quantity' => $totalQuantity,
                'Total price' => $totalPrice
            ];

            return $result;
        }
    }