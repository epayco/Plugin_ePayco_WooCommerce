<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Safetypay payment methods
 */
class Safetypay extends Resource
{
    /**
     * Create safetypay trx
     * @param  object $options data
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
               "POST",
               "/payment/process/safetypay",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang,
               false,
               false,
               $apify = true
        );
    }
}