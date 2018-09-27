<?php
    namespace App\Controller;

    use App\Services\AdminService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class AdminController
    {
        /**
         * @var AdminService
         */
        private $adminService;

        public function __construct(AdminService $adminService)
        {
            $this->adminService = $adminService;
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function giveAccess(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $accessData = array (
                'userID' => array_key_exists('userID', $bodyParams) ?
                    $bodyParams['userID'] :
                    null,
                'warehouseID' => array_key_exists('warehouseID', $bodyParams) ?
                    $bodyParams['warehouseID'] :
                    null
            );
            return $response->withStatus(200)->write($this->adminService->giveAccess($accessData));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteAccess(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $accessData = array (
                'userID' => array_key_exists('userID', $bodyParams) ?
                    $bodyParams['userID'] :
                    null,
                'warehouseID' => array_key_exists('warehouseID', $bodyParams) ?
                    $bodyParams['warehouseID'] :
                    null
            );
            return $response->withStatus(200)->write($this->adminService->deleteAccess($accessData));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function createWarehouse(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $warehouseData = array (
                'roomID' => array_key_exists('roomID', $bodyParams) ?
                    $bodyParams['roomID'] :
                    null,
                'name' => array_key_exists('name', $bodyParams) ?
                    $bodyParams['name'] :
                    null,
                'capacity' => array_key_exists('capacity', $bodyParams) ?
                    $bodyParams['capacity'] :
                    null
            );
            return $response->withStatus(200)->write($this->adminService->createWarehouse($warehouseData));
        }


        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeWarehouse(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $warehouseID = $args['id'];
            $warehouseData = [
                'name' => array_key_exists('name', $bodyParams) ?
                    $bodyParams['name'] :
                    null,
                'capacity' => array_key_exists('capacity', $bodyParams) ?
                    $bodyParams['capacity'] :
                    null
            ];
            return $response->withStatus(200)->write($this->adminService->changeWarehouse($warehouseID, $warehouseData));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteWarehouse(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->adminService->deleteWarehouse($args['id']));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addRoom(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody() ?? [];
            $roomData = [
                'address' => array_key_exists('address', $bodyParams) ?
                    $bodyParams['address'] :
                    null
            ];
            return $response->withStatus(200)->write($this->adminService->addRoom($roomData));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function deleteRoom(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->adminService->deleteRoom($args['id']));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getListRooms(Request $request, Response $response, $args = [])
        {
            $result = $this->adminService->getRooms();
            return count($result) == 0 ?
                $response->withStatus(406)->write('Rooms were not found.'):
                $response->withStatus(200)->withJson($this->adminService->getRooms());
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForWarehouse(Request $request, Response $response, $args = [])
        {
            $result = $this->adminService->getTransfersForWarehouse($args['id']);
            return count($result) == 0 ?
                $response->withStatus(406)->write('Transfers were not found.'):
                $response->withStatus(200)->withJson($result);
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getTransfersForItem(Request $request, Response $response, $args = [])
        {
            $result = $this->adminService->getTransfersForItem($args['id']);
            return count($result) == 0 ?
                $response->withStatus(406)->write('Transfers were not found.'):
                $response->withStatus(200)->withJson($result);
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function addItem(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? [];
            $data = [
                'name' => key_exists('name', $parsedBody) ?
                    $parsedBody['name']:
                    null,
                'type' => key_exists('type', $parsedBody) ?
                    $parsedBody['type']:
                    null,
                'size' => key_exists('size', $parsedBody) ?
                    $parsedBody['size']:
                    null,
                'price' => key_exists('price', $parsedBody) ?
                    $parsedBody['price']:
                    null,
                'quantity' => key_exists('quantity', $parsedBody) ?
                    $parsedBody['quantity']:
                    null
            ];
            $warehouseID = key_exists('warehouseID', $parsedBody) ?
                $parsedBody['warehouseID']:
                null;

            return $response->withStatus(200)->write($this->adminService->addItem($data, $warehouseID));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function changeItem(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? [];
            $data = [
                'name' => key_exists('name', $parsedBody) ?
                    $parsedBody['name']:
                    null,
                'type' => key_exists('type', $parsedBody) ?
                    $parsedBody['type']:
                    null,
                'price' => key_exists('price', $parsedBody) ?
                    $parsedBody['price']:
                    null
            ];
            $itemID = $args['id'];

            return $response->withStatus(200)->write($this->adminService->changeItem($itemID, $data));
        }

        /**
         * @param Request $request
         * @param Response $response
         * @param array $args
         * @return Response
         * @throws \Doctrine\DBAL\DBALException
         */
        public function itemState(Request $request, Response $response, $args = [])
        {
            $date = $request->getQueryParam('date');
            $onDate = isset($date) ?
                new \DateTime($date) :
                null;
            $result = $this->adminService->itemState($args['id'], $onDate);
            return count($result['warehouses']) == 0 ?
                $response->withStatus(406)->write('Item was not found.'):
                $response->withStatus(200)->withJson($result);
        }
    }