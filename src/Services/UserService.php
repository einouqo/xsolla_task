<?php
    namespace App\Services;

    use App\Model\EmployeeAbstract;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;

    class UserService
    {
        /**
         * @var UserRepository
         */
        private $userRepository;

        public function __construct(UserRepository $userRepository)
        {
            $this->userRepository = $userRepository;
        }

        /**
         * @param $email
         * @throws \Exception
         */
        private function emailValidation($email)
        {
            if (is_null($email) || $email == '') {
                throw new \Exception('Email cannot be empty.', 403);
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email are incorrect.', 403);
            }
        }

        /**
         * @param $phone
         * @throws \Exception
         */
        private function phoneValidation($phone)
        {
            if (is_null($phone) || $phone == '') {
                throw new \Exception('Phone cannot be empty.', 403);
            } elseif (!ctype_digit($phone)) {
                throw new \Exception('Phone may consist digits only.', 403);
            } elseif (strlen($phone) > 11) {
                throw new \Exception('Phone cannot be more than 11 digits.', 403);
            }
        }

        /**
         * @param $name
         * @throws \Exception
         */
        private function nameValidation($name)
        {
            if (is_null($name) || $name == '') {
                throw new \Exception('Name and Last name cannot be empty.', 403);
            } elseif (!ctype_alpha($name)) {
                throw new \Exception('Name and Last name may consist letters only.', 403);
            }
        }

        /**
         * @param array $data
         * @throws \Exception
         */
        private function baseValidation(array $data)
        {
            $this->emailValidation($data['email']);

            if (is_null($data['password']) || $data['password'] == '') {
                throw new \Exception('Password cannot be empty.', 403);
            }
        }

        /**
         * @param array $data
         * @param string $exceptID
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        private function fullValidation(array $data, $exceptID = '')
        {
            $this->nameValidation($data['name']);
            $this->nameValidation($data['lastname']);

            if (is_null($data['companyID']) || $data['companyID'] == '') {
                throw new \Exception('Company ID cannot be empty.', 403);
            } elseif (!ctype_digit($data['companyID'])) {
                throw new \Exception('Company ID may consist digits only.', 403);
            }

            $this->phoneValidation($data['phone']);

            if (is_null($data['position']) || $data['position'] == '') {
                throw new \Exception('Position cannot be empty. (0 - regular Employee, 1 - admin)', 403);
            } elseif (!ctype_digit($data['position']) || $data['position'] > 2 || $data['position'] < 0) {
                throw new \Exception('Position value are wrong.', 403);
            }

            $this->baseValidation($data);

            $this->dataValidation($data, $exceptID);
        }


        /**
         * @param array $data
         * @param string $exceptID
         * @throws \Doctrine\DBAL\DBALException
         */
        private function dataValidation(array $data, $exceptID = '')
        {
            $this->userRepository->isUniqueEmail($data['email'], $exceptID);
            $this->userRepository->isUniquePhone($data['phone'], $exceptID);
            $this->userRepository->isCompanyExist($data['companyID']);
            $this->userRepository->isUniqueCompanyEmployee($data['companyID'], $data['name'], $data['lastname'], $exceptID);
        }

        /**
         * @param int $length
         * @return string
         * @throws \Exception
         */
        private function salt(int $length = 8)
        {
            $salt = '';
            while (($len = strlen($salt)) < $length) {
                $size = $length - $len;
                $bytes = random_bytes($size);
                $salt .= substr(str_replace(['/', '+', '='], '', base64_encode($bytes)), 0, $size);
            }
            return $salt;
        }


        /**
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function registration(array $data)
        {
            $this->fullValidation($data);
            $salt = $this->salt();
            $personalInfoID = $this->userRepository->getPersonalInfoIfExist($data);
            if (is_null($personalInfoID)) {
                $this->userRepository->insertIntoPersonalInfo($data);
                $personalInfoID = $this->userRepository->lastInsertId();
            }
            $this->userRepository->insertIntoUsers($data, $personalInfoID, $salt);
            if (key_exists('token', $_COOKIE)) {
                $this->unsetCookies();
            }
            return 'You have registered successfully.';
        }

        /**
         * @param int $userID
         */
        private function setTokenCookie(int $userID)
        {
            $config = require __DIR__.'/../settings.php';
            setcookie('token',
                JWT::encode(
                    ['userID' => $userID, 'exp' => (time() + 60 * 60 * 24)],
                    $config['jwt']['secret']
                ),
                time() + 60 * 60 * 24,
                '/'
            );
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         */
        public function login(array $data)
        {
            $this->unsetCookies();
            if (!isset($data['email'], $data['password'])) {
                throw new \Exception('You need to login using email and password.', 401);
            }

            $this->baseValidation($data);
            $user = $this->userRepository->getUserInfo($data['email'], $data['password']);

            $this->setTokenCookie($user->getID());

            return 'Hello, '.$user->getName().'!';
        }

        private function unsetCookies()
        {
            if (isset($_SERVER['HTTP_COOKIE'])) {
                $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    setcookie($name, '', time() - 60 * 60, '/');
                }
            }
        }

        /**
         * @return string
         */
        public function logoff()
        {
            if (isset($_COOKIE['token'])){
                $this->unsetCookies();
            }
            return 'Bye-Bye';
        }

        /**
         * @param EmployeeAbstract $user
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function delete(EmployeeAbstract $user)
        {
            $this->userRepository->deleteAccount($user->getID());
            $this->unsetCookies();
            return 'Your account was deleted.';
        }

        /**
         * @param array $oldData
         * @param array $newData
         * @throws \Exception
         */
        private function isChangeable(array $oldData, array $newData)
        {
            foreach ($newData as $field => $data) {
                if ($oldData[$field] == $data) {
                    throw new \Exception('The '.$field.' value can not be the same as the old one, please try again.', 400);
                }
            }

            if (key_exists('email', $newData) && !filter_var($newData['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('New email are incorrect.', 403);
            }

            $this->dataValidation(
                [
                    'email' => key_exists('email', $newData) ?
                        $newData['email']:
                        $oldData['email'],
                    'phone' => key_exists('phone', $newData) ?
                        $newData['phone']:
                        $oldData['phone'],
                    'name' => key_exists('name', $newData) ?
                        $newData['name']:
                        $oldData['name'],
                    'lastname' => key_exists('lastname', $newData) ?
                        $newData['lastname']:
                        $oldData['lastname'],
                    'companyID' => $oldData['companyID']
                ],
                $oldData['id']
            );
        }

        /**
         * @param array $data
         * @return array
         * @throws \Exception
         */
        private function changeValidation(array $data){
            $changeableData = [];
            foreach ($data as $field => $value) {
                if (!is_null($value) && $value != '') {
                    $changeableData[$field] = $value;
                }
            }

            if (count($changeableData) == 0) {
                throw new \Exception('Nothing to change.', 400);
            }

            if (isset($changeableData['email'])) {
                $this->emailValidation($changeableData['email']);
            }

            if (isset($changeableData['phone'])) {
                $this->phoneValidation($changeableData['phone']);
            }

            if (isset($changeableData['name'])) {
                $this->nameValidation($changeableData['name']);
            }

            if (isset($changeableData['lastname'])) {
                $this->nameValidation($changeableData['lastname']);
            }

            return $changeableData;
        }

        /**
         * @param EmployeeAbstract $user
         * @param array $data
         * @return string
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function change(EmployeeAbstract $user, array $data)
        {
            $changeableData = $this->changeValidation($data);
            $this->isChangeable(
                $user->getPersonalInfo() + [
                    'id' => $user->getID(),
                    'companyID' => $user->getCompanyID()
                ],
                $changeableData);

            $this->userRepository->change($user, $changeableData);
            $this->unsetCookies();
            return 'Your data was successfully update.';
        }
    }