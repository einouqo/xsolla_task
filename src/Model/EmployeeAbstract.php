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
            $this->transfers = [];
            $this->warehouses = [];
        }

        /**
         * @return array
         */
        public function getPersonalInfo()
        {
            return [
                'name' => $this->name,
                'lastname' => $this->lastname,
                'email' => $this->email,
                'password' => $this->password,
                'phone' => $this->phone
            ];
        }

        /**
         * @return int|mixed
         */
        public function getCompanyID()
        {
            return $this->companyID;
        }

        /**
         * @return mixed|string
         */
        public function getName()
        {
            return $this->name;
        }

        /**
         * @return mixed|string
         */
        public function getLastname()
        {
            return $this->lastname;
        }

        /**
         * @return mixed|string
         */
        public function getEmail()
        {
            return $this->email;
        }

        /**
         * @return int|mixed
         */
        public function getPhone()
        {
            return $this->phone;
        }

        /**
         * @return mixed|string
         */
        public function getPassword()
        {
            return $this->password;
        }

        /**
         * @return int|mixed
         */
        public function getID()
        {
            return $this->id;
        }

        /**
         * @param int $id
         */
        public function setID(int $id)
        {
            $this->id = $id;
        }

        /**
         * @param Warehouse $warehouse
         */
        public function addWarehouse(Warehouse $warehouse)
        {
            array_push($this->warehouses, $warehouse);
        }

        /**
         * @param Transfer $transfer
         */
        public function addTransfer(Transfer $transfer)
        {
            array_push($this->transfers, $transfer);
        }

        /**
         * @param int $warehouseID
         * @return bool
         */
        public function isWarehouseExist($warehouseID)
        {
            foreach ($this->warehouses as $warehouse) {
                if ($warehouse->getID() == $warehouseID) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @return array
         */
        public function getWarehousesList()
        {
            $warehouses = [];
            foreach ($this->warehouses as $warehouse){
                array_push($warehouses, $warehouse->getShortInfo());
            }
            return $warehouses;
        }

        /**
         * @param $id
         * @return Warehouse|mixed|null
         */
        public function getWarehouseByID($id)
        {
            foreach ($this->warehouses as $warehouse) {
                if ($warehouse->getID() == $id) {
                    return $warehouse;
                }
            }
            return null;
        }

        /**
         * @return array
         */
        public function getTransferList()
        {
            $transfers = [];
            foreach ($this->transfers as $transfer) {
                array_push($transfers, $transfer->infoToArray());
            }
            return $transfers;
        }

        /**
         * @param $id
         * @return Transfer|mixed|null
         */
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