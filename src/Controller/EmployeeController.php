<?php
    namespace App\Controller;

    use App\Services\EmployeeService;
    use Slim\Http\Request;
    use Slim\Http\Response;

    class EmployeeController
    {
        /**
         * @var EmployeeService
         */
        private $employeeService;

        public function __construct(EmployeeService $employeeService)
        {
            $this->employeeService = $employeeService;
        }

        public function pendingList(Request $request, Response $response, $args = [])
        {
            $result = $this->employeeService->getPendingList();
            return count($result) == 0 ?
                $response->withStatus(200)->write('Nothing is pending.'):
                $response->withStatus(200)->withJson($result);
        }

        public function availableList(Request $request, Response $response, $args = [])
        {
            $warehouseID = $request->getQueryParam('warehouseID');
            $result = $this->employeeService->getAvailableList($warehouseID);
            return count($result['items']) == 0 ?
                $response->withStatus(200)->write('Your warehouses are empty.'):
                $response->withStatus(200)->withJson($result);
        }

        public function takeTransfer(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody();
            $transferID = key_exists('transferID', $parsedBody) ?
                $parsedBody['transferID']:
                null;
            return isset($transferID) ?
                $response->withStatus(200)->write($this->employeeService->takeTransfer($transferID)):
                $response->withStatus(400)->write('Transfer id can not be empty.');
        }

        public function addToTransfer(Request $request, Response $response, $args = [])
        {
            $itemID = $args['id'];
            $parsedBody = $request->getParsedBody() ?? array();
            $data = array (
                'warehouseFromID' => key_exists('warehouseFromID', $parsedBody) ?
                    $parsedBody['warehouseFromID']:
                    null,
                'size' => key_exists('size', $parsedBody) ?
                    $parsedBody['size']:
                    null,
                'quantity'=> key_exists('quantity', $parsedBody) ?
                    $parsedBody['quantity']:
                    null
            );

            return isset($data['warehouseFromID'])?
                $response->withStatus(200)->write($this->employeeService->addToTransfer($itemID, $data)):
                $response->withStatus(400)->write('Warehouse ID of this item cannot be empty.');
        }

        public function clearTransfer(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->write($this->employeeService->clearTransfer());
        }

        public function showTransfer(Request $request, Response $response, $args = [])
        {
            return $response->withStatus(200)->withJson($this->employeeService->getTransferList());
        }

        public function sendTransfer(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? array();
            $warehouseToID = key_exists('warehouseToID', $parsedBody) ?
                $parsedBody['warehouseToID']:
                null;
            return is_null($warehouseToID) ?
                $response->withStatus(400)->write('Warehouse ID of this item cannot be empty.'):
                $response->withStatus(200)->write($this->employeeService->sendTransfer($warehouseToID));
        }

        public function sellItem(Request $request, Response $response, $args = [])
        {
            $parsedBody = $request->getParsedBody() ?? array();
            $data = array (
                'warehouseID' => key_exists('warehouseID', $parsedBody) ?
                    $parsedBody['warehouseID']:
                    null,
                'size' => key_exists('size', $parsedBody) ?
                    $parsedBody['size']:
                    null,
                'quantity'=> key_exists('quantity', $parsedBody) ?
                    $parsedBody['quantity']:
                    null
            );
            $itemID = $args['id'];
            return $response->withStatus(200)->write($this->employeeService->sellItem($itemID, $data));
        }
    }