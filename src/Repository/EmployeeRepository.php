<?php
    namespace App\Repository;

    use App\Model\Employee;
    use App\Model\Item;
    use App\Model\Transfer;
    use App\Model\Warehouse;
    use Doctrine\DBAL\Connection;

    class EmployeeRepository
    {
        /**
         * @var Connection
         */
        private $dbConnection;

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
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

        public function fillWarehouses(Employee &$employee)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, addresses.address, name, capacity FROM addresses
                    INNER JOIN infoWarehouses ON addresses.address = infoWarehouses.address
                    INNER JOIN userAccessible ON addresses.id = userAccessible.id_address AND id_user = ?',
                [
                    $employee->getID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $warehouse =new Warehouse($row);
                $this->fillItems($warehouse);
                $employee->addWarehouse($warehouse);
            }
        }

        private function fillItemForTransfer(Transfer &$transfer)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, price, name, type, quantity, size FROM items
                    INNER JOIN transfer ON items.id = transfer.id_item AND id_history = ?',
                [
                    $transfer->getID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $transfer->addItem(new Item($row));
            }
        }

        public function fillPendingTransfers(Employee &$employee)
        {
            $rows = $this->dbConnection->executeQuery(
                'SELECT transferHistory.id, id_from AS warehouseFromID, id_to AS warehouseToID, date_departure AS dateDeparture, date_receiving AS dateReceiving 
                    FROM transferHistory
                    INNER JOIN addresses ON transferHistory.id_to = addresses.id AND date_receiving IS NULL AND addresses.id IN 
                    (SELECT id_address FROM userAccessible WHERE id_user = ?)',
                [
                    $employee->getID()
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $transfer = new Transfer($row);
                $this->fillItemForTransfer($transfer);
                $employee->addTransfer($transfer);
            }
        }

        private function addToWarehouse(Item $item, string $warehouseAddress)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO quantity(address, id_item, size, quantity) VALUES (?, ?, ?, ?)',
                [
                    $warehouseAddress,
                    $item->getID(),
                    $item->getSize(),
                    $item->getQuantity()
                ]
            );
        }

        public function addQuantity(Item $item, string $warehouseAddress, $quantity)
        {
            $this->dbConnection->executeQuery(
                'UPDATE quantity SET quantity = ? WHERE address = ? AND id_item = ? AND size = ?',
                [
                    $quantity + $item->getQuantity(),
                    $warehouseAddress,
                    $item->getID(),
                    $item->getSize()
                ]
            );
        }

        public function addItemToWarehouse(Item $item, string $warehouseAddress)
        {
            $isExist = $this->dbConnection->fetchAssoc(
                'SELECT quantity FROM quantity WHERE address = ? AND id_item = ? AND size = ?',
                [
                    $warehouseAddress,
                    $item->getID(),
                    $item->getSize()
                ]
            )['quantity'];

            is_null($isExist) ?
                $this->addToWarehouse($item, $warehouseAddress) :
                $this->addQuantity($item, $warehouseAddress, $isExist);
        }

        public function closeTransfer(int $transferID)
        {
            $this->dbConnection->executeQuery(
                'UPDATE transferHistory SET date_receiving = NOW() WHERE id = ?',
                [
                    $transferID
                ]
            );
        }

        private function deleteItem(int $warehouseID, array $item)
        {
            $this->dbConnection->executeQuery(
                'DELETE FROM quantity WHERE  id_item = ? AND size = ? AND 
                    address IN (SELECT address FROM addresses WHERE id = ?)',
                [
                    $item['id'],
                    $item['size'],
                    $warehouseID
                ]
            );
        }

        private function removeQuantity(int $warehouseID, array $item, int $availableQuantity)
        {
            $this->dbConnection->executeQuery(
                'UPDATE quantity SET quantity = ? WHERE id_item = ? AND size = ? AND 
                    address IN (SELECT address FROM addresses WHERE id = ?)',
                [
                    $availableQuantity - $item['quantity'],
                    $item['id'],
                    $item['size'],
                    $warehouseID
                ]
            );
        }

        private function removeItem(int $warehouseID, array $item)
        {
            $avaleble = $this->dbConnection->fetchAssoc(
                'SELECT quantity FROM quantity WHERE id_item = ? AND size = ? AND 
                    address IN (SELECT address FROM addresses WHERE id = ?)',
                [
                    $item['id'],
                    $item['size'],
                    $warehouseID
                ]
            );

            ($avaleble['quantity'] - $item['quantity']) == 0 ?
                $this->deleteItem($warehouseID, $item):
                $this->removeQuantity($warehouseID, $item, $avaleble['quantity']);
        }

        private function fillTranser(int $id, array $item)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO transfer(id_history, id_item, quantity, size) VALUES (?, ?, ?, ?)',
                [
                    $id,
                    $item['id'],
                    $item['quantity'],
                    $item['size']
                ]
            );
        }
        
        public function registerTransfer(array $transfer, int $warehouseToID)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO transferHistory(id_from, id_to, date_departure) VALUES (?, ?, NOW())',
                [
                    $transfer['warehouseID'],
                    $warehouseToID
                ]
            );
            $id_transfer = $this->dbConnection->lastInsertId();
            foreach ($transfer['items'] as $item) {
                $this->removeItem($transfer['warehouseID'], $item);
                $this->fillTranser($id_transfer, $item);
            }
        }

        public function sellItem(int $warehouseID, int $employeeID, int $itemID, array $data)
        {
            $unit = $this->dbConnection->fetchAssoc(
                'SELECT price FROM items WHERE id = ?',
                [
                    $itemID
                ]
            );

            $this->removeItem($warehouseID, $data + ['id' => $itemID]);

            $this->dbConnection->executeQuery(
                'INSERT INTO selling(id_address, id_user, id_item, size, quantity, unit_price, date)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())',
                [
                    $warehouseID,
                    $employeeID,
                    $itemID,
                    $data['size'],
                    $data['quantity'],
                    $unit['price'],
                ]
            );
        }
    }