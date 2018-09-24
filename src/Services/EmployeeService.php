<?php
    namespace App\Services;

    use App\Model\Employee;
    use App\Model\Item;
    use App\Model\Warehouse;
    use App\Repository\EmployeeRepository;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;

    class EmployeeService
    {
        /**
         * @var EmployeeRepository
         */
        private $employeeRepository;

        /**
         * @var UserRepository
         */
        private $userRepository;

        public function __construct(EmployeeRepository $employeeRepository, UserRepository $userRepository)
        {
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
            if (is_a($user, 'App\Model\Employee')) {
                return $user;
            } else {
                throw new \Exception('You have no access for this operation.', 403);
            }
        }

        private function fillPendingTransfers(Employee &$employee)
        {
            $transfers = $this->employeeRepository->getPendingTransfers($employee->getID());
            foreach ($transfers as $transfer) {
                $employee->addTransfer($transfer);
            }
        }

        public function getPendingList()
        {
            $employee = $this->getUser();

            $this->fillPendingTransfers($employee);

            return $employee->getTransferList();
        }

        private function fillWarehouses(Employee &$employee)
        {
            $warehouses = $this->employeeRepository->getWarehouses($employee->getID());
            foreach ($warehouses as $warehouse) {
                $employee->addWarehouse($warehouse);
            }
        }

        public function getAvailableList($warehouseID = null)
        {
            $employee = $this->getUser();

            $this->fillWarehouses($employee);
            if (isset($warehouseID) && !$employee->isWarehouseExist($warehouseID)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }
            return $employee->getItemList($warehouseID);
        }

        public function takeTransfer($transferID)
        {
            $employee = $this->getUser();

            $this->fillPendingTransfers($employee);
            $transfer = $employee->getTransferByID($transferID);
            if (is_null($transfer)) {
                throw new \Exception('Pending transfer with this id wasn\'t found.', 403);
            }
            $this->fillWarehouses($employee);
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
                return 'Transaction list are empty.';
            }
        }

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

        public function addToTransfer(int $itemID, array $data)
        {
            $employee = $this->getUser();

            if (is_null($data['size'])){
                throw new \Exception('You need to set size.', 400);
            }

            $this->fillWarehouses($employee);
            $warehouse = $employee->getWarehouseByID($data['warehouseFromID']);
            if (is_null($warehouse)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }

            $items = $warehouse->getItemByID($itemID, $data['size']);
            if (is_null($items)) {
                throw new \Exception('Item not found in this warehouse.', 403);
            }

            $totalQuantity = 0;
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

        public function clearTransfer()
        {
            $employee = $this->getUser();
            setcookie('transfer', '', time() - 60 * 60, '/');
            return 'Transfer list was successfully cleared.';
        }

        public function getTransferList()
        {
            $employee = $this->getUser();
            return $this->getTransfersFromCookie();
        }

        public function sendTransfer(int $warehouseToID)
        {
            $employee = $this->getUser();
            $isSent = false;
            $transfers = $this->getTransfersFromCookie();
            foreach ($transfers as $transfer) {
                if ($transfer['warehouseID'] != $warehouseToID) {
                    $this->employeeRepository->registerTransfer($transfer, $warehouseToID);
                    $isSent = true;
                }
            }
            setcookie('transfer', '', time() - 60 * 60, '/');
            return $isSent ?
                'Transfer was successfully sent.':
                'There is nothing to send.';
        }

        private function sellValidate(array $data)
        {
            foreach ($data as $field => $value) {
                if (is_null($value)) {
                    throw new \Exception('Field '.$field.' cannot be empty.', 403);
                }
            }
        }

        public function sellItem(int $itemID, array $data)
        {
            $employee = $this->getUser();
            $this->sellValidate($data);
            $this->fillWarehouses($employee);
            $warehouse = $employee->getWarehouseByID($data['warehouseID']);
            if (is_null($warehouse)) {
                throw new \Exception('You have no access for this warehouse.', 403);
            }

            $items = $warehouse->getItemByID($itemID, $data['size']);
            if (is_null($items)) {
                throw new \Exception('Item not found in this warehouse.', 403);
            }
            $item = array_shift($items);//также при доьбавлении в куку трансфера

            if ($item->getQuantity() < $data['quantity']) {
                throw new \Exception('There is not enough items.', 403);
            }

            $this->employeeRepository->sellItem($warehouse->getID(), $employee->getID(),$itemID, $data);
            return 'Item was sold successfully.';
        }
    }