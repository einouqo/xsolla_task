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
            $bodyParams = $request->getParsedBody() ?? [];
            $newUserData = array(
                'id' => null,
                'name' => key_exists('name', $bodyParams)?
                    $bodyParams['name']:
                    null,
                'lastname' => key_exists('lastname',$bodyParams) ?
                    $bodyParams['lastname']:
                    null,
                'companyID' => key_exists('companyID', $bodyParams) ?
                    $bodyParams['companyID']:
                    null,
                'email' => key_exists('email', $bodyParams) ?
                    $bodyParams['email']:
                    null,
                'phone' => key_exists('phone', $bodyParams) ?
                    $bodyParams['phone']:
                    null,
                'password' => key_exists('password', $bodyParams) ?
                    $bodyParams['password']:
                    null,
                'position' => key_exists('position', $bodyParams) ?
                    $bodyParams['position']:
                    null
            );

            return $response->write($this->userService->registration($newUserData));
        }
    }