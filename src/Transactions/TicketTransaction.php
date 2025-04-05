<?php

namespace Epayco\Woocommerce\Transactions;

use Epayco\Woocommerce\Gateways\AbstractGateway;

class TicketTransaction extends AbstractPaymentTransaction
{

    /**
     * Ticket Transaction constructor
     *
     * @param AbstractGateway $gateway
     * @param \WC_Order $order
     * @param array $checkout
     */
    public function __construct(AbstractGateway $gateway, \WC_Order $order, array $checkout)
    {
        parent::__construct($gateway, $order, $checkout);
    }

    /**
     * Get expiration date
     *
     * @return string
     */
    public function getExpirationDate(): string
    {
        $expirationDate = $this->epayco->hooks->options->getGatewayOption(
            $this->gateway,
            'date_expiration',
            EP_TICKET_DATE_EXPIRATION
        );

        return self::sumToNowDate($expirationDate . ' days');
    }

    /**
     * Sum now() with $value in GMT/CUT format
     *
     * @param string $value
     *
     * @return string
     */
    public static function sumToNowDate(string $value): string
    {
        if ($value) {
            return gmdate('Y-m-d\TH:i:s.000O', strtotime('+' . $value));
        }

        return gmdate('Y-m-d\TH:i:s.000O');
    }
}