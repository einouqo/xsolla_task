<?php
    namespace App\Services;

    use App\Model\Employee;
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
         * @param array $data
         * @throws \Exception
         */
        private function baseValidation(array $data)
        {
            if (is_null($data['email']) || $data['email'] == '') {
                throw new \Exception('Email cannot be empty.', 403);
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Email are incorrect.', 403);
            }

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
            if (is_null($data['name']) || $data['name'] == '') {
                throw new \Exception('Name cannot be empty.', 403);
            } elseif (!ctype_alpha($data['name'])) {
                throw new \Exception('Name may consist letters only.', 403);
            }

            if (is_null($data['lastname']) || $data['lastname'] == '') {
                throw new \Exception('Last name cannot be empty.', 403);
            } elseif (!ctype_alpha($data['lastname'])) {
                throw new \Exception('Last name may consist letters only.', 403);
            }

            if (is_null($data['companyID']) || $data['companyID'] == '') {
                throw new \Exception('Company ID cannot be empty.', 403);
            }

            if (is_null($data['phone']) || $data['phone'] == '') {
                throw new \Exception('Phone cannot be empty.', 403);
            } elseif (!is_numeric($data['phone'])) {
                throw new \Exception('Phone may consist digits only.', 403);
            }

            if (is_null($data['position']) || $data['position'] == '') {
                throw new \Exception('Position cannot be empty. (0 - regular Employee, 1 - admin)', 403);
            } elseif (!is_numeric($data['position']) || $data['position'] > 2 || $data['position'] < 0) {
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
         * @return mixed
         * @throws \Exception
         */
        private function getUserIDFromCookie()
        {
            if (isset($_COOKIE['token'])) {
                $config = require __DIR__.'/../settings.php';
                return ((array)JWT::decode(
                    $_COOKIE['token'],
                    $config['jwt']['secret'],
                    array('HS256')
                ))['userID'];
            } else {
                throw new \Exception('You need to login.', 401);
            }
        }

        /**
         * @return Employee|\App\Model\EmployeeAdmin
         * @throws \Exception
         */
        private function getUser()
        {
            return $this->userRepository->getUserInfoByID(
                $this->getUserIDFromCookie()
            );
        }

        /**
         * @param array $data
         * @return string
         * @throws \Exception
         */
        public function authentication(array $data)
        {
            $user = isset($data['email'], $data['password']) ?
                $this->userRepository->getUserInfo($data['email'], $data['password']):
                $this->getUser();

            $config = require __DIR__.'/../settings.php';
            setcookie('token',
                JWT::encode(
                ['userID' => $user->getID(), 'exp' => (time() + 60 * 60 * 24)],
                $config['jwt']['secret']
                ),
                time() + 60 * 60 * 24,
                '/'
            );

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
         * @return string
         * @throws \Exception
         */
        public function delete()
        {
            $this->userRepository->deleteAccount($this->getUserIDFromCookie());
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
         * @param array $newData
         * @return string
         * @throws \Exception
         */
        public function change(array $newData)
        {
            $user = $this->getUser();

            $changeableData = array();
            foreach ($newData as $field => $data) {
                if (!is_null($data) && $data != '') {
                    $changeableData[$field] = $data;
                }
            }

            if (count($changeableData) == 0) {
                throw new \Exception('Nothing to change.', 400);
            }

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