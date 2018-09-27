<?php
    namespace App\Model;

    class Warehouse extends Room
    {
        /**
         * @var string
         */
        private $name;

        /**
         * @var int
         */
        private $capacity;

        /**
         * @var \App\Model\Item[]
         */
        private $items;

        /**
         * @var int
         */
        private $loaded;

        public function __construct(array $data)
        {
            parent::__construct($data);
            $this->name = $data['name'];
            $this->capacity = $data['capacity'];
            $this->loaded = 0;
            $this->items = array();
        }

        public function addItem(Item $item)
        {
            $this->loaded += $item->quantity;
            foreach ($this->items as $key => $elem){
                if ($elem->id == $item->id && $elem->size == $item->size){
                    $this->items[$key]->quantity += $item->quantity;
                    return;
                }
            }
            array_push($this->items, $item);
        }

        /**
         * 'null' - removing failed
         * 'Item' - removing done
         * @param int $id
         * @param int $quantity
         * @return mixed
         */
        public function removeItem(int $id, int $quantity)
        {
            foreach ($this->items as $key => $elem) {
                if ($elem->id == $id){
                    $result = $elem->remove($quantity);
                    if (!is_null($result)){
                        $this->loaded -= $quantity;
                        if ($elem->quantity == 0){
                            array_splice($this->items, $key, 1);
                        }
                    }
                    return $result;
                }
            }
            return null;
        }

        public function getItemByID(int $itemID, $size = null)
        {
            $result = [];
            foreach ($this->items as $item) {
                if ($item->getID() == $itemID && (is_null($size) xor (isset($size) && $item->getSize() == $size))) {
                    array_push($result, $item);
                }
            }
            return count($result) == 0 ?
                null:
                $result;
        }

        public function getItems()
        {
            return $this->items;
        }

        /**
         * @return array
         */
        public function getItemsInfo()
        {
            $result = array(
                'items' => array()
            );
            $totalPrice = 0.;
            $totalQuantity = 0;
            foreach ($this->items as $item){
                array_push($result['items'], $item->infoToArray());
                $totalPrice += $item->getTotalPrice();
                $totalQuantity += $item->getQuantity();
            }
            $result += [
                'Total price: ' => $totalPrice
            ];
            return $result;
        }

        /**
         * @return array
         */
        public function getShortInfo()
        {
            $info = $this->getInfo();
            $info += [
                'name' => $this->name,
                'capacity' => $this->capacity,
                'loaded' =>$this->loaded,
            ];
            return $info;
        }

        /**
         * @return array
         */
        public function getFullInfo()
        {
            $info = $this->getShortInfo();
            $info += $this->getItemsInfo();
            return $info;
        }

        public function getName()
        {
            return $this->name;
        }

        public function getCapacity()
        {
            return $this->capacity;
        }

        public function getFreeSpace()
        {
            return $this->capacity - $this->loaded;
        }

        public function getLoaded()
        {
            return $this->loaded;
        }

        public function setLoaded(int $loaded)
        {
            if ($loaded <= $this->capacity) {
                $this->loaded = $loaded;
                return true;
            } else {
                return false;
            }
        }
    }