<?php
    use PHPUnit\Framework\TestCase;

    class WarehouseTest extends TestCase
    {
        public $warehouseData;

        public function __construct(?string $name = null, array $data = [], string $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            $this->warehouseData = [
                'id' => 1,
                'address' => 'addressTest',
                'name' => 'testWarehouse',
                'capacity' => 100
            ];
        }

        /**
         * @dataProvider dataAddItems
         */
        public function testAddItem($warehouseData, $itemData, $expectingItem)
        {
            $warehouse = new \App\Model\Warehouse($warehouseData);
            $warehouse->addItem(new \App\Model\Item($itemData));
            $this->assertEquals($warehouse->getItemByID($itemData['id']), [$expectingItem]);
        }

        public function dataAddItems()
        {
            $item1Data = [
                'id' => 1,
                'name' => 'test1Item',
                'type' => 'test',
                'size' => '1',
                'price' => 1000,
                'quantity' => 10
            ];
            $item2Data = [
                'id' => 2,
                'name' => 'test2Item',
                'type' => 'test',
                'size' => '1',
                'price' => 500,
                'quantity' => 10
            ];
            $item3Data = [
                'id' => 2,
                'name' => 'test2Item',
                'type' => 'test',
                'size' => '1',
                'price' => 500,
                'quantity' => 5
            ];
            return [
                [
                    $this->warehouseData,
                    $item1Data,
                    new \App\Model\Item($item1Data)
                ],
                [
                    $this->warehouseData,
                    $item2Data,
                    new \App\Model\Item($item2Data)
                ],
                [
                    $this->warehouseData,
                    $item3Data,
                    new \App\Model\Item($item3Data)
                ]
            ];
        }


        /**
         * @dataProvider dataAddItemsIfExist
         */
        public function testAddItemIfExist($warehouseData, $itemDataExist, $itemsDataAdding, $expectingItemExist, $expectingItemNew)
        {
            $warehouse = new \App\Model\Warehouse($warehouseData);
            $warehouse->addItem(new \App\Model\Item($itemDataExist));
            $warehouse->addItem(new \App\Model\Item($itemsDataAdding));
            $this->assertEquals($warehouse->getItemByID($itemDataExist['id']), [$expectingItemExist]);
            $this->assertEquals($warehouse->getItemByID($itemsDataAdding['id']), [$expectingItemNew]);
        }

        public function dataAddItemsIfExist()
        {
            $itemExist = [
                'id' => 1,
                'name' => 'test1Item',
                'type' => 'test',
                'size' => '1',
                'price' => 1000,
                'quantity' => 10
            ];
            $itemAdding = [
                'id' => 1,
                'name' => 'test1Item',
                'type' => 'test',
                'size' => '1',
                'price' => 1000,
                'quantity' => 5
            ];
            $itemExpect = [
                'id' => 1,
                'name' => 'test1Item',
                'type' => 'test',
                'size' => '1',
                'price' => 1000,
                'quantity' => 15
            ];
            return [
                [
                    $this->warehouseData,
                    $itemExist,
                    $itemAdding,
                    new \App\Model\Item($itemExpect),
                    new \App\Model\Item($itemExpect)
                ]
            ];
        }
    }
