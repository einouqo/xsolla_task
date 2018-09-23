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

        public function giveAccess(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody();
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

        public function deleteAccess(Request $request, Response $response, $args = [])
        {
            $bodyParams = $request->getParsedBody();
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

        public function createWarehouse(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody();
            $bodyParams = is_null($parsedBody) ?
                array():
                $parsedBody;
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


        public function changeWarehouse(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody();
            $bodyParams = is_null($parsedBody) ?
                array():
                $parsedBody;
            $warehouseData = array (
                'warehouseID' => $args['id'],
                'name' => array_key_exists('name', $bodyParams) ?
                    $bodyParams['name'] :
                    null,
                'capacity' => array_key_exists('capacity', $bodyParams) ?
                    $bodyParams['capacity'] :
                    null
            );
            return $response->withStatus(200)->write($this->adminService->changeWarehouse($warehouseData));
        }

        public function deleteWarehouse(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->adminService->deleteWarehouse($args['id']));
        }

        public function addRoom(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody();
            $bodyParams = is_null($parsedBody) ?
                array():
                $parsedBody;
            $roomData = array (
                'address' => array_key_exists('address', $bodyParams) ?
                    $bodyParams['address'] :
                    null
            );
            return $response->withStatus(200)->write($this->adminService->addRoom($roomData));
        }

        public function deleteRoom(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody();
            $roomID = !is_null($parsedBody['roomID']) ?
                $parsedBody['roomID'] :
                null;

            return $response->withStatus(200)->write($this->adminService->deleteRoom($roomID));
        }

        public function getListRooms(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->withJson($this->adminService->getRooms());
        }

        public function getTransfersForWarehouse(Request $request, Response $response, $args = [])
        {
            $result = $this->adminService->getTransfersForWarehouse($args['id']);
            return count($result) == 0 ?
                $response->withStatus(406)->write('Transfers was not found.'):
                $response->withStatus(200)->withJson($result);
        }

        public function getTransfersForItem(Request $request, Response $response, $args = [])
        {
            $result = $this->adminService->getTransfersForItem($args['id']);
            return count($result) == 0 ?
                $response->withStatus(406)->write('Transfers was not found.'):
                $response->withStatus(200)->withJson($result);
        }

//        public function itemState(Request $request, Response $response, $args = [])
//        {
//            $result = $this->adminService->itemState($args['id']);
//            return count($result) == 0 ?
//                $response->withStatus(406)->write('Transfers was not found.'):
//                $response->withStatus(200)->withJson($result);
//        }
    }