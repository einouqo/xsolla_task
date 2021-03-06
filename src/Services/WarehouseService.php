<?php
    namespace App\Services;

    use App\Model\EmployeeAbstract;
    use App\Model\EmployeeAdmin;
    use App\Repository\WarehouseRepository;

    class WarehouseService
    {
        /**
         * @var WarehouseRepository
         */
        private $warehouseRepository;

        public function __construct(WarehouseRepository $warehouseRepository)
        {
            $this->warehouseRepository = $warehouseRepository;
        }

        /**
         * @param EmployeeAbstract $user
         * @throws \Doctrine\DBAL\DBALException
         */
        private function fillWarehouses(EmployeeAbstract &$user)
        {
            $user instanceof EmployeeAdmin ?
                $this->warehouseRepository->fillWarehousesWithItems($user, true):
                $this->warehouseRepository->fillWarehousesForEmployee($user);
        }

        /**
         * @param EmployeeAbstract $user
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getList(EmployeeAbstract $user)
        {
            $this->fillWarehouses($user);
            return $user->getWarehousesList();
        }

        /**
         * @param array $report
         * @param \DateTime $date
         * @return array
         * @throws \Doctrine\DBAL\DBALException
         */
        private function getReport(array $report, \DateTime $date)
        {
            if (is_null($date)) {
                return $report;
            }

            $totalQuantity = 0;
            $totalPrice = 0.;
            foreach ($report['items'] as $key => $item) {
                $report['items'][$key]['quantity'] +=
                    ($this->warehouseRepository->getSellingCondition($item['id'], $report['id'], $item['size'], $date) ?? 0) -
                    ($this->warehouseRepository->getDeliveryCondition($item['id'], $report['address'], $item['size'], $date) ?? 0) +
                    ($this->warehouseRepository->getSendedCondition($item['id'], $report['id'], $item['size'], $date) ?? 0) -
                    ($this->warehouseRepository->getReceivingCondition($item['id'], $report['id'], $item['size'], $date) ?? 0);
                $totalQuantity += $report['items'][$key]['quantity'];
                $totalPrice += $report['items'][$key]['quantity'] * $report['items'][$key]['price'];
                if ($report['items'][$key]['quantity'] == 0) {
                    unset($report['items'][$key]);
                }
            }
            $report['Total price: '] = $totalPrice;
            $report['loaded'] = $totalQuantity;

            return $report;
        }

        /**
         * @param EmployeeAbstract $user
         * @param int $warehouseID
         * @param \DateTime|null $date
         * @return array
         * @throws \Exception
         * @throws \Doctrine\DBAL\DBALException
         */
        public function getOne(EmployeeAbstract $user, $warehouseID, \DateTime $date = null)
        {
            $this->fillWarehouses($user);
            $warehouse = $user->getWarehouseByID($warehouseID);
            if (is_null($warehouse)) {
                if ($user instanceof EmployeeAdmin) {
                    throw new \Exception('This warehouse wasn\'t found in your organisation.', 400);
                } else {
                    throw new \Exception('You don\'t have access to this warehouse.', 400);
                }
            }

            return is_null($date) ?
                $warehouse->getFullInfo():
                $this->getReport($warehouse->getFullInfo(), $date);
        }
    }