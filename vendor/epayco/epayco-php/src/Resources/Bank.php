<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Bank methods
 */
class Bank extends Resource
{
    /**
     * Return list all banks
     * @return object
     */
    public function pseBank($testMode = null)
    {
        $url = '/payment/pse/banks';
        return $this->request(
            "GET",
            $url,
            $this->epayco->api_key,
            null,
            $this->epayco->private_key,
            $this->epayco->test,
            true,
            $this->epayco->lang,
            null,
            null,
            true
        );
    }

    /**
     * Create transaction in ACH
     * @param  Object $options data transaction
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
            "POST",
            "/payment/process/pse",
            $this->epayco->api_key,
            $options,
            $this->epayco->private_key,
            $this->epayco->test,
            false,
            $this->epayco->lang,
            null,
            null,
            true
        );
    }

    /**
     * Return data transaction
     * @param  String $uid id transaction
     * @return object
     */
    public function get($uid = null)
    {
        return $this->request(
                "GET",
                "/restpagos/pse/transactioninfomation.json?transactionID=" . $uid . "&&public_key=" . $this->epayco->api_key,
                $api_key = $this->epayco->api_key,
                $uid,
                $private_key = $this->epayco->private_key,
                $test = $this->epayco->test,
                $switch = true,
                $lang = $this->epayco->lang
        );
    }
}
