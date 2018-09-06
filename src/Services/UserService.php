<?php
    namespace App\Services;

    use App\Model\Employee;
    use App\Repository\UserRepository;

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

        public function registration(array $newUserData)
        {
            $user = new Employee($newUserData);
            return $this->userRepository->registration($user);
        }

        public function authentication(array $authenticationData)
        {
            return $this->userRepository->authentication($authenticationData);
        }
    }