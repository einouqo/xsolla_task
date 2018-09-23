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
         * @var Transfer[]
         */
        protected $transfers;

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
            $this->transfers = array();
            $this->warehouses = array();
        }

        public function getPersonalInfo()
        {
            return array(
                'name' => $this->name,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'password' => $this->password,
                'phone' => $this->phone
            );
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

        public function addWarehouse(Warehouse $warehouse)
        {
            array_push($this->warehouses, $warehouse);
        }

        public function addTransfer(Transfer $transfer)
        {
            array_push($this->transfers, $transfer);
        }

        public function isWarehouseExist(int $warehouseID)
        {
            foreach ($this->warehouses as $warehouse) {
                if ($warehouse->getID() == $warehouseID) {
                    return true;
                }
            }
            return false;
        }

        public function getWarehousesList()
        {
            $warehouses = [];
            foreach ($this->warehouses as $warehouse){
                array_push($warehouses, $warehouse->getShortInfo());
            }
            return $warehouses;
        }

        public function getWarehouseByID(int $id)
        {
            foreach ($this->warehouses as $warehouse) {
                if ($warehouse->getID() == $id) {
                    return $warehouse;
                }
            }
            return null;
        }

        public function getTransferList()
        {
            $transfers = [];
            foreach ($this->transfers as $transfer) {
                array_push($transfers, $transfer->infoToArray());
            }
            return $transfers;
        }

        public function getTransferByID(int $id)
        {
            foreach ($this->transfers as $transfer) {
                if ($transfer->getID() == $id) {
                    return $transfer;
                }
            }
            return null;
        }
    }