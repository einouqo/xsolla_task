<?php
    namespace App\Model;

    class Employee extends EmployeeAbstract
    {
        public function __construct(array $data)
        {
            parent::__construct($data);
        }

        /**
         * @return string
         */
        public function moveItem(Warehouse $from, Warehouse $to, int $id, int $quantity)
        {
            $moving = new Actions\Move($from, $to);
            if ($moving->moveItem($id, $quantity)){
                return "Move success.";
            } else {
                return "Not enough items in warehouse.";
            }
        }
    }