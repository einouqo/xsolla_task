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

        /**
         * @return int|mixed
         */
        public function getID()
        {
            return $this->id;
        }

        /**
         * @return mixed|string
         */
        public function getSize()
        {
            return $this->size;
        }

        /**
         * @return float|mixed
         */
        public function getUnitPrice()
        {
            return $this->price;
        }

        /**
         * @return int|mixed
         */
        public function getQuantity()
        {
            return $this->quantity;
        }

        /**
         * @return float|int
         */
        public function getTotalPrice()
        {
            return $this->quantity * $this->price;
        }

        /**
         * @return array
         */
        public function shortInfo()
        {
            return array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'price' => (float)$this->price
            );
        }

        /**
         * @return array
         */
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