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

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Exception
         */
        public function login(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $loginData = [
                'email' => key_exists('email', $bodyParams) ?
                    $bodyParams['email'] :
                    null,
                'password' => key_exists('password', $bodyParams) ?
                    $bodyParams['password'] :
                    null
            ];

            return $response->withStatus(200)->write($this->userService->login($loginData));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        public function logoff(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->userService->logoff());
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Exception
         */
        public function delete(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->userService->delete($request->getAttribute('user')));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Exception
         */
        public function change(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $data = [
                'email' => array_key_exists('email', $bodyParams) ?
                    $bodyParams['email'] :
                    null,
                'password' => array_key_exists('password', $bodyParams) ?
                    $bodyParams['password'] :
                    null,
                'name' => array_key_exists('name', $bodyParams) ?
                    $bodyParams['name'] :
                    null,
                'lastname' => array_key_exists('lastname', $bodyParams) ?
                    $bodyParams['lastname'] :
                    null,
                'phone' => array_key_exists('phone', $bodyParams) ?
                    $bodyParams['phone'] :
                    null
            ];

            return $response->write($this->userService->change($request->getAttribute('user'), $data));
        }
    }