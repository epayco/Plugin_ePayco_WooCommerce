<?php

namespace Epayco\Woocommerce\Blocks;

use Epayco\Woocommerce\Helpers\Template;

if (!defined('ABSPATH')) {
    exit;
}

class TicketBlock extends AbstractBlock
{
    protected $scriptName = 'ticket';

    protected $name = 'woo-epayco-ticket';

    /**
     * CustomBlock constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->storeTranslations = $this->epayco->storeTranslations->ticketCheckout;
    }

    /**
     * Set payment block script params
     *
     * @return array
     */
    public function getScriptParams(): array
    {
        return [
            'content' => Template::html(
                'public/checkout/ticket-checkout',
                $this->gateway->getPaymentFieldsParams()
            ),
        ];
    }
}
