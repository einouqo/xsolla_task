<?php

    class CompanyTest extends \App\Tests\Functional\ApiTestCase
    {
        public function testGetList()
        {
            $this->request('GET', '/company/list');
            $this->assertThatResponseHasStatus(200);
            $this->assertCount(5, $this->responseData());
            $this->assertEquals(
                [
                    ['id' => 6, 'name' => 'OAO TEST'],
                    ['id' => 7, 'name' => 'OOO WareCo'],
                    ['id' => 8, 'name' => 'Testing inc.'],
                    ['id' => 9, 'name' => 'OOO YOLO'],
                    ['id' => 10, 'name' => 'test']
                ],
                $this->responseData()
            );
        }

        public function testCreateEmptyName()
        {
            $this->request(
                'POST',
                '/company/create',
                [
                    'name' => ''
                ]
            );
            $this->assertThatResponseHasStatus(403);
        }

        public function testCreate()
        {
            $this->request(
                'POST',
                '/company/create',
                [
                    'name' => 'test'
                ]
            );
            $this->assertThatResponseHasStatus(200);
            $this->request('GET', '/company/list');
            $this->assertThatResponseHasStatus(200);
            $this->assertCount(6, $this->responseData());
            $this->assertEquals(
                [
                    ['id' => 6, 'name' => 'OAO TEST'],
                    ['id' => 7, 'name' => 'OOO WareCo'],
                    ['id' => 8, 'name' => 'Testing inc.'],
                    ['id' => 9, 'name' => 'OOO YOLO'],
                    ['id' => 10, 'name' => 'test'],
                    ['id' => 16, 'name' => 'test']
                ],
                $this->responseData()
            );
        }
    }