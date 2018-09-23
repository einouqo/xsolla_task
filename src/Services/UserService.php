<?php
    namespace App\Services;

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

        private function dataValidation(array $data, string $exceptID = '')
        {
            $this->userRepository->isUniqueEmail($data['email'], $exceptID);
            $this->userRepository->isUniquePhone($data['phone'], $exceptID);
            $this->userRepository->isCompanyExist($data['companyID']);
            $this->userRepository->isUniqueCompanyEmployee($data['companyID'], $data['name'], $data['lastname'], $exceptID);
        }

        public function registration(array $data)
        {
            $this->dataValidation($data);
            $personalInfoID = $this->userRepository->getPersonalInfoIfExist($data);
            if (is_null($personalInfoID)) {
                $this->userRepository->insertIntoPersonalInfo($data);
                $personalInfoID = $this->userRepository->lastInsertId();
            }
            $this->userRepository->insertIntoUsers($data, $personalInfoID);
            return 'You have registered successfully.';
        }

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

        private function getUser()
        {
            return $this->userRepository->getUserInfoByID(
                $this->getUserIDFromCookie()
            );
        }

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

        public function logoff()
        {
            if (isset($_COOKIE['token'])){
                $this->unsetCookies();
            }
            return 'Bye-Bye';
        }

        public function delete()
        {
            $this->userRepository->deleteAccount($this->getUserIDFromCookie());
            $this->unsetCookies();
            return 'Your account was deleted.';
        }

        private function isChangeable(array $oldData, array $newData)
        {
            foreach ($newData as $field => $data) {
                if ($oldData[$field] == $data) {
                    throw new \Exception('The '.$field.' value can not be the same as the old one, please try again.', 400);
                }
            }

            $this->dataValidation(
                array(
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
                ),
                $oldData['id']
            );
        }

        public function change(array $newData)
        {
            $user = $this->getUser();

            $changeableData = array();
            foreach ($newData as $field => $data) {
                if (!is_null($data)) {
                    $changeableData[$field] = $data;
                }
            }

            if (count($changeableData) == 0) {
                throw new \Exception('Nothing to change.', 400);
            }

            $this->isChangeable($user->getPersonalInfo(), $changeableData);

            $this->userRepository->change($user, $changeableData);
            return 'Your data was successfully update.';
        }
    }