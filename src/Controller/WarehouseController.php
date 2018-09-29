<?php
    namespace App\Controller;

    use App\Services\WarehouseService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class WarehouseController
    {
        /**
         * @var WarehouseService
         */
        private $warehouseService;

        public function __construct(WarehouseService $warehouseService)
        {
            $this->warehouseService = $warehouseService;
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         */
        public function getList(Request $request, Response $response, $args = [])
        {
            $result = $this->warehouseService->getList(
                $request->getAttribute('user')
            );
            return count($result) == 0?
                $response->withStatus(406)->write('Warehouses were not found.'):
                $response->withStatus(200)->withJson($result);
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Exception
         */
        public function getOne(Request $request, Response $response, $args = [])
        {
            $date = $request->getQueryParam('date');
            $onDate = isset($date) ?
                new \DateTime($date) :
                null;
            return $response->withStatus(200)->withJson(
                $this->warehouseService->getOne(
                    $request->getAttribute('user'),
                    $args['id'],
                    $onDate
                )
            );
        }
    }