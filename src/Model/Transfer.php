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

        public function addItem(Item $item)
        {
            array_push($this->items, $item);
        }

        public function isPending()
        {
            return is_null($this->dateReceiving);
        }

        public function getID()
        {
            return $this->id;
        }

        public function getWarehouseFromID()
        {
            return $this->warehouseFromID;
        }

        public function getWarehouseToID()
        {
            return $this->warehouseToID;
        }

        public function getItems()
        {
            return $this->items;
        }

        public function isItemInTransaction(int $itemID)
        {
            foreach ($this->items as $item) {
                if ($item->getID() == $itemID) {
                    return true;
                }
            }
            return false;
        }

        public function itemsQuantity()
        {
            $quantity = 0;
            foreach ($this->items as $item) {
                $quantity += $item->getQuantity();
            }
            return $quantity;
        }
    }