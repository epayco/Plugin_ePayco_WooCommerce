<?php

namespace Epayco\Woocommerce\Order;
use Epayco\Woocommerce\Hooks\OrderMeta;
use WC_Order;

if (!defined('ABSPATH')) {
    exit;
}

class OrderMetadata
{
    public const PAYMENTS_IDS = '_Epayco_Payment_IDs';
    private const BLOCKS_PAYMENT = 'blocks_payment';
    private const USED_GATEWAY = '_used_gateway';
    private const TICKET_TRANSACTION_DETAILS = '_transaction_details_ticket';
    private const DAVIPLATA_TRANSACTION_DETAILS = '_transaction_details_daviplata';
    private OrderMeta $orderMeta;
    /**
     * Metadata constructor
     *
     * @param OrderMeta $orderMeta
     */
    public function __construct(OrderMeta $orderMeta)
    {
        $this->orderMeta = $orderMeta;
    }

    /**
     * @param WC_Order $order
     * @param mixed $value
     *
     * @return void
     */
    public function setTicketTransactionDetailsData(WC_Order $order, $value): void
    {
        $this->orderMeta->update($order, self::TICKET_TRANSACTION_DETAILS, $value);
    }

    /**
     * @param WC_Order $order
     *
     * @return mixed
     */
    public function getTicketTransactionDetailsMeta(WC_Order $order)
    {
        return $this->orderMeta->get($order, self::TICKET_TRANSACTION_DETAILS);
    }

    /**
     * @param WC_Order $order
     * @param mixed $value
     *
     * @return void
     */
    public function setDaviplataTransactionDetailsData(WC_Order $order, $value): void
    {
        $this->orderMeta->update($order, self::DAVIPLATA_TRANSACTION_DETAILS, $value);
    }

    /**
     * @param WC_Order $order
     *
     * @return mixed
     */
    public function getDaviplataTransactionDetailsMeta(WC_Order $order)
    {
        return $this->orderMeta->get($order, self::DAVIPLATA_TRANSACTION_DETAILS);
    }

    /**
     * Update an order's payments metadata
     *
     * @param WC_Order $order
     * @param array $paymentsId
     *
     * @return void
     */
    public function updatePaymentsOrderMetadata(WC_Order $order, array $paymentsId)
    {
        $paymentsIdMetadata = $this->getPaymentsIdMeta($order);

        if (empty($paymentsIdMetadata)) {
            $this->setPaymentsIdData($order, implode(', ', $paymentsId));
        }

        foreach ($paymentsId as $paymentId) {
            $date                  =  gmdate('Y-m-d H:i:s');
            $paymentDetailKey      = "Epayco - Payment $paymentId";
            $paymentDetailMetadata = $this->orderMeta->get($order, $paymentDetailKey);

            if (empty($paymentDetailMetadata)) {
                $this->orderMeta->update($order, $paymentDetailKey, "[Date $date]");
            }
        }
    }

    /**
     * @param WC_Order $order
     * @param bool $single
     *
     * @return mixed
     */
    public function getPaymentsIdMeta(WC_Order $order, bool $single = true)
    {
        return $this->orderMeta->get($order, self::PAYMENTS_IDS, $single);
    }

    /**
     * @param WC_Order $order
     * @param mixed $value
     *
     * @return void
     */
    public function setPaymentsIdData(WC_Order $order, $value): void
    {
        $this->orderMeta->add($order, self::PAYMENTS_IDS, $value);
    }

    /**
     * Update an order's payments metadata
     *
     * @param WC_Order $order
     * @param string $value
     *
     * @return void
     */
    public function markPaymentAsBlocks(WC_Order $order, string $value)
    {
        $this->orderMeta->update($order, self::BLOCKS_PAYMENT, $value);
    }

    /**
     * @param WC_Order $order
     *
     * @return mixed
     */
    public function getUsedGatewayData(WC_Order $order)
    {
        return $this->orderMeta->get($order, self::USED_GATEWAY);
    }
}