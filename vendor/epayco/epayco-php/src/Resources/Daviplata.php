<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Daviplata payment methods
 */
class Daviplata extends Resource
{
    /**
     * Create daviplata trx
     * @param  object $options data
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
               "POST",
               "/payment/process/daviplata",
               $this->epayco->api_key,
               $options,
               $this->epayco->private_key,
               $this->epayco->test,
               false,
               $this->epayco->lang,
               false,
               false,
               $apify = true
        );
    }

    /**
     * Return data confirm
     * @param  object $options data
     * @return object
     */
    public function confirm($options = null)
    {
        return $this->request(
                "POST",
                "/payment/confirm/daviplata",
                $api_key = $this->epayco->api_key,
                $options,
                $private_key = $this->epayco->private_key,
                $test = $this->epayco->test,
                $switch = true,
                $lang = $this->epayco->lang,
                false,
                false,
                $apify = true
        );
    }
}