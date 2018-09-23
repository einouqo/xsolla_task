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

        public function getWarehouses(int $employeeID)
        {
            $warehouses = [];
            $rows = $this->dbConnection->executeQuery(
                'SELECT id, addresses.address, name, capacity FROM addresses
                    INNER JOIN infoWarehouses ON addresses.address = infoWarehouses.address
                    INNER JOIN userAccessible ON addresses.id = userAccessible.id_address AND id_user = ?',
                [
                    $employeeID
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $warehouse =new Warehouse($row);
                $this->fillItems($warehouse);
                array_push($warehouses, $warehouse);
            }

            return $warehouses;
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

        public function getPendingTransfers(int $employeeID)
        {
            $transfers = [];
            $rows = $this->dbConnection->executeQuery(
                'SELECT transferHistory.id, id_from AS warehouseFromID, id_to AS warehouseToID, date_departure AS dateDeparture, date_receiving AS dateReceiving 
                    FROM transferHistory
                    INNER JOIN addresses ON transferHistory.id_to = addresses.id AND date_receiving IS NULL AND addresses.id IN 
                    (SELECT id_address FROM userAccessible WHERE id_user = ?)',
                [
                    $employeeID
                ]
            );

            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $transfer = new Transfer($row);
                $this->fillItemForTransfer($transfer);
                array_push($transfers, $transfer);
            }

            return $transfers;
        }

        public function addItemToWarehouse(Item $item, string $warehouseAddress)
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

        private function removeQuantity(int $warehouseID, array $item, int $avalebleQuantity)
        {
            $this->dbConnection->executeQuery(
                'UPDATE quantity SET quantity = ? WHERE id_item = ? AND size = ? AND 
                    address IN (SELECT address FROM addresses WHERE id = ?)',
                [
                    $avalebleQuantity - $item['quantity'],
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
    }