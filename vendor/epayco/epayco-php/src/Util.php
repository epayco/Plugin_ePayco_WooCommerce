<?php

namespace Epayco;

use Epayco\Utils\PaycoAes;

class Util
{
        public function setKeys($array)
        {
            $aux = array();
            $file = dirname(__FILE__) . "/Utils/key_lang.json";
            $values = json_decode(file_get_contents($file), true);
            foreach ($array as $key => $value) {
                if (array_key_exists($key, $values)) {
                    $aux[$values[$key]] = $value;
                } else {
                    $aux[$key] = $value;
                }
            }
            return $aux;
        }

        public function setKeys_apify($array)
        {
            $aux = array();
            $file = dirname(__FILE__) . "/Utils/key_lang_apify.json";
            $values = json_decode(file_get_contents($file), true);
            if(!is_null($array)){
                foreach ($array as $key => $value) {
                    if (array_key_exists($key, $values)) {
                        $aux[$values[$key]] = $value;
                    } else {
                        $aux[$key] = $value;
                    }
                }
            }

            return $aux;
        }

        public function mergeSet($data, $test, $lang, $private_key, $api_key, $cash)
        {
            $data["ip"] = isset($data["ip"]) ? $data["ip"] : getHostByName(getHostName());
            $data["test"] = $test;

            /**
             * Init AES
             * @var PaycoAes
             */

          if ($cash) {
              $aes = new PaycoAes($private_key, Client::IV, $lang);
              $adddata = array(
                "public_key" => $api_key,
                "i" => base64_encode(Client::IV),
                "enpruebas" => $aes->encrypt($test),
                "lenguaje" => Client::LENGUAGE,
                "p" => "",
            );
            return array_merge($data, $adddata);
                                   
          }else{
            $aes = new PaycoAes($private_key, Client::IV, $lang);
            $encryptData = $aes->encryptArray($data);
            $adddata = array(
                "public_key" => $api_key,
                "i" => base64_encode(Client::IV),
                "enpruebas" => $aes->encrypt($test),
                "lenguaje" => Client::LENGUAGE,
                "p" => "",
            );
            return array_merge($encryptData, $adddata);
        }
        }
}