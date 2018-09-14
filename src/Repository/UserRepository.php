<?php
    namespace App\Repository;

    use App\Model\Employee;
    use App\Model\EmployeeAbstract;
    use App\Model\EmployeeAdmin;
    use Doctrine\DBAL\Connection;


    class UserRepository
    {

        /**
         * @var Connection
         */
        private $dbConnection;

        /**
         * @var string
         */
        public $errorMessage;

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
        }

        public function lastInsertId()
        {
            return $this->dbConnection->lastInsertId();
        }

        public function isUniqueEmail(string $email)
        {
            $dbEmail = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM users WHERE email = ?',
                [
                    $email
                ]
            );
            if ($dbEmail['count'] == 0){
                return true;
            } else {
                $this->errorMessage = 'A user with this email already exists.';
                return false;
            }
        }

        public function isUniquePhone(string $phone)
        {
            $dbPhone = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM personalInfo WHERE phone = ?',
                [
                    $phone
                ]
            );
            if ($dbPhone['count'] == 0){
                return true;
            } else {
                $this->errorMessage = 'A user with this phone already exists.';
                return false;
            }
        }

        public function isCompanyExist(string $companyID)
        {
            $companyID = $this->dbConnection->fetchAssoc(
                'SELECT id FROM company WHERE id = ?',
                [
                    $companyID
                ]
            );
            if (is_null($companyID['id'])){
                $this->errorMessage = 'There is no company with that name.';
                return false;
            } else {
                return true;
            }
        }

        public function getPersonalInfoIfExist(array $data)
        {
            $personalInfoID = $this->dbConnection->fetchAssoc(
                'SELECT id FROM personalInfo WHERE name = ? AND lastname = ? AND phone = ?',
                [
                    $data['name'],
                    $data['lastname'],
                    $data['phone']
                ]
            );
            return $personalInfoID['id'];
        }

        public function insertIntoPersonalInfo(array $data)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO personalInfo (name, lastname, phone) VALUES (?, ?, ?)',
                [
                    $data['name'],
                    $data['lastname'],
                    $data['phone']
                ]
            );
        }

        public function insertIntoUsers(array $data, int $personalInfoID)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO users (email, password, id_company, id_personalData, admin) VALUES (?, ?, ?, ?, ?)',
                [
                    $data['email'],
                    $data['password'],
                    $data['companyID'],
                    $personalInfoID,
                    $data['admin']
                ]
            );
        }

        public function isUniqueCompanyEmployee(int $companyID, string $name, string $lastname)
        {
            $rows = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM users
                    INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND id_company = ? 
                    AND personalInfo.name = ? AND personalInfo.lastname = ?',
                [
                    $companyID,
                    $name,
                    $lastname
                ]
            );

            if ($rows['count'] == 0) {
                return true;
            } else {
                $this->errorMessage = 'User with the same name and company is already exist.';
                return false;
            }
        }

        public function getUserInfo(string $email, string $password)
        {
            $userEmail = $this->dbConnection->fetchAssoc(
                'SELECT email FROM users WHERE email = ?',
                [
                    $email
                ]
            );

            if (!isset($userEmail['email'])) {
                $this->errorMessage = 'User not found';
                return null;
            }

            $userData = $this->dbConnection->fetchAssoc(
                'SELECT users.id, personalInfo.name, personalInfo.lastname, users.id_company AS companyID, users.email, users.password, users.admin ,personalInfo.phone
                FROM users
                INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.email = ? AND users.password = ?',
                [
                    $email,
                    $password
                ]
            );

            if (isset($userData['id'])) {
                return $userData['admin'] == 1 ?
                    new EmployeeAdmin($userData):
                    new Employee($userData);
            } else {
                $this->errorMessage = 'Password don\'t valid';
                return null;
            }
        }

        public function getUserInfoByID(int $userID)
        {
            $userData = $this->dbConnection->fetchAssoc(
                'SELECT users.id, personalInfo.name, personalInfo.lastname, users.id_company AS companyID, users.email, users.password, users.admin, personalInfo.phone
                FROM users
                INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.id = ?',
                [
                    $userID
                ]
            );
            return $userData['admin'] == 1 ?
                new EmployeeAdmin($userData):
                new Employee($userData);
        }

        private function deletePersonalData(int $personalDataID)
        {
            $countAccounts = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM users WHERE id_personalData = ?',
                [
                    $personalDataID
                ]
            );

            if ($countAccounts['count'] == 0)
            {
                $this->dbConnection->executeQuery(
                    'DELETE FROM personalInfo WHERE id = ?',
                    [
                        $personalDataID
                    ]
                );
            }
        }

        public function deleteAccount(int $id)
        {
            $result = $this->dbConnection->fetchAssoc(
                'SELECT id_personalData FROM users WHERE id = ?',
                [
                    $id
                ]
            );

            $this->dbConnection->executeQuery(
                'DELETE FROM userAccessible WHERE id_user = ?',
                [
                    $id
                ]
            );

            $this->dbConnection->executeQuery(
                'DELETE FROM users WHERE id = ?',
                [
                    $id
                ]
            );

            $this->deletePersonalData($result['id_personalData']);
        }

        public function change(EmployeeAbstract $employee, array $data)
        {
            $this->dbConnection->executeQuery(
                'UPDATE users SET email = ?, password = ? WHERE id = ?',
                [
                    key_exists('email', $data) ?
                        $data['email']:
                        $employee->getEmail(),
                    key_exists('password', $data) ?
                        $data['password']:
                        $employee->getPassword(),
                    $employee->getID()
                ]
            );

            $this->dbConnection->executeQuery(
                'UPDATE personalInfo SET name = ?, lastname = ?, phone = ? WHERE id IN
                  (SELECT id_personalData FROM users WHERE id = ?)',
                [
                    key_exists('name', $data) ?
                        $data['name']:
                        $employee->getName(),
                    key_exists('lastname', $data) ?
                        $data['lastname']:
                        $employee->getLastname(),
                    key_exists('phone', $data) ?
                        $data['phone']:
                        $employee->getPhone(),
                    $employee->getID()
                ]
            );
        }
    }