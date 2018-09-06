<?php
    namespace App\Model\Actions\Interfaces;

    interface iGetInfo
    {
        public static function getInfoForItem(\App\Model\Item $item, bool $beautify);
        public static function getInfoForWaerhouse(\App\Model\Warehouse $waerhouse, bool $beautify);
        public static function getInfoForCompany(\App\Model\Company $company, bool $beautify);
    }