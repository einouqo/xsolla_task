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

        public function getList(Request $request, Response $response, $args = [])
        {
            $result =$this->warehouseService->getList();
            return count($result) == 0?
                $response->withStatus(406)->write('Warehouses were not found.'):
                $response->withStatus(200)->withJson($this->warehouseService->getList());
        }

        public function getOne(Request $request, Response $response, $args = [])
        {
            $param = $request->getQueryParam('date');
            $date = is_null($param) ?
                null:
                new \DateTime($param);
            return $response->withStatus(200)->withJson($this->warehouseService->getOne($args['id'], $date));
        }
    }