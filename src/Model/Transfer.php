<?php
    namespace App\Model;

    class Transfer
    {
        /**
         * @var int
         */
        private $id;

        /**
         * @var int
         */
        private $warehouseFromID;

        /**
         * @var int
         */
        private $warehouseToID;

        /**
         * @var Item[]
         */
        private $items;

        /**
         * @var string
         */
        private $dateDeparture;

        /**
         * @var string
         */
        private $dateReceiving;

        public function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->warehouseFromID = $data['warehouseFromID'];
            $this->warehouseToID = $data['warehouseToID'];
            $this->dateDeparture = $data['dateDeparture'];
            $this->dateReceiving = $data['dateReceiving'];
            $this->items = array();
        }

        public function shortInfoToArray()
        {
            return array(
                'id' => $this->id,
                'warehouseFromID' => $this->warehouseFromID,
                'warehouseToID' => $this->warehouseToID,
                'dateDeparture' => $this->dateDeparture,
                'dateReceiving' => $this->dateReceiving
            );
        }

        /**
         * @return array
         */
        public function infoToArray()
        {
            $items = [];
            foreach ($this->items as $item) {
                array_push($items, $item->infoToArray());
            }

            $info = $this->shortInfoToArray();
            $info += ['items' => $items];
            return $info;
        }

        /**
         * @param Item $item
         */
        public function addItem(Item $item)
        {
            array_push($this->items, $item);
        }

        /**
         * @return bool
         */
        public function isPending()
        {
            return is_null($this->dateReceiving);
        }

        /**
         * @return int|mixed
         */
        public function getID()
        {
            return $this->id;
        }

        /**
         * @return int|mixed
         */
        public function getWarehouseFromID()
        {
            return $this->warehouseFromID;
        }

        /**
         * @return int|mixed
         */
        public function getWarehouseToID()
        {
            return $this->warehouseToID;
        }

        /**
         * @return Item[]|array
         */
        public function getItems()
        {
            return $this->items;
        }

        /**
         * @param int $itemID
         * @return bool
         */
        public function isItemInTransaction(int $itemID)
        {
            foreach ($this->items as $item) {
                if ($item->getID() == $itemID) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @return int|mixed
         */
        public function itemsQuantity()
        {
            $quantity = 0;
            foreach ($this->items as $item) {
                $quantity += $item->getQuantity();
            }
            return $quantity;
        }
    }