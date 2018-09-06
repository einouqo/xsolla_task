<?php
    namespace App\Controller;

    use App\Services\UserService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class UserController
    {
        /**
         * @var UserService
         */
        private $userService;

        public function __construct(UserService $userService)
        {
            $this->userService = $userService;
        }

        public function authentication(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody();
            $authenticationData = array (
                'email' => $bodyParams['email'],
                'password' => $bodyParams['password']
            );

            $result = $this->userService->authentication($authenticationData);
        }
    }