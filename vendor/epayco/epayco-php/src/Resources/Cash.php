<?php

namespace Epayco\Resources;

use Epayco\Resource;
use Epayco\Exceptions\ErrorException;

/**
 * Cash payment methods
 */
class Cash extends Resource
{
    /**
     * Return data payment cash
     * @param  array $options data transaction
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
            "POST",
            "/payment/process/cash",
            $this->epayco->api_key,
            $options,
            $this->epayco->private_key,
            $this->epayco->test,
            false,
            $this->epayco->lang,
            true,
            false,
            true
        );
    }

    /**
     * Return data transaction
     * @param  String $uid id transaction
     * @return object
     */
    public function transaction($uid = null)
    {
        return $this->request(
                "GET",
                "/restpagos/transaction/response.json?ref_payco=" . $uid . "&public_key=" . $this->epayco->api_key,
                $api_key = $this->epayco->api_key,
                $uid,
                $private_key = $this->epayco->private_key,
                $test = $this->epayco->test,
                $switch = true,
                $lang = $this->epayco->lang
        );
    }
}
