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
                'email' => array_key_exists('email', $bodyParams) ?
                    $bodyParams['email']:
                    null,
                'password' => array_key_exists('password', $bodyParams) ?
                    $bodyParams['password']:
                    null,
            );

            //return $response->withJson($this->userService->authentication($authenticationData));
            return $response->write($this->userService->authentication($authenticationData));
        }

        public function logoff(Request $request, Response $response, $args = [])
        {
            return $response->write($this->userService->logoff());
        }

        public function delete(Request $request, Response $response, $args = [])
        {
            return $response->write($this->userService->delete());
        }
    }