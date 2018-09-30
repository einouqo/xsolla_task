<?php
    namespace App\Tests\Model;

    use PHPUnit\Framework\TestCase;

    class TransferTest extends TestCase
    {
        public $transferData;

        public function __construct(?string $name = null, array $data = [], string $dataName = '')
        {
            parent::__construct($name, $data, $dataName);
            $this->transferData = [
                'id' => 1,
                'warehouseFromID' => 1,
                'warehouseToID' => 2,
                'dateDeparture' => 1990-12-12,
                'dateReceiving' => null,
                'items' => []
            ];
        }

        /**
         * @dataProvider dataInfoToArray
         */
        public function testInfoToArray($itemData, $expectedPrice)
        {
            $item = new Item($itemData);
            $this->assertEquals($item->getTotalPrice(), $expectedPrice);
        }

        public function dataInfoToArray()
        {
            return [
                [
                    
                ]
            ];
        }
    }