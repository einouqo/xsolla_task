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

        /**
         * @var Employee[]
         */
        private $employees;

        public function __construct(array $data)
        {
            parent::__construct($data);
            $this->accesses = array();
            $this->rooms = array();
            $this->employees = array();
        }

        /**
         * @param int $userID
         * @param int $warehouseID
         */
        public function addAccess(int $userID, int $warehouseID)
        {
            array_push($this->accesses,
                array(
                    'userID' => $userID,
                    'warehouseID' => $warehouseID
                )
            );
        }

        /**
         * @param Room $room
         */
        public function addRoom(Room $room)
        {
            array_push($this->rooms, $room);
        }

        /**
         * @param Employee $employee
         */
        public function addEmployee(Employee $employee)
        {
            array_push($this->employees, $employee);
        }

        /**
         * @param int $roomID
         * @return bool
         */
        public function isRoomExist(int $roomID)
        {
            foreach ($this->rooms as $room) {
                if ($room->getID() == $roomID) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @param string $address
         * @return bool
         */
        public function isRoomExistByAddress(string $address)
        {
            foreach ($this->rooms as $room) {
                if ($room->getAddress() == $address) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @param int $employeeID
         * @return bool
         */
        public function isEmployeeExist(int $employeeID)
        {
            foreach ($this->employees as $employee) {
                if ($employee->getID() == $employeeID) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @param int $userID
         * @param int $warehouseID
         * @return bool
         */
        public function isAccessExist(int $userID, int $warehouseID)
        {
            foreach ($this->accesses as $access) {
                if ($access['userID'] == $userID && $access['warehouseID'] == $warehouseID) {
                    return true;
                }
            }
            return false;
        }

        /**
         * @return array
         */
        public function getRoomsList()
        {
            $roomList = [];
            foreach ($this->rooms as $room) {
                array_push($roomList, $room->getInfo());
            }
            return $roomList;
        }

        /**
         * @param int $warehouseID
         * @return array
         */
        public function getWarehouseTransfers(int $warehouseID)
        {
            $transfers = [];
            foreach ($this->transfers as $transfer) {
                if ($transfer->getWarehouseFromID() == $warehouseID || $transfer->getWarehouseToID() == $warehouseID) {
                    array_push($transfers, $transfer->infoToArray());
                }
            }
            return $transfers;
        }

        /**
         * @param int $itemID
         * @return array
         */
        public function getItemTransfers(int $itemID)
        {
            $transfers = [];
            foreach ($this->transfers as $transfer) {
                if ($transfer->isItemInTransaction($itemID)) {
                    array_push($transfers, $transfer->infoToArray());
                }
            }
            return $transfers;
        }
    }