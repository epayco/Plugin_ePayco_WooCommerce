<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Genrate token credit card tokenize
 */
class Token extends Resource
{
    /**
     * Return id tokenize credit card
     * @param  array $options credit card info
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
               "POST",
               "/v1/tokens",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }
    /**
     * Return id tokenize credit card without cv
     * @param  array $options credit card info
     * @return object
     */
    public function createNoCvc($options = null)
    {
        return $this->request(
            "POST",
            "/v1/nocvc/tokens",
            $api_key = $this->epayco->api_key,
            $options,
            $private_key = $this->epayco->private_key,
            $test = $this->epayco->test,
            $switch = false,
            $lang = $this->epayco->lang
        );
    }
    /**
     * remove temporal card with cvs
     * @param  array $options credit card info
     * @return object
     */
    public function remove($options = null)
    {
        return $this->request(
            "POST",
            "/v1/remove/tokens",
            $api_key = $this->epayco->api_key,
            $options,
            $private_key = $this->epayco->private_key,
            $test = $this->epayco->test,
            $switch = false,
            $lang = $this->epayco->lang
        );
    }
}