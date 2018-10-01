<?php
    namespace App\Tests\Model;

    use App\Model\Employee;
    use App\Model\EmployeeAdmin;
    use App\Model\Room;
    use App\Model\Transfer;
    use App\Model\Warehouse;
    use PHPUnit\Framework\TestCase;

    class EmployeesTest extends TestCase
    {
        public $personalData;

        public function __construct(?string $name = null, array $data = [], string $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            $this->personalData = [
                'id' => 1,
                'name' => 'Name',
                'lastname' => 'Last Name',
                'companyID' => 1,
                'email' => 'email@email.co',
                'password' => 'qwerty123',
                'phone' => '899900000011'
            ];
        }

        /**
         * @dataProvider dataSetID
         */
        public function testSetID($personalData, $newID)
        {
            $user = new Employee($personalData);
            $this->assertEquals($user->getID(), $personalData['id']);
            $user->setID($newID);
            $this->assertEquals($user->getID(), $newID);
            $user = new EmployeeAdmin($personalData);
            $this->assertEquals($user->getID(), $personalData['id']);
            $user->setID($newID);
            $this->assertEquals($user->getID(), $newID);
        }

        public function dataSetID()
        {
            return [
                [
                    $this->personalData,
                    123
                ]
            ];
        }

        /**
         * @dataProvider dataIsWarehouseExist
         */
        public function testIsWarehouseExist($personalData, $warehouseData, $expected)
        {
            $user = new Employee($personalData);
            $warehouse = new Warehouse($warehouseData);
            $this->assertEquals($user->isWarehouseExist($warehouse->getID()), $expected);
            $user->addWarehouse($warehouse);
            $this->assertEquals($user->isWarehouseExist($warehouse->getID()), !$expected);
            $user = new EmployeeAdmin($personalData);
            $this->assertEquals($user->isWarehouseExist($warehouse->getID()), $expected);
            $user->addWarehouse($warehouse);
            $this->assertEquals($user->isWarehouseExist($warehouse->getID()), !$expected);
        }

        public function dataIsWarehouseExist()
        {
            return [
                [
                    $this->personalData,
                    [
                        'id' => 1,
                        'address' => 'addressTest',
                        'name' => 'testWarehouse',
                        'capacity' => 100
                    ],
                    false
                ]
            ];
        }

        /**
         * @dataProvider dataGetWarehouseByID
         */
        public function testGetWarehouseByID($personalData, $warehouseData)
        {
            $user = new Employee($personalData);
            $warehouse = new Warehouse($warehouseData);
            $this->assertEquals($user->getWarehouseByID($warehouse->getID()), null);
            $user->addWarehouse($warehouse);
            $this->assertEquals($user->getWarehouseByID($warehouse->getID()), $warehouse);
            $user = new EmployeeAdmin($personalData);
            $this->assertEquals($user->getWarehouseByID($warehouse->getID()), null);
            $user->addWarehouse($warehouse);
            $this->assertEquals($user->getWarehouseByID($warehouse->getID()), $warehouse);
        }

        public function dataGetWarehouseByID()
        {
            return [
                [
                    $this->personalData,
                    [
                        'id' => 1,
                        'address' => 'addressTest',
                        'name' => 'testWarehouse',
                        'capacity' => 100
                    ]
                ]
            ];
        }

        /**
         * @dataProvider dataGetTransferByID
         */
        public function testGetTransferByID($personalData, $transferData)
        {
            $user = new Employee($personalData);
            $transfer = new Transfer($transferData);
            $this->assertEquals($user->getTransferByID($transfer->getID()), null);
            $user->addTransfer($transfer);
            $this->assertEquals($user->getTransferByID($transfer->getID()), $transfer);
            $user = new EmployeeAdmin($personalData);
            $this->assertEquals($user->getTransferByID($transfer->getID()), null);
            $user->addTransfer($transfer);
            $this->assertEquals($user->getTransferByID($transfer->getID()), $transfer);
        }

        public function dataGetTransferByID()
        {
            return [
                [
                    $this->personalData,
                    [
                        'id' => 1,
                        'warehouseFromID' => 1,
                        'warehouseToID' => 2,
                        'dateDeparture' => 1990-12-12,
                        'dateReceiving' => null,
                        'items' => []
                    ]
                ]
            ];
        }

        /**
         * @dataProvider dataAdminIsRoomExist
         */
        public function testAdminIsRoomExist($personalData, $roomData)
        {
            $admin = new EmployeeAdmin($personalData);
            $room = new Room($roomData);
            $this->assertEquals($admin->isRoomExist($room->getID()), false);
            $admin->addRoom($room);
            $this->assertEquals($admin->isRoomExist($room->getID()), true);
        }

        public function dataAdminIsRoomExist()
        {
            return [
                [
                    $this->personalData,
                    [
                        'id' => 1,
                        'address' => 'address test'
                    ]
                ]
            ];
        }

        /**
         * @dataProvider dataAdminIsRoomExist
         */
        public function testAdminIsRoomExistByAddress($personalData, $roomData)
        {
            $admin = new EmployeeAdmin($personalData);
            $room = new Room($roomData);
            $this->assertEquals($admin->isRoomExistByAddress($room->getAddress()), false);
            $admin->addRoom($room);
            $this->assertEquals($admin->isRoomExistByAddress($room->getAddress()), true);
        }

        /**
         * @dataProvider dataAdminIsEmployeeExist
         */
        public function testAdminIsEmployeeExist($personalData, $employeeData)
        {
            $admin = new EmployeeAdmin($personalData);
            $employee = new Employee($employeeData);
            $this->assertEquals($admin->isEmployeeExist($employee->getID()), false);
            $admin->addEmployee($employee);
            $this->assertEquals($admin->isEmployeeExist($employee->getID()), true);
        }

        public function dataAdminIsEmployeeExist()
        {
            return [
                [
                    $this->personalData,
                    [
                        'id' => 2,
                        'name' => 'Name2',
                        'lastname' => 'Last2 Name2',
                        'companyID' => 2,
                        'email' => 'email2@email.co',
                        'password' => 'qwerty1234',
                        'phone' => '899900000012'
                    ]
                ]
            ];
        }

        /**
         * @dataProvider dataAdminIsAccessExist
         */
        public function testAdminIsAccessExist($personalData, $accessData)
        {
            $admin = new EmployeeAdmin($personalData);
            $this->assertEquals($admin->isAccessExist($accessData['userID'], $accessData['warehouseID']), false);
            $admin->addAccess($accessData['userID'], $accessData['warehouseID']);
            $this->assertEquals($admin->isAccessExist($accessData['userID'], $accessData['warehouseID']), true);
        }

        public function dataAdminIsAccessExist()
        {
            return [
                [
                    $this->personalData,
                    [
                        'userID' => 1,
                        'warehouseID' => 1
                    ]
                ]
            ];
        }
    }