<?php
    namespace App\Controller;

    use App\Services\CompanyService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class CompanyController
    {
        /**
         * @var CompanyService
         */
        private $companyService;

        public function __construct(CompanyService $companyService)
        {
            $this->companyService = $companyService;
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getList(Request $request, Response $response, $args = [])
        {
            $result = $this->companyService->getList();
            return count($result) == 0 ?
                $response->withStatus(406)->write('No company found.'):
                $response->withStatus(200)->withJson(
                    $result
                );
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function create(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? [];
            $name = key_exists('name', $parsedBody) ?
                $parsedBody['name'] :
                null;
            return is_null($name) || $name == '' ?
                $response->withStatus(403)->write('Company name cannot be empty.'):
                $response->withStatus(200)->write(
                    $this->companyService->create($name)
                );
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function delete(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? [];
            $data = [
                'key' => key_exists('key', $parsedBody) ?
                    $parsedBody['key'] :
                    null,
                'companyID' => $args['id']
            ];
            return is_null($data['key']) ?
                $response->withStatus(403)->write('You need to use key.'):
                $response->withStatus(200)->write(
                    $this->companyService->delete($data)
                );
        }
    }