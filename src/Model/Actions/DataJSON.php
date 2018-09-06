<?php
    namespace App\Model\Actions;

    class DataJSON extends ActionAbstract
    {
        /**
         * @var string
         */
        private $path;

        /**
         * @var JSON
         */
        private $json;

        /**
         * decoded warehouses JSON
         * @var array
         */
        private $decoded;

        public function getCompanyData(\App\Model\Company $company)
        {
            $warehouses = $this->decoded['warehouses'];
            foreach ($warehouses as $key => $wh){
                $company->addWarehouse(new \App\Model\Warehouse($wh));
                foreach ($wh['items'] as $item){
                    $company->warehouses[$key]->addItem(new \App\Model\Item($item));
                }
            }
        }

        public function update(\App\Model\Company $company)
        {
            file_put_contents(__DIR__.'/../../../'.$this->pathToActReport, $this->actJson);
            $jsonUpd = \App\Model\Actions\GetInfo::getInfoForCompany($company);
            if ($this->json != $jsonUpd){
                file_put_contents(__DIR__.'/../../../'.$this->path, $jsonUpd);
            }
        }

        public function __construct(string $pathToJSON)
        {
            $this->path = $pathToJSON;
            $this->json = file_get_contents(__DIR__.'/../../../'.$pathToJSON);
            $this->decoded = json_decode($this->json, TRUE);
        }
    }