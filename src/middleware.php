<?php
    namespace App;

    use App\Model\Employee;
    use App\Model\EmployeeAdmin;
    use App\Repository\UserRepository;
    use Firebase\JWT\JWT;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class Middleware
    {
        /**
         * @var UserRepository
         */
        private $userRepository;

        /**
         * @var string
         */
        private $secret;

        public function __construct(UserRepository $userRepository, string $secret)
        {
            $this->userRepository = $userRepository;
            $this->secret = $secret;
        }

        /**
         * @return mixed
         * @throws \Exception
         */
        private function getUserIDFromCookie()
        {
            if (isset($_COOKIE['token'])) {
                return ((array)JWT::decode(
                    $_COOKIE['token'],
                    $this->secret,
                    array('HS256')
                ))['userID'];
            } else {
                throw new \Exception('You need to login.', 401);
            }
        }

        /**
         * @return Employee|EmployeeAdmin
         * @throws \Exception
         */
        private function getUser()
        {
            return $this->userRepository->getUserInfoByID(
                $this->getUserIDFromCookie()
            );
        }

        public function __invoke(Request $request, Response $response, $next)
        {
            $user = $this->getUser();
            $request = $request->withAttribute('user', $user);
            $response = $next($request, $response);
            $response->getBody()->write('AFTER');

            return $response;
        }
    }