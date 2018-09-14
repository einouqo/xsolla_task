<?php
    namespace App\Model;

    class Employee extends EmployeeAbstract
    {
        /**
         * @var Warehouse[]
         */
        protected $warehouses;

        public function __construct(array $data)
        {
            parent::__construct($data);
            $this->warehouses = array();
        }

//        /**
//         * @return string
//         */
//        public function moveItem(Warehouse $from, Warehouse $to, int $id, int $quantity)
//        {
//            $moving = new Actions\Move($from, $to);
//            if ($moving->moveItem($id, $quantity)){
//                return "Move success.";
//            } else {
//                return "Not enough items in warehouse.";
//            }
//        }

//        /**
//         * @return array
//         */
//        public function fullInfoToArray()
//        {
//            $result = array(
//                'warehouses' => array()
//            );
//            foreach ($this->warehouses as $wh){
//                array_push($result['warehouses'], $wh->fullInfoToArray());
//            }
//            return $result;
//        }

//        /**
//         * @param int $id
//         * @return Warehouse|mixed|null
//         */
//        public function getWarehouseByID(int $id)
//        {
//            foreach ($this->warehouses as $wh) {
//                if ($wh->getID() == $id)
//                    return $wh;
//            }
//            return null;
//        }

        public function addWarehouse(Warehouse $wh)
        {
            array_push($this->warehouses, $wh);
        }

        //!!!!!!!!!!!!!!!!!!
//        public function warehousesList()
//        {
//            $info = array();
//            foreach ($this->warehouses as $wh){
//                array_push($info, $wh->fullInfoToArray());
//            }
//            return $info;
//        }
    }