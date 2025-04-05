<?php

namespace Epayco;

use Epayco\Resources\Bank;
use Epayco\Resources\Cash;
use Epayco\Resources\Charge;
use Epayco\Resources\Customers;
use Epayco\Resources\Plan;
use Epayco\Resources\Subscriptions;
use Epayco\Resources\Token;
use Epayco\Resources\Daviplata;
use Epayco\Resources\Safetypay;
use Epayco\Resources\Transaction;

/**
 * Global class constructor
 */
class Epayco
{
    /**
     * Public key client
     * @var String
     */
    public $api_key;
    /**
     * Private key client
     * @var String
     */
    public $private_key;

    /**
     * test mode transaction
     * @var String
     */
    public $test;

    /**
     * lang client errors
     * @var String
     */
    public $lang;

    /**
     * Constructor methods publics
     * @param array $options
     */
    public function __construct($options)
    {
        $this->api_key = $options["apiKey"];
        $this->private_key = $options["privateKey"];
        $this->test = $options["test"] ? "TRUE" : "FALSE";
        $this->lang = $options["lenguage"];

        if ($this->api_key && $this->private_key) {
            $this->token = new Token($this);
            $this->customer = new Customers($this);
            $this->plan = new Plan($this);
            $this->subscriptions = new Subscriptions($this);
            $this->bank = new Bank($this);
            $this->cash = new Cash($this);
            $this->charge = new Charge($this);
            $this->daviplata = new Daviplata($this);
            $this->safetypay = new Safetypay($this);
            $this->transaction = new Transaction($this);
        }


    }
}