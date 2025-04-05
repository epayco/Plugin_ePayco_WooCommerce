<?php

namespace Epayco\Utils;

/**
 * Epayco library encrypt based in AES
 */
class OpensslEncrypt
{   

    private $cipher  = "aes-256-cbc";
    private $key;
    private $iv;
    public function __construct($_key, $_iv, $lang){
        $this->key  = $_key;
        $this->iv   = $_iv;
    }

    public function encrypt($data){
        $ciphertext = openssl_encrypt(
                $data
              , $this->cipher
              , $this->key
              , OPENSSL_RAW_DATA
              , $this->iv);
        return \base64_encode($ciphertext);
    }

    public function decrypt($data){
        $plaintext_dec = openssl_decrypt(
                  base64_decode($data)
                , $this->cipher
                , $this->key
                , OPENSSL_RAW_DATA
                , $this->iv);
        return $plaintext_dec;
    }

    private function addpadPKCS7($data, $block_size){
        $pad = $block_size - (strlen($data) % $block_size);
        $data .= str_repeat(chr($pad), $pad);
        return $data;
    }

    private function unpadPKCS7($data){
        $last = substr($data, -1);
        return substr($data, 0, strlen($data) - ord($last));
    }

    public function encryptArray($arrdata){
        $aux = array();
        foreach ($arrdata as $key => $value) {
            $aux[$key] = $this->encrypt($value);
        }
        return $aux;
    }
}
?>