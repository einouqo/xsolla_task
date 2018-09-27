<?php
    namespace App\Model;

    class Company
    {
        /**
         * @var int
         */
        private $id;

        /**
         * @var string
         */
        private $name;

        /**
         * @var EmployeeAbstract
         */
        private $employee;

        public function __construct(array $data)
        {
            $this->id = $data['id'];
            $this->name = $data['name'];
            $this->employee = $data['employee'];
        }

        /**
         * @param Warehouse $wh
         */
        public function addWarehouseToEmployee(Warehouse $wh)
        {
            $this->employee->addWarehouse($wh);
        }

        /**
         * @return EmployeeAbstract|mixed
         */
        public function getEmployee()
        {
            return $this->employee;
        }
    }