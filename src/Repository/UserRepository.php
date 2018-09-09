<?php
    namespace App\Repository;

    use App\Model\Employee;
    use App\Model\EmployeeAbstract;
    use App\Model\Item;
    use App\Model\Warehouse;
    use Doctrine\DBAL\Connection;

    class UserRepository
    {
        /**
         * @var string
         */
        private $errorMessage;

        /**
         * @var Connection
         */
        private $dbConnection;

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
        }

        private function isUniqueEmail(string $email)
        {
            $dbEmail = $this->dbConnection->fetchAssoc(
                'SELECT email FROM users WHERE email = ?',
                [
                    $email
                ]
            );
            if (is_null($dbEmail['email'])){
                return true;
            } else {
                $this->errorMessage = 'A user with this email already exists.';
                return false;
            }
        }

        private function isCompanyExist(string $companyName)
        {
            $companyID = $this->dbConnection->fetchAssoc(
                'SELECT id FROM company WHERE name = ?',
                [
                    $companyName
                ]
            );
            if (is_null($companyID['id'])){
                $this->errorMessage = 'There is no company with that name.';
                return false;
            } else {
                return true;
            }
        }

        private function getPersonalInfoIfExist(EmployeeAbstract $newUser)
        {
            $personalInfoID = $this->dbConnection->fetchAssoc(
                'SELECT id FROM personalInfo WHERE name = ? AND lastname = ? AND phone = ?',
                [
                    $newUser->getName(),
                    $newUser->getLastname(),
                    $newUser->getPhone()
                ]
            );
            return $personalInfoID['id'];
        }

        private function insertIntoPersonalInfo(EmployeeAbstract $newUser)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO personalInfo (name, lastname, phone) VALUES (?, ?, ?)',
                [
                    $newUser->getName(),
                    $newUser->getLastname(),
                    $newUser->getPhone()
                ]
            );
        }

        private function insertIntoUsers(EmployeeAbstract $newUser, int $personalInfoID)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO users (email, password, id_company, id_personalData) VALUES (?, ?, ?, ?)',
                [
                    $newUser->getEmail(),
                    $newUser->getPassword(),
                    $newUser->getCompanyName(),
                    $personalInfoID
                ]
            );
        }

        public function registration(EmployeeAbstract $newUser)
        {
            if ($this->isUniqueEmail($newUser->getEmail()) && $this->isCompanyExist($newUser->getCompanyName())) {
                $personalInfoID = $this->getPersonalInfoIfExist($newUser);
                if (is_null($personalInfoID)) {
                    $this->insertIntoPersonalInfo($newUser);
                    $personalInfoID = $this->dbConnection->lastInsertId();
                }
                $this->insertIntoUsers($newUser, $personalInfoID);
                $newUser->setID($this->dbConnection->lastInsertId());
                return $newUser;
            } else {
                return $this->errorMessage;
            }
        }

        private function getUserInfo(string $email, string $password)
        {
            $userData = $this->dbConnection->fetchAssoc(
                'SELECT users.id, personalInfo.name, personalInfo.lastname, company.name AS companyName, users.email, users.password, personalInfo.phone
                FROM users
                INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.email = ? AND users.password = ?
                INNER JOIN company ON users.id_company = company.id;',
                [
                    $email,
                    $password
                ]
            );

            return $userData;
        }

        private function getItems(string $address)
        {
            $items = [];
            $rows = $this->dbConnection->executeQuery(
                    'SELECT items.id, price, items.name, type, size, quantity FROM items, quantity 
                    WHERE quantity.address = ? AND items.id = quantity.id_item',
                    [
                        $address
                    ]
                );
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $items[] = new Item($row);
            }

            return $items;
        }

        private function getWarehouses(array $warehousesID)
        {
            $warehouses = array();
            foreach ($warehousesID as $whID) {
                $row = $this->dbConnection->fetchAssoc(
                    'SELECT id, infoWarehouses.name, addresses.address, infoWarehouses.capacity 
                        FROM addresses, infoWarehouses
                        WHERE id = ? AND addresses.address = infoWarehouses.address',
                    [
                        $whID
                    ]
                );
                $warehouse = new Warehouse($row);
                $items = $this->getItems($warehouse->getAddress());
                foreach ($items as $item){
                    $warehouse->addItem($item);
                }
                array_push($warehouses, $warehouse);
            }
            return $warehouses;
        }

        private function giveWarehouses(Employee &$employee)
        {
            $warehousesID = $this->dbConnection->fetchAssoc(
                'SELECT id_address FROM userAccessible WHERE id_user = ?',//не много ли условий?//!!! тут услови вообщедурушле, тебе надо адреса получить (точно адреса?)
                [
                    $employee->getID()
                ]
            );

            $warehouses = $this->getWarehouses($warehousesID);

            foreach ($warehouses as $wh) {
                $employee->addWarehouse($wh);
            }
        }


        private function getUserInfoByID()
        {
            if (isset($_SESSION['user_id'])){
                $userData = $this->dbConnection->fetchAssoc(
                    'SELECT users.id, personalInfo.name, personalInfo.lastname, company.name AS companyName, users.email, users.password, personalInfo.phone
                    FROM users
                    INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.id = ?
                    INNER JOIN company ON users.id_company = company.id;',
                    [
                        $_SESSION['user_id']
                    ]
                );
                return $userData;
            } else {
                $this->errorMessage = 'User not found';
                return null;
            }

        }

        private function getRegisteredUser(string $email = null, string $password = null)
        {
            if (is_null($email)) {
                $userData = $this->getUserInfoByID();
            } else {
                $userData = $this->getUserInfo($email, $password);
            }

            if (isset($userData['id'])) {
                return new Employee($userData);
            } else {
                $this->errorMessage = 'User not found';
                return null;
            }
        }

        public function authentication(array $authenticationData)
        {
            $user = $this->getRegisteredUser($authenticationData['email'], $authenticationData['password']);
            if (is_null($user)) {
                return $this->errorMessage;
            } else {
                $this->giveWarehouses($user);
                $_SESSION['user_id'] = $user->getID();
                //return $user->warehousesList();
                return 'Hello, '.$user->getName().'!';
            }
        }
    }