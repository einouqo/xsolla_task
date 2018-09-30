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

        /**
         * @param EmployeeAdmin $admin
         * @throws \Doctrine\DBAL\DBALException
         */
        public function fillEmployees(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT users.id, name, lastname, id_company AS companyID, email, password, phone  FROM users
                    INNER JOIN personalInfo on users.id_personalData = personalInfo.id AND id_company = ? AND position <> 1',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $admin->addEmployee(new Employee($row));
            }
        }

        /**
         * @param EmployeeAdmin $admin
         * @throws \Doctrine\DBAL\DBALException
         */
        public function fillAccesses(EmployeeAdmin &$admin)
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

        /**
         * @param EmployeeAdmin $admin
         * @throws \Doctrine\DBAL\DBALException
         */
        public function fillRooms(EmployeeAdmin &$admin)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, address FROM addresses WHERE id_company = ?',
                [
                    $admin->getCompanyID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $admin->addRoom(new Room($row));
            }
        }

        /**
         * @param array $data
         * @param int $companyID
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @param int $companyID
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param string $address
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $warehouseID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        private function hasUncompletedTransfers(int $warehouseID)
        {
            $uncomlitedTransfers = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM transferHistory WHERE date_receiving IS NULL AND id_to = ?',
                [
                    $warehouseID
                ]
            );

            if ($uncomlitedTransfers['count'] != 0) {
                throw new \Exception('Warehouse are awaiting '.$uncomlitedTransfers['count'].' transfer(-s). Deleting forbidden.', 403);
            }
        }

        /**
         * @param int $id
         * @param string $address
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteWarehouse(int $id, string $address)
        {
            $this->isEmpty($address);
            $this->hasUncompletedTransfers($id);

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

        /**
         * @param array $data
         * @param int $companyID
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $roomID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param Warehouse $warehouse
         * @param array $data
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param Transfer $transfer
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param EmployeeAdmin $admin
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @return string
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @param string $address
         * @param int $id_item
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @param string $address
         * @param int $id_item
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @param string $address
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $itemID
         * @param array $data
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeItem(int $itemID, array $data)
        {
            $itemOld = $this->dbConnection->fetchAssoc(
                'SELECT price, name, type FROM items WHERE id = ?',
                [
                    $itemID
                ]
            );

            if (is_null($itemOld['name'])) {
                throw new \Exception('Item was not found.', 400);
            }

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

        /**
         * @param int $itemID
         * @param int $companyID
         * @return array
         */
        public function getItems(int $itemID, int $companyID)
        {
            return $this->dbConnection->fetchAll(
                'SELECT addresses.id AS id, quantity.address AS address, items.name, size, quantity, price FROM quantity
                    INNER JOIN addresses ON addresses.address = quantity.address AND id_item = ? AND id_company = ?
                    INNER JOIN items ON quantity.id_item = items.id',
                [
                    $itemID,
                    $companyID
                ]
            );
        }
    }