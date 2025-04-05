<?php

namespace Epayco\Woocommerce\Blocks;

use Epayco\Woocommerce\Helpers\Template;

if (!defined('ABSPATH')) {
    exit;
}
class CheckoutBlock extends AbstractBlock
{
    protected $scriptName = 'checkout';

    protected $name = 'woo-epayco-checkout';

    /**
     * SubscriptionBlock constructor
     */
    public function __construct()
    {
        parent::__construct();
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
                'public/checkout/epayco-checkout',
                $this->gateway->getPaymentFieldsParams()
            ),
        ];
    }
}