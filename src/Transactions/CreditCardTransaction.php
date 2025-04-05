<?php

namespace Epayco\Woocommerce\Transactions;

use Epayco\Woocommerce\Gateways\AbstractGateway;
class CreditCardTransaction extends AbstractPaymentTransaction
{
    /**
     * @const
     */
    public const ID = 'credit_card';

    /**
     * Custom Transaction constructor
     *
     * @param AbstractGateway $gateway
     * @param \WC_Order $order
     * @param array $checkout
     */
    public function __construct(AbstractGateway $gateway, \WC_Order $order, array $checkout)
    {
        parent::__construct($gateway, $order, $checkout);
    }

}
