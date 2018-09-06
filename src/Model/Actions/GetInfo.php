<?php
    namespace App\Model\Actions;

    class GetInfo implements Interfaces\iGetInfo
    {
        private static function beautifyJson(array $info)
        {
            $output = json_encode($info, JSON_UNESCAPED_UNICODE);
            $patterns = array('/\[/', '/]},{/', '/]/', '/},{/');
            $replace = array('[<br>', ']},<br>{', '<br>]<br>', '},<br>{');
            return preg_replace($patterns, $replace, $output);
        }

        public static function beautifyJsonActReport(array $info)
        {
            $output = json_encode($info, JSON_UNESCAPED_UNICODE);
            $patterns = array('/{"f/', '/,"t/', '/,"p/', '/}}/', '/},{/');
            $replace = array('{<br>"f', ',<br>"t', ',<br>"p', '}<br>}', '},<br>{');
            return preg_replace($patterns, $replace, $output);
        }

        public static function getInfoForItem(\App\Model\Item $item, bool $beautify = false)
        {
            if ($beautify){
                return GetInfo::beautifyJson($item->infoToArray());
            } else {
                return json_encode($item->infoToArray(), JSON_UNESCAPED_UNICODE);
            }
        }

        public static function getInfoForWaerhouse(\App\Model\Warehouse $warehouse, bool $beautify = false)
        {
            if ($beautify){
                return GetInfo::beautifyJson($warehouse->fullInfoToArray());
            } else {
                return json_encode($warehouse->fullInfoToArray(), JSON_UNESCAPED_UNICODE);
            }
        }

        public static function getInfoForCompany(\App\Model\Company $company, bool $beautify = false)
        {
            if ($beautify){
                return GetInfo::beautifyJson($company->fullInfoToArray());
            } else {
                return json_encode($company->fullInfoToArray(), JSON_UNESCAPED_UNICODE);
            }
        }
    }