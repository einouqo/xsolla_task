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
         * @var int
         */
        private $size;

        /**
         * @var int
         */
        public $quantity;

        /**
         * @var float
         */
        public $price;

         /**
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

        public function infoToArray()
        {
            return array(
                'id' => $this->id,
                'name' => $this->name,
                'type' => $this->type,
                'size' => $this->size,
                'price' => $this->price,
                'quantity' => $this->quantity
            );
        }

        public function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->type = $data['type'];
            $this->size = $data['size'];
            $this->price = $data['price'];
            $this->quantity = $data['quantity'];
        }
    }