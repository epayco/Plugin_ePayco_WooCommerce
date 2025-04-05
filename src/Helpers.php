<?php

namespace Epayco\Woocommerce;
use Epayco\Woocommerce\Helpers\PaymentMethods;
use Epayco\Woocommerce\Helpers\Session;
use Epayco\Woocommerce\Helpers\Url;

if (!defined('ABSPATH')) {
    exit;
}

class Helpers
{
    public PaymentMethods $paymentMethods;

    public Session $session;

    public Url $url;

    public function __construct(
        PaymentMethods $paymentMethods,
        Session $session,
        Url $url
    ){
        $this->paymentMethods = $paymentMethods;
        $this->session        = $session;
        $this->url      = $url;
    }
}