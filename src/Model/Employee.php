<?php
    namespace App\Model;

    class Employee extends EmployeeAbstract
    {
        public function __construct(array $data)
        {
            parent::__construct($data);
        }

        private function warehouseItems(int $warehouseID)
        {
            foreach ($this->warehouses as $warehouse) {
                if ($warehouse->getID() == $warehouseID) {
                    return $warehouse->getItemsInfo();
                }
            }
            return null;
        }

        private function addItemToInfo(array &$itemsInfo, Item $item)
        {
            foreach ($itemsInfo as $key => $itemInfo) {
                if ($itemInfo['id'] == $item->getID() && $itemInfo['size'] == $item->getSize()) {
                    $itemsInfo[$key]['quantity'] += $item->getQuantity();
                    return;
                }
            }
            array_push($itemsInfo, $item->infoToArray());
        }

        private function allItems()
        {
            $result = array(
                'items' => array()
            );
            $totalPrice = 0.;
            $totalQuantity = 0;
            foreach ($this->warehouses as $warehouse) {
                $items = $warehouse->getItems();
                foreach ($items as $item) {
                    $this->addItemToInfo($result['items'], $item);
                    $totalPrice +=$item->getTotalPrice();
                }
                $totalQuantity += $warehouse->getLoaded();
            }
            $result += [
                'Report' => array(
                'Total price: ' => $totalPrice,
                'Total quantity: ' => $totalQuantity
                )
            ];

            return $result;
        }

        public function getItemList(int $warehouseID = null)
        {
            return is_null($warehouseID) ?
                $this->allItems() :
                $this->warehouseItems($warehouseID);
        }
    }