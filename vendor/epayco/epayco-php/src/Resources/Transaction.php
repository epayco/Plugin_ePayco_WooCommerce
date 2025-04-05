<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Class Transaction
 *
 * Genrate get transaction
 */
Class Transaction extends Resource{
    /**
     * Return data payment cash
     * @param  string $options data transaction
     * @return object
     */
    public function get($option, $apify=false, $method='GET')
    {
        if(!$apify){
            $url = "/transaction/response.json?ref_payco=".$option."&&public_key=".$this->epayco->api_key;
            $options = [];
        }else{
            $url = "/transaction/detail";
            $options = $option;
        }
        return $this->request(
            $method,
            $url,
            $this->epayco->api_key,
            $options,
            $this->epayco->private_key,
            $this->epayco->test,
            true,
            $this->epayco->lang,
            true,
            false,
            $apify
        );
    }
}