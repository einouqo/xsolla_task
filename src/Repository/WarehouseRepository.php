<?php
    namespace App\Repository;

    use App\Model\Employee;
    use App\Model\EmployeeAdmin;
    use App\Model\Item;
    use App\Model\Warehouse;
    use Doctrine\DBAL\Connection;

    class WarehouseRepository
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
         * @param int $itemID
         * @param string $address
         * @param string $size
         * @param \DateTime|null $date
         * @return int
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $itemID
         * @param string $warehouseID
         * @param string $size
         * @param \DateTime|null $date
         * @return int
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $itemID
         * @param string $warehouseID
         * @param string $size
         * @param \DateTime|null $date
         * @return int
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $itemID
         * @param string $warehouseID
         * @param string $size
         * @param \DateTime|null $date
         * @return int
         * @throws \Doctrine\DBAL\DBALException
         */
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


        /**
         * @param Warehouse $warehouse
         * @return Warehouse
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param Warehouse $warehouse
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param EmployeeAdmin $admin
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param EmployeeAdmin $admin
         * @param bool $setLoaded
         * @throws \Doctrine\DBAL\DBALException
         */
        public function fillWarehousesForAdmin(EmployeeAdmin &$admin, bool $setLoaded = false)
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

        /**
         * @param Employee $employee
         * @throws \Doctrine\DBAL\DBALException
         */
        public function fillWarehousesForEmployee(Employee &$employee)
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
    }