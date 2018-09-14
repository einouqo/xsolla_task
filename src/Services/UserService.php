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

        private function dataValidation($data)
        {
            return (
                $this->userRepository->isUniqueEmail($data['email']) &&
                $this->userRepository->isUniquePhone($data['phone']) &&
                $this->userRepository->isCompanyExist($data['companyID']) &&
                $this->userRepository->isUniqueCompanyEmployee($data['companyID'], $data['name'], $data['lastname'])
            ) ?
                true:
                false;
        }

        public function registration(array $data)
        {
            if ($this->dataValidation($data)) {
                $personalInfoID = $this->userRepository->getPersonalInfoIfExist($data);
                if (is_null($personalInfoID)) {
                    $this->userRepository->insertIntoPersonalInfo($data);
                    $personalInfoID = $this->userRepository->lastInsertId();
                }
                $this->userRepository->insertIntoUsers($data, $personalInfoID);
                return 'You have registered successfully.';
            } else {
                return $this->userRepository->errorMessage;
            }
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
                $this->userRepository->errorMessage = 'You need to login.';
                return null;
            }
        }

        private function getUser()
        {
            $id = $this->getUserIDFromCookie();
            return is_null($id) ?
                null:
                $this->userRepository->getUserInfoByID($id);
        }

        public function authentication(array $data)
        {
            $user = isset($data['email'], $data['password']) ?
                $this->userRepository->getUserInfo($data['email'], $data['password']):
                $this->getUser();
            if (is_null($user)) {
                return $this->userRepository->errorMessage;
            } else {
                $config = require __DIR__.'/../settings.php';
                setcookie('token',
                    JWT::encode(
                    ['userID' => $user->getID(), 'exp' => (time() + 60 * 60 * 24)],
                    $config['jwt']['secret']
                    )
                );
                //$_SESSION['user_id'] = $user->getID();
                //return $user->warehousesList();
                return 'Hello, '.$user->getName().'!';
            }
        }

        private function unsetCookies()
        {
            if (isset($_SERVER['HTTP_COOKIE'])) {
                $cookies = explode(';', $_SERVER['HTTP_COOKIE']);
                foreach($cookies as $cookie) {
                    $parts = explode('=', $cookie);
                    $name = trim($parts[0]);
                    //setcookie($name, '', time() - 60 * 60);
                    setcookie($name, '', time() - 60 * 60, '/user');
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
            $id = $this->getUserIDFromCookie();
            if (!is_null($id)) {
                $this->userRepository->deleteAccount($id);
                $this->unsetCookies();
                return 'Your account was deleted.';
            } else {
                return $this->userRepository->errorMessage;
            }
        }

        public function change(array $newData)
        {
            $user = $this->getUser();
            if (is_null($user)) {
                return $this->userRepository->errorMessage;
            }

            $changeableData = array();
            foreach ($newData as $field => $data) {
                if (!is_null($data)) {
                    $changeableData[$field] = $data;
                }
            }

            if (count($changeableData) == 0) {
                return 'Nothing to change.';
            }

            $result = $user->isChangeable($changeableData);
            if (!is_null($result)) {
                return 'The '.$result.' value can not be the same as the old one, please try again.';
            }

            $this->userRepository->change($user, $changeableData);
            return 'Your data was successfully update.';
        }
    }