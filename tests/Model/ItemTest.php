<?php
    namespace App\Tests\Model;

    use App\Model\Item;
    use PHPUnit\Framework\TestCase;

    class ItemTest extends TestCase
    {
        public $itemData;

        public function __construct(?string $name = null, array $data = [], string $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            $this->itemData = [
                'id' => 1,
                'name' => 'test1Item',
                'type' => 'test',
                'size' => '1',
                'price' => 1000,
                'quantity' => 100
            ];
        }

        /**
         * @dataProvider dataGetTotalPrice
         */
        public function testGetTotalPrice($itemData, $expectedPrice)
        {
            $item = new Item($itemData);
            $this->assertEquals($item->getTotalPrice(), $expectedPrice);
        }

        public function dataGetTotalPrice()
        {
            return [
                [
                    $this->itemData,
                    100000
                ]
            ];
        }

        /**
         * @dataProvider dataInfoToArray
         */
        public function testInfoToArray($itemData)
        {
            $item = new Item($itemData);
            $this->assertEquals($item->infoToArray(), $itemData);
        }

        public function dataInfoToArray()
        {
            return [
                [
                    $this->itemData
                ]
            ];
        }
    }