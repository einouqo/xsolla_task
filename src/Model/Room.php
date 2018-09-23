<?php
    namespace App\Model;


    class Room
    {
        /**
         * @var int
         */
        protected $id;

        /**
         * @var string
         */
        protected $address;

        public function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->address = $data['address'];
        }

        public function getID()
        {
            return $this->id;
        }

        public function getAddress()
        {
            return $this->address;
        }

        public function getInfo()
        {
            return array(
                'id' => $this->id,
                'address' => $this->address
            );
        }
    }