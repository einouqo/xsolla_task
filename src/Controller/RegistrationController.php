<?php
    namespace App\Controller;

    use App\Services\UserService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class RegistrationController
    {
        /**
         * @var UserService
         */
        private $userService;

        public function __construct(UserService $userService)
        {
            $this->userService = $userService;
        }

        public function registration(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody();
            $newUserData = array(
                'id' => null,
                'name' => $bodyParams['name'],
                'lastname' => $bodyParams['lastname'],
                'companyID' => $bodyParams['companyID'],
                'email' => $bodyParams['email'],
                'phone' => $bodyParams['phone'],
                'password' => md5($bodyParams['password']),
                'admin' => $bodyParams['admin']
            );

            return $response->write($this->userService->registration($newUserData));
        }
    }