<?php
    namespace App\Model;

    abstract class EmployeeAbstract
    {
        /**
         * @var int
         */
        protected $id;

        /**
         * @var string
         */
        protected $name;

        /**
         * @var string
         */
        protected $lastname;

        /**
         * @var int
         */
        protected $companyID;

        /**
         * @var string
         */
        protected $email;

        /**
         * @var int
         */
        protected $phone;

        /**
         * @var string
         */
        protected $password;

        /**
         * @var Warehouse[]
         */
        protected $warehouses;

        protected function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->lastname = $data['lastname'];
            $this->companyID = $data['companyID'];
            $this->email = $data['email'];
            $this->password = $data['password'];
            $this->phone = $data['phone'];
            $this->warehouses = array();
        }

        /**
         * @return array
         */
        public function fullInfoToArray()
        {
            $result = array(
                'warehouses' => array()
            );
            foreach ($this->warehouses as $wh){
                array_push($result['warehouses'], $wh->fullInfoToArray());
            }
            return $result;
        }

        /**
         * @param int $id
         * @return Warehouse|mixed|null
         */
        public function getWarehouseByID(int $id)
        {
            foreach ($this->warehouses as $wh) {
                if ($wh->id == $id)
                    return $wh;
            }
            return null;
        }

        public function addWarehouse(Warehouse $wh)
        {
            array_push($this->warehouses, $wh);
        }

        //!!!!!!!!!!!!!!!!!!
        public function warehousesList()
        {
            $info = array();
            foreach ($this->warehouses as $wh){
                array_push($info, $wh->fullInfoToArray());
            }
            return $info;
        }

        public function getCompanyID()
        {
            return $this->companyID;
        }

        public function getName()
        {
            return $this->name;
        }

        public function getLastname()
        {
            return $this->lastname;
        }

        public function getEmail()
        {
            return $this->email;
        }

        public function getPhone()
        {
            return $this->phone;
        }

        public function getPassword()
        {
            return $this->password;
        }

        public function getID()
        {
            return $this->id;
        }

        public function setID(int $id)
        {
            $this->id = $id;
        }

        /**
         * @param array $newData
         * @return null|string
         */
        public function isChangeable(array $newData)
        {
            foreach ($newData as $field => $data) {
                if ($this->$field == $data) {
                    return $field;
                }
            }
            return null;
        }
    }