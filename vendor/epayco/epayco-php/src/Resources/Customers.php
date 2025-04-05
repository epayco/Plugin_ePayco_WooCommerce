<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Customer methods
 */
class Customers extends Resource
{
    /**
     * Create client and asocciate credit card
     * @param  array $options client and token id info
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
               "POST",
               "/payment/v1/customer/create",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    } 

    /**
     * Get client for id
     * @param  String $uid id client
     * @return object
     */
    public function get($uid)
    {
        return $this->request(
               "GET",
               "/payment/v1/customer/" . $this->epayco->api_key . "/" . $uid . "/",
               $api_key = $this->epayco->api_key,
               $options = null,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    /**
     * Get list customer from client epayco
     * @return object
     */
    public function getList()
    {
        return $this->request(
               "GET",
               "/payment/v1/customers/" . $this->epayco->api_key . "/",
               $api_key = $this->epayco->api_key,
               $options = null,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    /**
     * Update customer from client epayco
     * @return object
     */
    public function update($uid, $options = null)
    {
        return $this->request(
               "POST",
               "/payment/v1/customer/edit/" . $this->epayco->api_key . "/" . $uid . "/",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

     /**
     * delete customer from client epayco
     * @return object
     */
    public function delete($options = null)
    {
        return $this->request(
               "POST",
               "/v1/remove/token",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    
     /**
     * add default card
     * @return object
     */
    public function addDefaultCard($options = null)
    {
        return $this->request(
               "POST",
               "/payment/v1/customer/reasign/card/default",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang,
               $cash = false,
               $card = true
        );
    }

    /**
     * add new token
     * @return object
     */
    public function addNewToken($options = null)
    {
        return $this->request(
               "POST",
               "/v1/customer/add/token",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang,
               $cash = false,
               $card = true
        );
    }


    /**
     * graphql query client epayco
     * @return object
     */
      public function query($query,$type,$custom_key = null){

        return $this->graphql($query,'customer',$this->epayco->api_key,$type,$custom_key);
      }
}