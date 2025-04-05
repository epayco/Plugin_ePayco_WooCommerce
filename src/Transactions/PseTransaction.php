<?php

namespace Epayco\Woocommerce\Transactions;

use Epayco\Woocommerce\Gateways\AbstractGateway;
class PseTransaction extends AbstractPaymentTransaction
{
    /**
     * @const
     */
    public const ID = 'pse';

    /**
     * PSE Transaction constructor
     *
     * @param AbstractGateway $gateway
     * @param \WC_Order|null $order
     * @param array $checkout
     */
    public function __construct(AbstractGateway $gateway, ?\WC_Order $order, array $checkout)
    {
        parent::__construct($gateway, $order, $checkout);
    }



}
