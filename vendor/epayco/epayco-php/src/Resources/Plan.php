<?php

namespace Epayco\Resources;

use Epayco\Resource;

/**
 * Plan methods
 */
class Plan extends Resource
{
    /**
     * Create plan
     * @param  object $options data from plan
     * @return object
     */
    public function create($options = null)
    {
        return $this->request(
               "POST",
               "/recurring/v1/plan/create",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    /**
     * Get plan from id
     * @param   $uid id plan
     * @return object
     */
    public function get($uid)
    {
        return $this->request(
               "GET",
               "/recurring/v1/plan/" . $this->epayco->api_key . "/" . $uid . "/",
               $api_key = $this->epayco->api_key,
               $options = null,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    /**
     * Get list all plans from client epayco
     * @return object
     */
    public function getList()
    {
        return $this->request(
               "GET",
               "/recurring/v1/plans/" . $this->epayco->api_key,
               $api_key = $this->epayco->api_key,
               $options = null,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

     /**
      * Update plan
      * @param  String $uid     id plan
      * @param  object $options contenten update
      * @return object
      */
    public function update($uid, $options = null)
    {
        return $this->request(
               "POST",
               "/recurring/v1/plan/edit/" . $this->epayco->api_key . "/" . $uid . "/",
               $api_key = $this->epayco->api_key,
               $options,
               $private_key = $this->epayco->private_key,
               $test = $this->epayco->test,
               $switch = false,
               $lang = $this->epayco->lang
        );
    }

    /**
     * remove plan
     * @param  String $uid     id plan
     * @param  object $options contenten update
     * @return object
     */
   public function remove($uid, $options = null)
   {
       return $this->request(
              "POST",
              "/recurring/v1/plan/remove/" . $this->epayco->api_key . "/" . $uid . "/",
              $api_key = $this->epayco->api_key,
              $options = null,
              $private_key = $this->epayco->private_key,
              $test = $this->epayco->test,
              $switch = false,
              $lang = $this->epayco->lang
       );
   }

    /**
    * graphql query client epayco
    * @return object
    */
    public function query($query,$type,$custom_key = null){

        return $this->graphql($query,'plan',$this->epayco->api_key,$type,$custom_key);
    }
}