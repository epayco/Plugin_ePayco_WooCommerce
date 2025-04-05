<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Charge payment methods
 */
class Charge extends Resource
{
    /**
     * Create charge
     * @param  object $options data charge
     * @param boolean $discount
     * @return object
     */
    public function create($options = null,$discount = false)
    {
        $url = $discount == true ? "/payment/v1/charge/discount/create" : "/payment/v1/charge/create";
        return $this->request(
               "POST",
               $url,
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
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

    /**
     * @param  object $options data charge
     * @param integer $permission
     * @return object
     */
    public function revert($options,$permission)
    {
        $options["enabled_key"] = $permission;

        return $this->request(
            "POST",
            "/payment/v1/revert/discount/create",
            $api_key = $this->epayco->api_key,
            $options,
            $private_key = $this->epayco->private_key,
            $test = $this->epayco->test,
            $switch = false,
            $lang = $this->epayco->lang
        );
    }
}