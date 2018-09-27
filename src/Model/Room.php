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
        public function getAddress()
        {
            return $this->address;
        }

        /**
         * @return array
         */
        public function getInfo()
        {
            return array(
                'id' => $this->id,
                'address' => $this->address
            );
        }
    }