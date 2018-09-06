<?php
    namespace App\Model\Actions;

    abstract class ActionAbstract
    {

        /**
         * @var string
         */
        protected $pathToActReport;

        /**
         * @var JSON
         */
        protected $actJson;

        /**
         * secoded actions JSON
         * @var array
         */
        public $actDecoded;

        abstract public function getCompanyData(\App\Model\Company $company);
        abstract public function update(\App\Model\Company $company);

        public function actionsReport(\App\Model\Warehouse $from, \App\Model\Warehouse $to, int $id, int $quantity)
        {
            array_push($this->actDecoded, $actReport = array(
                'from' => $from->address,
                'to' => $to->address,
                'product' => array(
                    'id' => $id,
                    'quantity' => $quantity
                )
            ));
            $this->actJson = json_encode($this->actDecoded, JSON_UNESCAPED_UNICODE);
        }

        public function initializeActions(string $path)
        {
            $this->pathToActReport = $path;
            $this->actJson = file_get_contents(__DIR__.'/../../../'.$path);
            $this->actDecoded = json_decode($this->actJson, TRUE);
            if (is_null($this->actDecoded)){//?
                $this->actDecoded = array();
            }
        }
    }