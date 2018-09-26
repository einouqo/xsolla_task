<?php
    namespace App\Repository;

    use App\Model\Employee;
    use App\Model\EmployeeAdmin;
    use App\Model\Item;
    use App\Model\Room;
    use App\Model\Transfer;
    use App\Model\Warehouse;
    use Doctrine\DBAL\Connection;

    class AdminRepository
    {
        /**
         * @var Connection
         */
        private $dbConnection;

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
        }

        public function fillEmployees(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT users.id, name, lastname, id_company AS companyID, email, password, phone  FROM users
                    INNER JOIN personalInfo on users.id_personalData = personalInfo.id AND id_company = ?',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $admin->addEmployee(new Employee($row));
            }
        }

        public function getAccesses(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id_address AS warehouseID, id_user AS userID FROM userAccessible
                    WHERE id_company = ?',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $admin->addAccess($row['userID'], $row['warehouseID']);
            }
        }

        private function warehouseWithLoaded(Warehouse $warehouse)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT quantity FROM quantity WHERE address = ?',
                [
                    $warehouse->getAddress()
                ]
            );

            $loaded = 0;
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $loaded += $row['quantity'];
            }

            if (!$warehouse->setLoaded($loaded)) {
                throw new \Exception('Unexpected error.', 409);
            };
            return $warehouse;
        }

        private function fillItems(Warehouse &$warehouse)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, price, name, type, quantity, size FROM items
                    INNER JOIN quantity ON items.id = quantity.id_item AND address = ?',
                [
                    $warehouse->getAddress()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $warehouse->addItem(new Item($row));
            }
        }

        public function fillWarehousesWithItems(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, addresses.address, name, capacity FROM addresses
                    INNER JOIN infoWarehouses on addresses.address = infoWarehouses.address AND id_company = ?',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $warehouse =new Warehouse($row);
                $this->fillItems($warehouse);
                $admin->addWarehouse($warehouse);
            }
        }

        public function fillWarehouses(EmployeeAdmin &$admin, bool $setLoaded = false)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, addresses.address, name, capacity FROM addresses
                    INNER JOIN infoWarehouses on addresses.address = infoWarehouses.address AND id_company = ?',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $setLoaded ?
                    $admin->addWarehouse($this->warehouseWithLoaded(new Warehouse($row))):
                    $admin->addWarehouse(new Warehouse($row));
            }
        }

        public function getRooms(int $companyID)
        {
            $rooms = [];
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, address FROM addresses WHERE id_company = ?',
                [
                    $companyID
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $rooms[] = new Room($row);
            }

            return $rooms;
        }

        public function giveAccess(array $data, int $companyID)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO userAccessible(id_company, id_address, id_user)
                    VALUES (?, ?, ?)',
                [
                    $companyID,
                    $data['warehouseID'],
                    $data['userID']
                ]
            );
        }

        public function deleteAccess(array $data, int $companyID)
        {
            $this->dbConnection->executeQuery(
                'DELETE FROM userAccessible
                    WHERE id_company = ? AND id_address = ? AND id_user = ?',
                [
                    $companyID,
                    $data['warehouseID'],
                    $data['userID']
                ]
            );
        }

        public function createWarehouse(array $data)
        {
            $row = $this->dbConnection->fetchAssoc(
                'SELECT address FROM addresses WHERE id = ?',
                [
                    $data['roomID']
                ]
            );

            $this->dbConnection->executeQuery(
                'INSERT INTO infoWarehouses(address, name, capacity) VALUES (?, ?, ?)',
                [
                    $row['address'],
                    $data['name'],
                    $data['capacity']
                ]
            );
        }

        private function isEmpty(string $address)
        {
            $items = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM quantity
                    WHERE address = ?',
                [
                    $address
                ]
            );

            if ($items['count'] != 0) {
                throw new \Exception('Warehouse not empty. Deleting forbidden.', 403);
            }
        }

        private function hasCompletedTransfers(int $warehouseID)
        {
            $unComlitedTransfers = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM transferHistory WHERE date_receiving IS NULL AND id_to = ?',
                [
                    $warehouseID
                ]
            );

            if ($unComlitedTransfers['count'] != 0) {
                throw new \Exception('Warehouse are awaiting '.$unComlitedTransfers['count'].' transfer(-s). Deleting forbidden.', 403);
            }
        }

        public function deleteWarehouse(int $id, string $address)
        {
            $this->isEmpty($address);
            $this->hasCompletedTransfers($id);

            $this->dbConnection->executeQuery(
                'DELETE FROM infoWarehouses WHERE address = ?',
                [
                    $address
                ]
            );

            $this->dbConnection->executeQuery(
                'DELETE FROM userAccessible WHERE id_address = ?',
                [
                    $id
                ]
            );
        }

        public function addRoom(array $data, int $companyID)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO addresses(address, id_company) VALUES (?, ?)',
                [
                    $data['address'],
                    $companyID
                ]
            );
        }

        public function deleteRoom(int $roomID)
        {
            $unfinishedTransfer = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM transferHistory
                    WHERE (id_from = ? OR id_to = ?) AND date_receiving IS NULL',
                [
                    $roomID,
                    $roomID
                ]
            );

            if ($unfinishedTransfer['count'] != 0) {
                throw new \Exception('There is unfinished transfer.', 403);
            }

            $this->dbConnection->executeQuery(
                'DELETE FROM addresses WHERE id = ?',
                [
                    $roomID
                ]
            );
        }

        public function changeWarehouse(Warehouse $warehouse, array $data)
        {
            $this->dbConnection->executeQuery(
                'UPDATE infoWarehouses SET name = ?, capacity = ? WHERE address = ?',
                [
                    is_null($data['name']) ?
                        $warehouse->getName() :
                        $data['name'],
                    is_null($data['capacity']) ?
                        $warehouse->getCapacity() :
                        $data['capacity'],
                    $warehouse->getAddress()
                ]
            );
        }

        private function fillTransfer(Transfer &$transfer)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, price, name, type, size, quantity FROM items 
                    INNER JOIN transfer ON items.id = transfer.id_item AND id_history = ?',
                [
                    $transfer->getID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $transfer->addItem(new Item($row));
            }
        }

        public function fillTransfers(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, id_from AS warehouseFromID, id_to AS warehouseToID, date_departure AS dateDeparture, date_receiving AS dateReceiving
                    FROM transferHistory WHERE id_from IN (SELECT id FROM addresses WHERE id_company = ?) OR id_to IN (SELECT id FROM addresses WHERE id_company = ?)',
                [
                    $admin->getCompanyID(),
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $transfer = new Transfer($row);
                $this->fillTransfer($transfer);
                $admin->addTransfer($transfer);
            }
        }

        public function createItem(array $data)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO items(price, name, type) VALUES (?, ?, ?)',
                [
                    $data['price'],
                    $data['name'],
                    $data['type']
                ]
            );
            return $this->dbConnection->lastInsertId();
        }

        private function addQuantity(array $data, string $address, int $id_item)
        {
            $old = $this->dbConnection->fetchAssoc(
                'SELECT quantity FROM quantity WHERE address = ? AND id_item = ? AND size = ?',
                [
                    $address,
                    $id_item,
                    $data['size']
                ]
            );

            $this->dbConnection->executeQuery(
                'UPDATE quantity SET quantity = ? WHERE address = ? AND id_item = ? AND size = ?',
                [
                    $old['quantity'] + $data['quantity'],
                    $address,
                    $id_item,
                    $data['size']
                ]
            );
        }

        private function addItemToWarehouse(array $data, string $address, int $id_item)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO quantity(address, id_item, size, quantity) VALUES (?, ?, ?, ?)',
                [
                    $address,
                    $id_item,
                    $data['size'],
                    $data['quantity']
                ]
            );
        }

        public function addItem(array $data, string $address)
        {
            $existItem = $this->dbConnection->fetchAssoc(
                'SELECT id FROM items WHERE price = ? AND name = ? AND type = ?',
                [
                    $data['price'],
                    $data['name'],
                    $data['type']
                ]
            );

            $id = $existItem['id'] ?? $this->createItem($data);

            $alreadyIs = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM quantity WHERE address = ? AND id_item = ? AND size = ?',
                [
                    $address,
                    $id,
                    $data['size'],
                ]
            );

            $this->dbConnection->executeQuery(
                'INSERT INTO delivery(address, id_item, size, quantity, date) VALUES (?, ?, ?, ?, NOW())',
                [
                    $address,
                    $id,
                    $data['size'],
                    $data['quantity']
                ]
            );

            $alreadyIs['count'] == 0 ?
                $this->addItemToWarehouse($data, $address, $id):
                $this->addQuantity($data, $address, $id);
        }

        public function changeItem(int $itemID, array $data)
        {
            $itemOld = $this->dbConnection->fetchAssoc(
                'SELECT price, name, type FROM items WHERE id = ?',
                [
                    $itemID
                ]
            );

            if ((is_null($data['price']) || $data['price'] == $itemOld['price']) &&
                (is_null($data['name']) || $data['name'] == $itemOld['name']) &&
                (is_null($data['type']) || $data['type'] == $itemOld['type'])) {
                throw new \Exception('Nothing to change', 400);
            }

            $this->dbConnection->executeQuery(
                'UPDATE items SET price = ?, name = ?, type = ? WHERE id = ?',
                [
                    $data['price'] ?? $itemOld['price'],
                    $data['name'] ?? $itemOld['name'],
                    $data['type'] ?? $itemOld['type'],
                    $itemID
                ]
            );
        }

        public function getDeliveryCondition(int $itemID, string $address, string $size, \DateTime $date = null)
        {
            return isset($date) ?
                $this->dbConnection->fetchAssoc(
                'SELECT SUM(quantity) AS quantity FROM delivery WHERE id_item = ? AND address = ? AND size = ? AND date > ? ;',
                    [
                        $itemID,
                        $address,
                        $size,
                        $date->format('Y-m-d H:i:s')
                    ]
                )['quantity'] : 0;
        }

        public function getSellingCondition(int $itemID, string $warehouseID, string $size, \DateTime $date = null)
        {
            return isset($date) ? $this->dbConnection->fetchAssoc(
                'SELECT SUM(quantity) AS quantity FROM selling WHERE id_item = ? AND id_address = ? AND size = ? AND date > ? ;',
                [
                    $itemID,
                    $warehouseID,
                    $size,
                    $date->format('Y-m-d H:i:s')
                ]
            )['quantity'] : 0;
        }

        public function getSendedCondition(int $itemID, string $warehouseID, string $size, \DateTime $date = null)
        {
            return isset($date) ? $this->dbConnection->fetchAssoc(
                'SELECT SUM(quantity) AS quantity FROM transfer
                    INNER JOIN transferHistory ON transfer.id_history = transferHistory.id AND id_item = ? AND id_from = ? AND size = ? AND date_departure > ?',
                [
                    $itemID,
                    $warehouseID,
                    $size,
                    $date->format('Y-m-d H:i:s')
                ]
            )['quantity'] : 0;
        }

        public function getReceivingCondition(int $itemID, string $warehouseID, string $size, \DateTime $date = null)
        {
            return isset($date) ? $this->dbConnection->fetchAssoc(
                'SELECT SUM(quantity) AS quantity FROM transfer
                    INNER JOIN transferHistory ON transfer.id_history = transferHistory.id AND id_item = ? AND id_to = ? AND size = ? AND date_receiving > ?',
                [
                    $itemID,
                    $warehouseID,
                    $size,
                    $date->format('Y-m-d H:i:s')
                ]
            )['quantity'] : 0;
        }

        public function itemState(int $itemID, int $companyID, \DateTime $date = null)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT addresses.id AS id, quantity.address AS address, size, quantity, price FROM quantity
                    INNER JOIN addresses ON addresses.address = quantity.address AND id_item = ? AND id_company = ?
                    INNER JOIN items ON quantity.id_item = items.id',
                [
                    $itemID,
                    $companyID
                ]
            );

            $result = [
                'itemID' => $itemID,
                'warehouses' => []
            ];
            $totalQuantity = 0;
            $totalPrice = 0.;
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $quantity = $row['quantity'] -
                    ($this->getDeliveryCondition($itemID, $row['address'], $row['size'], $date) ?? 0) +
                    ($this->getSellingCondition($itemID, $row['id'], $row['size'], $date) ?? 0) +
                    ($this->getSendedCondition($itemID, $row['id'], $row['size'], $date) ?? 0) -
                    ($this->getReceivingCondition($itemID, $row['id'], $row['size'], $date) ?? 0);
                $totalQuantity += $quantity;
                $totalPrice += $quantity * $row['price'];
                array_push(
                    $result['warehouses'],
                    [
                        'id' => $row['id'],
                        'address' => $row['address'],
                        'size' => $row['size'],
                        'quantity' => $quantity
                    ]
                );
            }
            $result += [
                'Total quantity' => $totalQuantity,
                'Total price' => $totalPrice
            ];

            return $result;
        }
    }