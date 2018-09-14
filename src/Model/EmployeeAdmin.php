<?php
    namespace App\Model;


    class EmployeeAdmin extends EmployeeAbstract
    {
        /**
         * @var array
         */
        private $accesses;

        /**
         * @var Room[]
         */
        private $rooms;

        public function __construct(array $data)
        {
            parent::__construct($data);
            $this->accesses = array();
            $this->rooms = array();
        }

        public function addAccess(int $userID, int $addressID)
        {
            array_push($this->accesses,
                array(
                    'userID' => $userID,
                    'addressID' => $addressID
                )
            );
        }

        public function addRooms(Room $room)
        {
            array_push($this->rooms, $room);
        }

        //- Только вот зачем эта функция?
        //- Пусть пока полежит здесь...
//        public function makeWarehouse(Warehouse $warehouse)
//        {
//            foreach ($this->rooms as $key => $room) {
//                if ($room->getID() == $warehouse->getID()) {
//                    $this->rooms[$key] = &$warehouse;
//                    return true;
//                }
//            }
//            return false;
//        }
    }