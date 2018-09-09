<?php
    namespace App\Model;

    class Warehouse
    {
        /**
         * @var int
         */
        public $id;

        /**
         * @var string
         */
        private $name;

        /**
         * @var string
         */
        public $address;

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
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->address = $data['address'];
            $this->capacity = $data['capacity'];
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

        /**
         * @return array
         */
        public function getItemsInfo()
        {
            $result = array();
            foreach ($this->items as $item){
                array_push($result, $item->infoToArray());
            }
            return $result;
        }

        /**
         * @return array
         */
        private function shortInfoToArray()
        {
            return array(
                'id' => $this->id,
                'name' => $this->name,
                'address' => $this->address
            );
        }

        /**
         * @return array
         */
        public function fullInfoToArray()
        {
            $shortInfo = $this->shortInfoToArray();
            $shortInfo += ['capacity' => $this->capacity];
            return $shortInfo += ['items' => $this->getItemsInfo()];
        }

        public function getID()
        {
            return $this->id;
        }

        public function getAddress()
        {
            return $this->address;
        }
    }