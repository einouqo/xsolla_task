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
                'password' => $bodyParams['password']
            );

            $result = $this->userService->registration($newUserData);
            return gettype($result) !== 'string' ?
                $response->withJson(
                    [
                        'name' => $result->getName(),
                        'company' => $result->getCompanyID()
                    ],
                    200
                ):
                $response->withStatus(406)->write($result);
        }
    }