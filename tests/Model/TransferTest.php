<?php
    namespace App\Tests\Model;

    use App\Model\Item;
    use App\Model\Transfer;
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
        public function testInfoToArray($transferData, $itemData1, $itemData2, $expected)
        {
            $transfer = new Transfer($transferData);
            $item = new Item($itemData1);
            $transfer->addItem($item);
            $item = new Item($itemData2);
            $transfer->addItem($item);
            $this->assertEquals($transfer->infoToArray(), $expected);
        }

        public function dataInfoToArray()
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
            return [
                [
                    $this->transferData,
                    $item1Data,
                    $item2Data,
                    [
                        'id' => 1,
                        'warehouseFromID' => 1,
                        'warehouseToID' => 2,
                        'dateDeparture' => 1990-12-12,
                        'dateReceiving' => null,
                        'items' => [
                            [
                                'id' => 1,
                                'name' => 'test1Item',
                                'type' => 'test',
                                'size' => '1',
                                'price' => 1000,
                                'quantity' => 10
                            ],
                            [
                                'id' => 2,
                                'name' => 'test2Item',
                                'type' => 'test',
                                'size' => '1',
                                'price' => 500,
                                'quantity' => 10
                            ]
                        ]
                    ]
                ]
            ];
        }
    }