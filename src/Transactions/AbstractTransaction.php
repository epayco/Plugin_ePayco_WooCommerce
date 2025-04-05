<?php

namespace Epayco\Woocommerce\Transactions;

use Epayco\Woocommerce\Gateways\AbstractGateway;
use Epayco\Woocommerce\WoocommerceEpayco;

use Epayco as EpaycoSdk;
abstract class AbstractTransaction
{

    protected WoocommerceEpayco $epayco;

    protected AbstractGateway $gateway;

    /**
     * Abstract Transaction constructor
     *
     * @param AbstractGateway $gateway
     * @param \WC_Order|null $order
     * @param array|null $checkout
     */
    public function __construct(AbstractGateway $gateway, ?\WC_Order $order, array $checkout = null)
    {
        global $epayco;

        $this->epayco = $epayco;
        $this->gateway     = $gateway;
        $this->sdk         = $this->getSdkInstance();
    }

    /**
     * Get SDK instance
     */
    public function getSdkInstance()
    {

        $lang = get_locale();
        $lang = explode('_', $lang);
        $lang = $lang[0];
        $public_key = $this->epayco->sellerConfig->getCredentialsPublicKeyPayment();
        $private_key = $this->epayco->sellerConfig->getCredentialsPrivateKeyPayment();
        $isTestMode = $this->epayco->storeConfig->isTestMode()?"true":"false";
        return new EpaycoSdk\Epayco(
            [
                "apiKey" => $public_key,
                "privateKey" => $private_key,
                "lenguage" => strtoupper($lang),
                "test" => $isTestMode
            ]
        );
    }

}