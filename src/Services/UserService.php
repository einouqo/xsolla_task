<?php
    namespace App\Services;

    use App\Model\Employee;
    use App\Repository\UserRepository;

    session_start();

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

        private function dataValidation($userData)
        {
            return ($this->userRepository->isUniqueEmail($userData['email']) && $this->userRepository->isCompanyExist($userData['companyID']) &&
                $this->userRepository->isUniqueCompanyEmployee($userData['companyID'], $userData['name'], $userData['lastname'])) ?
                true:
                false;
        }

        public function registration(array $newUserData)
        {
            if ($this->dataValidation($newUserData)) {
                $employee = new Employee($newUserData);
                $personalInfoID = $this->userRepository->getPersonalInfoIfExist($employee);
                if (is_null($personalInfoID)) {
                    $this->userRepository->insertIntoPersonalInfo($employee);
                    $personalInfoID = $this->userRepository->lastInsertId();
                }
                $this->userRepository->insertIntoUsers($employee, $personalInfoID);
                return 'You have registered successfully.';
            } else {
                return $this->userRepository->errorMessage;
            }
        }

        private function getUser()
        {
            if (isset($_SESSION['user_id'])) {
                return $this->userRepository->getUserInfoByID($_SESSION['user_id']);
            } else {
                $this->userRepository->errorMessage = 'You need to login.';
                return null;
            }
        }

        public function authentication(array $data)
        {
            $user = isset($data['email'], $data['password']) ?
                $this->userRepository->getUserInfo($data['email'], $data['password']):
                $this->getUser();
            if (is_null($user)) {
                return $this->userRepository->errorMessage;
            } else {
                $_SESSION['user_id'] = $user->getID();
                //return $user->warehousesList();
                return 'Hello, '.$user->getName().'!';
            }
        }

        public function logoff()
        {
            if (isset($_SESSION['user_id'])){
                session_destroy();
            }
            return 'Bye-Bye';
        }

        public function delete()
        {
            if (isset($_SESSION['user_id'])) {
                $this->userRepository->deleteAccount($_SESSION['user_id']);
                session_destroy();
                return 'Your account was deleted.';
            } else {
                return 'You need to login.';
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