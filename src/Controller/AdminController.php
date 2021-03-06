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
            return $response->withStatus(200)->write(
                $this->adminService->giveAccess(
                    $request->getAttribute('user'),
                    $accessData
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->deleteAccess(
                    $request->getAttribute('user'),
                    $accessData
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->createWarehouse(
                    $request->getAttribute('user'),
                    $warehouseData
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->changeWarehouse(
                    $request->getAttribute('user'),
                    $warehouseID,
                    $warehouseData
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->deleteWarehouse(
                    $request->getAttribute('user'),
                    $args['id']
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->addRoom(
                    $request->getAttribute('user'),
                    $roomData
                )
            );
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
            return $response->withStatus(200)->write(
                $this->adminService->deleteRoom(
                    $request->getAttribute('user'),
                    $args['id']
                )
            );
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
            $result = $this->adminService->getRooms($request->getAttribute('user'));
            return count($result) == 0 ?
                $response->withStatus(406)->write('Rooms were not found.'):
                $response->withStatus(200)->withJson($result);
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
            $result = $this->adminService->getTransfersForWarehouse(
                $request->getAttribute('user'),
                $args['id']
            );
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
            $result = $this->adminService->getTransfersForItem(
                $request->getAttribute('user'),
                $args['id']
            );
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

            return $response->withStatus(200)->write(
                $this->adminService->addItem(
                    $request->getAttribute('user'),
                    $data,
                    $warehouseID
                )
            );
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

            return $response->withStatus(200)->write(
                $this->adminService->changeItem(
                    $request->getAttribute('user'),
                    $itemID,
                    $data
                )
            );
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
                \DateTime::createFromFormat('Y-m-d H:i:s', $date.''.($request->getQueryParam('time') ?? '00:00:00')) :
                null;
            if (gettype($onDate) == 'boolean') {
                throw new \Exception('Date format are wrong. Date should be like: "year-month-day" [optional: time "hours:minutes:seconds"]', 403);
            }
            $result = $this->adminService->itemState(
                $request->getAttribute('user'),
                $args['id'],
                $onDate
            );
            return count($result['warehouses']) == 0 ?
                $response->withStatus(406)->write('Item was not found.'):
                $response->withStatus(200)->withJson($result);
        }
    }