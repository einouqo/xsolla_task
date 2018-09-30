<?php
    namespace App\Services;

    use App\Model\Employee;
    use App\Model\EmployeeAbstract;
    use App\Model\Item;
    use App\Repository\EmployeeRepository;
    use App\Repository\WarehouseRepository;
    use Firebase\JWT\JWT;

    class EmployeeService
    {
        /**
         * @var EmployeeRepository
         */
        private $employeeRepository;

        /**
         * @var WarehouseRepository
         */
        private $warehouseRepository;

        public function __construct(EmployeeRepository $employeeRepository, WarehouseRepository $warehouseRepository)
        {
            $this->employeeRepository = $employeeRepository;
            $this->warehouseRepository = $warehouseRepository;
        }

        /**
         * @param EmployeeAbstract $user
         * @return EmployeeAbstract
         * @throws \Exception
         */
        private function validateUser(EmployeeAbstract $user)
        {
            if ($user instanceof Employee) {
                return $user;
            } else {
                throw new \Exception('You have no access for this operation.', 403);
            }
        }

        /**
         * @param EmployeeAbstract $user
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getPendingList(EmployeeAbstract $user)
        {
            /** @var Employee $employee */
            $employee = $this->validateUser($user);

            $this->employeeRepository->fillPendingTransfers($employee);

            return $employee->getTransferList();
        }

        /**
         * @param EmployeeAbstract $user
         * @param null $warehouseID
         * @return array|null
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getAvailableList(EmployeeAbstract $user, $warehouseID = null)
        {
            /** @var Employee $employee */
            $employee = $this->validateUser($user);

            $this->warehouseRepository->fillWarehousesForEmployee($employee);
            if (isset($warehouseID) && !$employee->isWarehouseExist($warehouseID)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }
            return $employee->getItemList($warehouseID);
        }

        /**
         * @param EmployeeAbstract $user
         * @param $transferID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function takeTransfer(EmployeeAbstract $user, $transferID)
        {
            /** @var Employee $employee */
            $employee = $this->validateUser($user);

            $this->employeeRepository->fillPendingTransfers($employee);
            $transfer = $employee->getTransferByID($transferID);
            if (is_null($transfer)) {
                throw new \Exception('Pending transfer with this id wasn\'t found.', 403);
            }
            $this->warehouseRepository->fillWarehousesForEmployee($employee);
            $warehouse = $employee->getWarehouseByID($transfer->getWarehouseToID());
            if ($warehouse->getCapacity() - $warehouse->getLoaded() < $transfer->itemsQuantity()) {
                throw new \Exception('There is not enough space in the warehouse.');
            }

            $items = $transfer->getItems();
            foreach ($items as $item) {
                $this->employeeRepository->addItemToWarehouse($item, $warehouse->getAddress());
            }

            $this->employeeRepository->closeTransfer($transfer->getID());

            return 'Items was successfully added to your warehouse.';
        }

        /**
         * @param array $data
         */
        private function setCookieJWT(array $data)
        {
            setcookie('transfer', '', time() - 60 * 60, '/');
            $config = require __DIR__.'/../settings.php';
            setcookie('transfer',
                JWT::encode(
                ['Transfers' => $data] + ['exp' => (time() + 60 * 60 * 24)],
                    $config['jwt']['secret']
                ),
                time() + 60 * 60 * 24,
                '/'
            );
        }

        /**
         * @param int $itemID
         * @param array $data
         * @param int $totalQuantity
         * @return string
         */
        private function makeTransferCookie(int $itemID, array $data, int $totalQuantity)
        {
            $this->setCookieJWT(
                [[
                    'warehouseID' => $data['warehouseFromID'],
                    'items' => [[
                            'id' => $itemID,
                            'size' => $data['size'],
                            'quantity' => (int) ($data['quantity'] ?? $totalQuantity)
                        ]]
                ]]
            );

            return 'Transfer was created.';
        }

        /**
         * @return array|string
         */
        private function getTransfersFromCookie()
        {
            if (isset($_COOKIE['transfer'])) {
                $config = require __DIR__.'/../settings.php';
                $transfers = (array)((array)JWT::decode(
                    $_COOKIE['transfer'],
                    $config['jwt']['secret'],
                    array('HS256')
                ))['Transfers'];
                foreach ($transfers as $key => $transfer) {
                    $transfers[$key] = (array)$transfer;
                    foreach ($transfers[$key]['items'] as $itemKey => $item) {
                        $transfers[$key]['items'][$itemKey] = (array)$item;
                    }
                }
                return $transfers;
            } else {
                return null;
            }
        }

        /**
         * @param array $item
         * @param array $data
         * @param int $totalQuantity
         * @throws \Exception
         */
        private function addItemQuantityToPreCookie(array &$item, array $data, int $totalQuantity)
        {
            if (is_null($item['quantity'])) {
                throw new \Exception('All this items already added in transfer.', 403);
            }
            if ($item['quantity'] + $data['quantity'] >= $totalQuantity) {
                throw new \Exception('There is not enough items. Total quantity: '.$totalQuantity.
                    ' units. Already reserved: '.$item['quantity'].' units. Available: '.($totalQuantity - $item['quantity']).' units.', 403);
            }
            $item['quantity'] += $data['quantity'] ?? ($totalQuantity - $item['quantity']);
        }

        /**
         * @param array $transfer
         * @param int $itemID
         * @param array $data
         * @param int $totalQuantity
         * @return bool
         * @throws \Exception
         */
        private function findInItems(array &$transfer, int $itemID, array $data, int $totalQuantity)
        {
            foreach ($transfer['items'] as $key => $item) {
                if ($item['id'] == $itemID && $item['size'] == $data['size']) {
                    $this->addItemQuantityToPreCookie($transfer['items'][$key], $data, $totalQuantity);
                    return true;
                }
            }
            return false;
        }

        /**
         * @param int $itemID
         * @param array $data
         * @param int $totalQuantity
         * @return string
         * @throws \Exception
         */
        private function addToTransferCookie(int $itemID, array $data, int $totalQuantity)
        {
            $added = false;
            $transfers = $this->getTransfersFromCookie();
            foreach ($transfers as $key => $transfer) {
                if ($transfer['warehouseID'] == $data['warehouseFromID']) {
                    if (!$this->findInItems($transfers[$key], $itemID, $data, $totalQuantity)) {
                        array_push($transfers[$key]['items'],
                            [
                                'id' => $itemID,
                                'size' => $data['size'],
                                'quantity' => (int)$data['quantity']
                            ]
                        );
                    };
                    $added = true;
                    break;
                }
            }
            if (!$added) {
                array_push($transfers,
                    [
                    'warehouseID' => $data['warehouseFromID'],
                    'items' =>
                        [[
                            'id' => $itemID,
                            'size' => $data['size'],
                            'quantity' => (int) ($data['quantity'] ?? $totalQuantity)
                        ]]
                    ]
                );
            }
            $this->setCookieJWT($transfers);
            return 'Item was successfully added to transfer.';
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $itemID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addToTransfer(EmployeeAbstract $user, int $itemID, array $data)
        {
            /** @var Employee $employee */
            $employee = $this->validateUser($user);

            if (is_null($data['size'])){
                throw new \Exception('You need to set size.', 400);
            }

            $this->warehouseRepository->fillWarehousesForEmployee($employee);
            $warehouse = $employee->getWarehouseByID($data['warehouseFromID']);
            if (is_null($warehouse)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }

            $items = $warehouse->getItemByID($itemID, $data['size']);
            if (is_null($items)) {
                throw new \Exception('Item not found in this warehouse.', 403);
            }

            $totalQuantity = 0;
            /** @var Item $item */
            foreach ($items as $item) {
                $totalQuantity += $item->getQuantity();
            }

            if (isset($data['quantity']) && ($totalQuantity - $data['quantity'] < 0)) {
                throw new \Exception('There is not enough items.', 403);
            }

            return key_exists('transfer', $_COOKIE) ?
                $this->addToTransferCookie($itemID, $data, $totalQuantity) :
                $this->makeTransferCookie($itemID, $data, $totalQuantity);
        }

        /**
         * @param EmployeeAbstract $user
         * @return string
         * @throws \Exception
         */
        public function clearTransfer(EmployeeAbstract $user)
        {
            $this->validateUser($user);
            setcookie('transfer', '', time() - 60 * 60, '/');
            return 'Transfer list was successfully cleared.';
        }

        /**
         * @param EmployeeAbstract $user
         * @return array|string
         * @throws \Exception
         */
        public function getTransferList(EmployeeAbstract $user)
        {
            $this->validateUser($user);
            return $this->getTransfersFromCookie();
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $warehouseToID
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function sendTransfer(EmployeeAbstract $user, int $warehouseToID)
        {
            $this->validateUser($user);
            $isSent = false;
            $transfers = $this->getTransfersFromCookie();
            if (gettype($transfers) == 'array') {
                foreach ($transfers as $transfer) {
                    if ($transfer['warehouseID'] != $warehouseToID) {
                        $this->employeeRepository->registerTransfer($transfer, $warehouseToID);
                        $isSent = true;
                    }
                }
            }
            setcookie('transfer', '', time() - 60 * 60, '/');
            return $isSent ?
                'Transfer was successfully sent.':
                'There is nothing to send.';
        }

        /**
         * @param array $data
         * @throws \Exception
         */
        private function sellValidate(array $data)
        {
            foreach ($data as $field => $value) {
                if (is_null($value)) {
                    throw new \Exception('Field '.$field.' cannot be empty.', 403);
                }
            }
        }

        /**
         * @param int $itemID
         * @param string $size
         * @param int $warehouseID
         * @return int
         */
        private function quantityFromTransfer(int $itemID, string $size, int $warehouseID)
        {
            if (!key_exists('transfer', $_COOKIE)) {
                return 0;
            }
            $transfers = $this->getTransfersFromCookie();
            foreach ($transfers as $key => $transfer) {
                if ($transfer['warehouseID'] == $warehouseID) {
                    foreach ($transfer['items'] as $item) {
                        if ($item['id'] == $itemID && $item['size'] == $size) {
                            return $item['quantity'];
                        }
                    }
                }
            }
            return 0;
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $itemID
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function sellItem(EmployeeAbstract $user, int $itemID, array $data)
        {
            /** @var Employee $employee */
            $employee = $this->validateUser($user);
            $this->sellValidate($data);
            $this->warehouseRepository->fillWarehousesForEmployee($employee);
            $warehouse = $employee->getWarehouseByID($data['warehouseID']);
            if (is_null($warehouse)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }

            $items = $warehouse->getItemByID($itemID, $data['size']);
            if (is_null($items)) {
                throw new \Exception('Item not found in this warehouse.', 403);
            }
            $item = array_shift($items);

            $reserved = $this->quantityFromTransfer($itemID, $data['size'], $data['warehouseID']);
            $available = $item->getQuantity();
            if ($available < $data['quantity'] + $reserved) {
                throw new \Exception('There is not enough items. Available: '.$available.' units. Reserved '.$reserved.' units.', 403);
            }

            $this->employeeRepository->sellItem($warehouse->getID(), $itemID, $data);
            return 'Item was sold successfully.';
        }
    }