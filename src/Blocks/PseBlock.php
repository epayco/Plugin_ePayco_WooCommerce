<?php

namespace Epayco\Woocommerce\Blocks;

use Epayco\Woocommerce\Helpers\Template;

if (!defined('ABSPATH')) {
    exit;
}

class PseBlock extends AbstractBlock
{
    /**
     * @var string
     */
    protected $scriptName = 'pse';

    /**
     * @var string
     */
    protected $name = 'woo-epayco-pse';

    /**
     * CustomBlock constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->storeTranslations = $this->epayco->storeTranslations->pseCheckout;
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
                'public/checkout/pse-checkout',
                $this->gateway->getPaymentFieldsParams()
            ),
        ];
    }
}
