<?php
    namespace App\Repository;

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
    }