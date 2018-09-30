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
         * @throws \Doctrine\DBAL\DBALException
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
                \DateTime::createFromFormat('Y-m-d H:i:s', $date.''.($request->getQueryParam('time') ?? '00:00:00')) :
                null;
            if (gettype($onDate) == 'boolean') {
                throw new \Exception('Date format are wrong. Date should be like: "year-month-day" [optional: time "hours:minutes:seconds"]', 403);
            }
            return $response->withStatus(200)->withJson(
                $this->warehouseService->getOne(
                    $request->getAttribute('user'),
                    $args['id'],
                    $onDate
                )
            );
        }
    }