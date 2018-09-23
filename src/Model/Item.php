<?php
    namespace App\Model;

    class Item
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
        private $type;

        /**
         * @var string
         */
        public $size;

        /**
         * @var int
         */
        public $quantity;

        /**
         * @var float
         */
        public $price;

        public function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->type = $data['type'];
            $this->size = $data['size'];
            $this->price = $data['price'];
            $this->quantity = $data['quantity'];
        }

        public function getID()
        {
            return $this->id;
        }

        public function getSize()
        {
            return $this->size;
        }

        public function getUnitPrice()
        {
            return $this->price;
        }

        public function getQuantity()
        {
            return $this->quantity;
        }

        public function getTotalPrice()
        {
            return $this->quantity * $this->price;
        }

         /**???????????????????????????????????
          * 'null' - removing failed
          * 'new Item' - removing done
          * @return mixed
          */
        public function remove(int $quantity)
        {
            if ($this->quantity - $quantity < 0){
                return null;
            } else {
                $this->quantity -= $quantity;
                $info = $this->infoToArray();
                $info['quantity'] = $quantity;
                return new Item($info);
            }
        }

        public function shortInfo()
        {
            return array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'price' => (float)$this->price
            );
        }

        public function infoToArray()
        {
            $result = $this->shortInfo();
            $result += [
                'size' => $this->size,
                'quantity' => (int)$this->quantity
            ];
            return $result;
        }
    }