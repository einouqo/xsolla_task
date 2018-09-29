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

        public function __construct(Connection $dbConnection)
        {
            $this->dbConnection = $dbConnection;
        }

        /**
         * @return string
         */
        public function lastInsertId()
        {
            return $this->dbConnection->lastInsertId();
        }

        /**
         * @param string $email
         * @param string $exceptID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function isUniqueEmail(string $email, string $exceptID)
        {
            $result = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM users WHERE email = ? AND id <> ?',
                [
                    $email,
                    $exceptID
                ]
            );
            if ($result['count'] != 0) {
                throw new \Exception('A user with this email already exists.', 409);
            }
        }

        /**
         * @param string $phone
         * @param string $exceptID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function isUniquePhone(string $phone, string $exceptID)
        {
            $dbPhone = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM personalInfo 
                    INNER JOIN users ON users.id_personalData = personalInfo.id AND phone = ? AND users.id <> ?;',
                [
                    $phone,
                    $exceptID
                ]
            );

            if ($dbPhone['count'] != 0) {
                throw new \Exception('A user with this phone already exists.', 409);
            }
        }

        /**
         * @param string $companyID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function isCompanyExist(string $companyID)
        {
            $companyID = $this->dbConnection->fetchAssoc(
                'SELECT id FROM company WHERE id = ?',
                [
                    $companyID
                ]
            );

            if (is_null($companyID['id'])) {
                throw new \Exception('There is no company with that name.', 409);
            }
        }


        /**
         * @param int $companyID
         * @param string $name
         * @param string $lastname
         * @param string $exceptID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function isUniqueCompanyEmployee(int $companyID, string $name, string $lastname, string $exceptID)
        {
            $rows = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) as count FROM users
                    INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND id_company = ? 
                    AND personalInfo.name = ? AND personalInfo.lastname = ? AND users.id <> ?',
                [
                    $companyID,
                    $name,
                    $lastname,
                    $exceptID
                ]
            );

            if ($rows['count'] != 0) {
                throw new \Exception('User with the same name and company is already exist.', 409);
            }
        }

        /**
         * @param array $data
         * @return mixed
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param array $data
         * @param int $personalInfoID
         * @param string $salt
         * @throws \Doctrine\DBAL\DBALException
         */
        public function insertIntoUsers(array $data, int $personalInfoID, string $salt)
        {
            $this->dbConnection->executeQuery(
                'INSERT INTO users (email, password, id_company, id_personalData, position, salt) VALUES (?, ?, ?, ?, ?, ?)',
                [
                    $data['email'],
                    password_hash($data['password'].$salt, PASSWORD_DEFAULT),
                    $data['companyID'],
                    $personalInfoID,
                    $data['position'],
                    $salt
                ]
            );
        }

        /**
         * @param string $email
         * @param string $password
         * @return Employee|EmployeeAdmin
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getUserInfo(string $email, string $password)
        {
            $isUserEmail = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM users WHERE email = ?',
                [
                    $email
                ]
            );

            if ($isUserEmail['count'] != 1) {
                throw new \Exception('User not found', 403);
            }

            $userData = $this->dbConnection->fetchAssoc(
                'SELECT users.id, personalInfo.name, personalInfo.lastname, users.id_company AS companyID, 
                users.email, users.password, users.position ,personalInfo.phone, salt FROM users
                INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.email = ?',
                [
                    $email,
                ]
            );

            if (isset($userData['id']) && password_verify($password.$userData['salt'], $userData['password'])) {
                return $userData['position'] == 1 ?
                    new EmployeeAdmin($userData):
                    new Employee($userData);
            } else {
                throw new \Exception('Password don\'t valid', 400);
            }
        }

        /**
         * @param int $userID
         * @return Employee|EmployeeAdmin
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getUserInfoByID(int $userID)
        {
            $userData = $this->dbConnection->fetchAssoc(
                'SELECT users.id, personalInfo.name, personalInfo.lastname, users.id_company AS companyID, users.email, users.password, users.position, personalInfo.phone
                FROM users
                INNER JOIN personalInfo ON users.id_personalData = personalInfo.id AND users.id = ?',
                [
                    $userID
                ]
            );

            if (isset($userData['id'])) {
                return $userData['position'] == 1 ?
                    new EmployeeAdmin($userData) :
                    new Employee($userData);
            } else {
                throw new \Exception('Your account don\'t valid anymore', 403);
            }
        }

        /**
         * @param int $personalDataID
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param int $id
         * @throws \Doctrine\DBAL\DBALException
         */
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

        /**
         * @param string $email
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function isNewEmailValid(string $email)
        {
            $result = $this->dbConnection->fetchAssoc(
                'SELECT COUNT(*) AS count FROM users WHERE email = ?',
                [
                    $email
                ]
            );

            if ($result['count'] != 0) {
                throw new \Exception('The email you entered is already taken.', 403);
            }
        }

        /**
         * @param EmployeeAbstract $employee
         * @param array $data
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function change(EmployeeAbstract $employee, array $data)
        {
            $passData = $this->dbConnection->fetchAssoc(
                'SELECT salt, password FROM users WHERE id = ?',
                [
                    $employee->getID()
                ]
            );

            if (password_verify($data['password'].$passData['salt'], $passData['password'])) {
                throw new \Exception('Password value cannot be same as the old one.', 403);
            }

            $this->dbConnection->executeQuery(
                'UPDATE users SET email = ?, password = ? WHERE id = ?',
                [
                    key_exists('email', $data) ?
                        $data['email']:
                        $employee->getEmail(),
                    key_exists('password', $data) ?
                        password_hash($data['password'].$passData['salt'], PASSWORD_DEFAULT):
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