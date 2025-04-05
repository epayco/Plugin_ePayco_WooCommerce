<?php

namespace Epayco\Woocommerce\Blocks;

use Epayco\Woocommerce\Helpers\Template;

if (!defined('ABSPATH')) {
    exit;
}
class SubscriptionBlock extends AbstractBlock
{
    protected $scriptName = 'subscription';

    protected $name = 'woo-epayco-subscription';

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
                'public/checkout/subscription-checkout',
                $this->gateway->getPaymentFieldsParams()
            ),
        ];
    }
}